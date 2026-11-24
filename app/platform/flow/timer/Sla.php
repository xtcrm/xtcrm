<?php

declare(strict_types=1);

namespace app\platform\flow\timer;

use app\platform\flow\service\NotificationService;
use app\platform\flow\service\OrgResolver;
use app\platform\timer\BaseTask;
use think\facade\Db;
use think\facade\Log;

/**
 * 流程 SLA 扫描：临期提醒 + 超时升级
 *
 * 每 5 分钟最多执行一次（BaseTask::setInterval 防重）。
 */
class Sla extends BaseTask
{
    protected $taskKey = 'flow_sla';
    protected $taskExpire = 300;

    public function handle(array $param): void
    {
        $storeId = (int)($param['storeId'] ?? 0);
        if ($storeId <= 0) return;

        $this->setInterval($storeId, $this->taskKey, $this->taskExpire, function () use ($storeId) {
            $this->scan($storeId);
        });
    }

    private function scan(int $storeId): void
    {
        $now = time();

        // 临期提醒
        $reminders = Db::name('flow_task')
            ->where('store_id', $storeId)
            ->whereIn('status', ['pending', 'claimed'])
            ->where('remind_at', '>', 0)
            ->where('remind_at', '<=', $now)
            ->limit(200)
            ->select()->toArray();

        foreach ($reminders as $task) {
            try {
                NotificationService::taskRemind((int)$task['id'], false);
                Db::name('flow_task')->where('id', (int)$task['id'])->where('store_id', $storeId)
                    ->update(['remind_at' => 0, 'update_time' => $now]);
            } catch (\Throwable $e) {
                Log::error('[flow] remind failed: ' . $e->getMessage());
            }
        }

        // 超时升级
        $overdue = Db::name('flow_task')
            ->where('store_id', $storeId)
            ->whereIn('status', ['pending', 'claimed'])
            ->where('due_at', '>', 0)
            ->where('due_at', '<', $now)
            ->where('escalated_at', 0)
            ->limit(200)
            ->select()->toArray();

        foreach ($overdue as $task) {
            try {
                NotificationService::taskRemind((int)$task['id'], true);
                $this->notifyEscalation($storeId, $task);
                Db::name('flow_task')->where('id', (int)$task['id'])->where('store_id', $storeId)
                    ->update(['escalated_at' => $now, 'update_time' => $now]);
            } catch (\Throwable $e) {
                Log::error('[flow] escalate failed: ' . $e->getMessage());
            }
        }
    }

    /**
     * 按节点 escalate_to 配置升级；未配置时通知处理人部门负责人
     */
    private function notifyEscalation(int $storeId, array $task): void
    {
        $targets = [];

        $instance = Db::name('flow_instance')
            ->where('store_id', $storeId)
            ->where('id', (int)$task['instance_id'])
            ->find();
        if (!$instance) return;

        $node = Db::name('flow_node')
            ->where('store_id', $storeId)
            ->where('template_id', (int)$instance['template_id'])
            ->where('node_key', (string)$task['node_key'])
            ->find();

        if ($node && !empty($node['escalate_to'])) {
            $rules = json_decode((string)$node['escalate_to'], true);
            if (is_array($rules)) {
                $users = (new OrgResolver($storeId))->resolve($rules, [
                    'variables' => [],
                    'snapshot' => [],
                    'initiator_id' => (int)$instance['started_by'],
                ]);
                foreach ($users as $user) {
                    $targets[] = (int)$user['id'];
                }
            }
        }

        if (empty($targets)) {
            $resolver = new OrgResolver($storeId);
            $managerId = $resolver->managerOfUser((int)$task['assignee_id']);
            if ($managerId > 0) $targets[] = $managerId;
        }

        $targets = array_values(array_unique(array_filter($targets)));
        foreach ($targets as $userId) {
            if ($userId === (int)$task['assignee_id']) continue;
            NotificationService::taskEscalated((int)$task['id'], $userId);
        }
    }
}
