<?php

declare(strict_types=1);

namespace app\platform\flow\service;

use app\platform\flow\FlowRegistry;
use app\platform\notification\InAppNotify;
use think\facade\Db;
use think\facade\Log;

/**
 * 流程通知（站内）
 *
 * 失败只记日志，不影响流程推进。
 */
class NotificationService
{
    public static function taskAssigned(int $taskId): void
    {
        try {
            $task = Db::name('flow_task')->where('id', $taskId)->find();
            if (!$task || !in_array((string)$task['status'], ['pending', 'claimed'], true)) return;
            if ((int)$task['assignee_id'] <= 0) return;

            $instance = Db::name('flow_instance')->where('id', (int)$task['instance_id'])->find();
            if (!$instance) return;

            $title = self::businessTitle($instance);
            InAppNotify::create(
                (int)$task['assignee_id'],
                '待办：' . $task['node_name'],
                self::line($title, (string)$task['node_name']),
                'flow',
                'flow',
                (int)$instance['id'],
                (int)$task['store_id']
            );
        } catch (\Throwable $e) {
            Log::error('[flow] taskAssigned notify failed: ' . $e->getMessage());
        }
    }

    public static function instanceEnded(int $instanceId, string $result): void
    {
        try {
            $instance = Db::name('flow_instance')->where('id', $instanceId)->find();
            if (!$instance || (int)$instance['started_by'] <= 0) return;

            $label = [
                'approved' => '流程已完成',
                'rejected' => '流程已驳回',
                'cancelled' => '流程已取消',
                'terminated' => '流程已终止',
            ][$result] ?? '流程已结束';

            InAppNotify::create(
                (int)$instance['started_by'],
                $label . '：' . self::businessTitle($instance),
                self::line(self::businessTitle($instance), $label),
                'flow',
                'flow',
                $instanceId,
                (int)$instance['store_id']
            );
        } catch (\Throwable $e) {
            Log::error('[flow] instanceEnded notify failed: ' . $e->getMessage());
        }
    }

    public static function businessTitle(array $instance): string
    {
        $snapshot = json_decode((string)($instance['business_snapshot'] ?? ''), true);
        if (!is_array($snapshot)) return (string)$instance['biz_type'];

        $config = FlowRegistry::has((string)$instance['biz_type'])
            ? FlowRegistry::get((string)$instance['biz_type'])
            : [];
        $field = (string)($config['title_field'] ?? 'title');
        $title = (string)($snapshot[$field] ?? $snapshot['title'] ?? '');
        if ($title === '') $title = (string)$instance['biz_type'];
        return $title;
    }

    private static function line(string $title, string $node): string
    {
        return "{$title} · {$node}，请及时处理。";
    }

    public static function taskRemind(int $taskId, bool $overdue = false): void
    {
        try {
            $task = Db::name('flow_task')->where('id', $taskId)->find();
            if (!$task || !in_array((string)$task['status'], ['pending', 'claimed'], true)) return;

            $instance = Db::name('flow_instance')->where('id', (int)$task['instance_id'])->find();
            if (!$instance) return;

            $title = self::businessTitle($instance);
            $prefix = $overdue ? '已超时' : '即将到期';
            InAppNotify::create(
                (int)$task['assignee_id'],
                $prefix . '：' . $task['node_name'],
                "{$title} · {$task['node_name']}，请尽快处理。",
                'flow',
                'flow',
                (int)$instance['id'],
                (int)$task['store_id']
            );
        } catch (\Throwable $e) {
            Log::error('[flow] taskRemind notify failed: ' . $e->getMessage());
        }
    }

    public static function taskEscalated(int $taskId, int $userId): void
    {
        try {
            $task = Db::name('flow_task')->where('id', $taskId)->find();
            if (!$task) return;
            $instance = Db::name('flow_instance')->where('id', (int)$task['instance_id'])->find();
            if (!$instance) return;

            $title = self::businessTitle($instance);
            InAppNotify::create(
                $userId,
                '流程超时升级：' . $task['node_name'],
                "{$title} · {$task['node_name']} 已超时，原处理人 {$task['assignee_name']}，请关注。",
                'flow',
                'flow',
                (int)$instance['id'],
                (int)$task['store_id']
            );
        } catch (\Throwable $e) {
            Log::error('[flow] taskEscalated notify failed: ' . $e->getMessage());
        }
    }
}
