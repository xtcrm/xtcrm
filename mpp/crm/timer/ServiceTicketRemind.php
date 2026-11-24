<?php

namespace mpp\crm\timer;

use app\timer\controller\Task;
use mpp\crm\service\NotificationService;
use think\facade\Db;
use think\facade\Log;

/**
 * 客服单到期提醒
 *
 * 只处理填了「期望完成时间」且未解决/未关闭的单：
 *  - 到期前 1 小时提醒处理人
 *  - 到期时提醒处理人，同时提醒其部门经理
 * 提醒一次即写 remind_sent，不重复骚扰。
 */
class ServiceTicketRemind extends Task
{
    private $taskKey = 'ServiceTicketRemind';
    protected $taskExpire = 300;
    private $storeId;

    public function handle(array $param)
    {
        ['storeId' => $this->storeId] = $param;
        $this->setInterval($this->storeId, $this->taskKey, $this->taskExpire, function () {
            try {
                $this->run();
            } catch (\Throwable $e) {
                Log::warning('[ServiceTicketRemind] ' . $e->getMessage());
            }
        });
    }

    public function run(): int
    {
        $storeId = (int)$this->storeId;
        if ($storeId <= 0) return 0;

        $now = time();
        $count = 0;

        $rows = Db::name('crm_service_ticket')
            ->where('store_id', $storeId)
            ->where('is_delete', 0)
            ->where('expect_at', '>', 0)
            ->whereIn('status', [1, 2, 3])
            ->where('remind_sent', '<', 2)
            ->field('id,ticket_no,title,assignee_user_id,created_by,dept_id,expect_at,remind_sent')
            ->order('id', 'asc')
            ->limit(500)
            ->select()->toArray();

        foreach ($rows as $row) {
            $assignee = (int)$row['assignee_user_id'] ?: (int)$row['created_by'];
            if ($assignee <= 0) continue;

            $ticketId = (int)$row['id'];
            $due = (int)$row['expect_at'];
            $no = (string)$row['ticket_no'];
            $title = (string)$row['title'];
            $url = '/crm/service/ticket?id=' . $ticketId;

            if ($now >= $due && (int)$row['remind_sent'] < 2) {
                $this->notify($assignee, $storeId, "客服单已到期：{$no}", "期望完成时间已过，请尽快处理：{$title}", 'service_ticket_due', $ticketId, $url);

                if ((int)$row['dept_id'] > 0) {
                    $managerId = (int)Db::name('store_department')
                        ->where('store_id', $storeId)
                        ->where('id', (int)$row['dept_id'])
                        ->value('manager_id');
                    if ($managerId > 0 && $managerId !== $assignee) {
                        $this->notify($managerId, $storeId, "客服单超时：{$no}", "处理人未按期完成：{$title}", 'service_ticket_overdue', $ticketId, $url);
                    }
                }

                Db::name('crm_service_ticket')
                    ->where('store_id', $storeId)->where('id', $ticketId)
                    ->update(['remind_sent' => 2]);
                $count++;
            } elseif ($now >= $due - 3600 && (int)$row['remind_sent'] === 0) {
                $this->notify($assignee, $storeId, "客服单即将到期：{$no}", "期望完成时间不足 1 小时：{$title}", 'service_ticket_due_soon', $ticketId, $url);

                Db::name('crm_service_ticket')
                    ->where('store_id', $storeId)->where('id', $ticketId)
                    ->update(['remind_sent' => 1]);
                $count++;
            }
        }

        return $count;
    }

    private function notify(int $userId, int $storeId, string $title, string $content, string $type, int $sourceId, string $url): void
    {
        (new NotificationService())->push($userId, $storeId, $title, $content, $type, 'service_ticket', $sourceId, $url);
    }
}
