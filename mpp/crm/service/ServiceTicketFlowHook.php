<?php

namespace mpp\crm\service;

use app\platform\flow\FlowBizInterface;
use think\facade\Db;

/**
 * 客服单 · 流程接入
 *
 * 只有客服主动「发起流程」时才会创建实例；默认直通不走流程。
 * 引擎不直接写客服单表，状态同步都在这里完成。
 */
class ServiceTicketFlowHook implements FlowBizInterface
{
    const STATUS_LABELS = [1 => '新建', 2 => '处理中', 3 => '待确认', 4 => '已解决', 5 => '已关闭'];

    public function snapshot(int $bizId, int $storeId): array
    {
        $ticket = $this->findTicket($bizId, $storeId);
        return [
            'title'            => (string)$ticket['ticket_no'],
            'ticket_no'        => (string)$ticket['ticket_no'],
            'subtitle'         => (string)$ticket['title'],
            'priority'         => (int)$ticket['priority'],
            'status'           => (int)$ticket['status'],
            'status_text'      => self::STATUS_LABELS[(int)$ticket['status']] ?? '',
            'assignee_user_id' => (int)$ticket['assignee_user_id'],
            'customer_id'      => (int)$ticket['customer_id'],
        ];
    }

    public function detailUrl(int $bizId, int $storeId): string
    {
        return '/crm/service/ticket?id=' . $bizId;
    }

    public function variables(int $bizId, int $storeId): array
    {
        $ticket = $this->findTicket($bizId, $storeId);
        return [
            'priority'         => (int)$ticket['priority'],
            'category'         => (string)$ticket['category'],
            'channel'          => (string)$ticket['channel'],
            'status'           => (int)$ticket['status'],
            'assignee_user_id' => (int)$ticket['assignee_user_id'],
            'created_by'       => (int)$ticket['created_by'],
            'customer_id'      => (int)$ticket['customer_id'],
            'dept_id'          => (int)$ticket['dept_id'],
        ];
    }

    public function onInstanceStart(array $instance): void
    {
        $storeId = (int)$instance['store_id'];
        $ticketId = (int)$instance['biz_id'];
        $ticket = $this->findTicket($ticketId, $storeId);

        $update = [
            'flow_instance_id' => (int)$instance['id'],
            'update_time'      => time(),
        ];
        if (in_array((int)$ticket['status'], [1, 3], true)) {
            $update['status'] = 2;
        }
        Db::name('crm_service_ticket')
            ->where('store_id', $storeId)->where('id', $ticketId)
            ->update($update);

        TouchpointService::record(
            (int)$ticket['customer_id'],
            'service',
            'flow_started',
            '客服单 ' . $ticket['ticket_no'] . '：发起流程',
            ['ticket_id' => $ticketId, 'instance_id' => (int)$instance['id']],
            (int)($instance['started_by'] ?? 0),
            $storeId,
            [
                'source_type' => 'service_ticket',
                'source_id'   => $ticketId,
                'action_url'  => '/crm/service/ticket?id=' . $ticketId,
                'dedup_key'   => 'flow_started:' . (int)$instance['id'],
            ]
        );
    }

    public function onNodeEnter(array $instance, string $nodeKey): void
    {
        // 节点进入不改业务状态，避免和直通状态抢写
    }

    public function onNodeComplete(array $instance, string $nodeKey, array $task): void
    {
        $storeId = (int)$instance['store_id'];
        $ticketId = (int)$instance['biz_id'];
        $ticket = $this->findTicket($ticketId, $storeId);
        $nodeName = (string)($task['node_name'] ?? $nodeKey);

        TouchpointService::record(
            (int)$ticket['customer_id'],
            'service',
            'flow_node',
            '客服单 ' . $ticket['ticket_no'] . "：流程节点「{$nodeName}」完成",
            ['ticket_id' => $ticketId, 'node_key' => $nodeKey, 'node_name' => $nodeName],
            (int)($task['completed_by'] ?? $task['assignee_id'] ?? 0),
            $storeId,
            [
                'source_type' => 'service_ticket',
                'source_id'   => $ticketId,
                'action_url'  => '/crm/service/ticket?id=' . $ticketId,
                'dedup_key'   => 'flow_node:' . $nodeKey . ':' . time(),
            ]
        );
    }

    public function onInstanceEnd(array $instance, string $result): void
    {
        $storeId = (int)$instance['store_id'];
        $ticketId = (int)$instance['biz_id'];
        $ticket = $this->findTicket($ticketId, $storeId);

        $now = time();
        $update = ['flow_instance_id' => 0, 'update_time' => $now];

        $success = in_array($result, ['approved', 'completed'], true);

        if ((int)$ticket['status'] !== 5) {
            if ($success) {
                $update['status'] = 4;
                if (empty($ticket['resolved_at'])) $update['resolved_at'] = $now;
            } else {
                $update['status'] = 2;
            }
        }

        Db::name('crm_service_ticket')
            ->where('store_id', $storeId)->where('id', $ticketId)
            ->update($update);

        $label = $success ? '流程完成' : '流程结束';
        TouchpointService::record(
            (int)$ticket['customer_id'],
            'service',
            'flow_ended',
            '客服单 ' . $ticket['ticket_no'] . '：' . $label,
            ['ticket_id' => $ticketId, 'instance_id' => (int)$instance['id'], 'result' => $result],
            (int)($instance['started_by'] ?? 0),
            $storeId,
            [
                'source_type' => 'service_ticket',
                'source_id'   => $ticketId,
                'action_url'  => '/crm/service/ticket?id=' . $ticketId,
                'dedup_key'   => 'flow_ended:' . (int)$instance['id'],
            ]
        );
    }

    private function findTicket(int $bizId, int $storeId): array
    {
        $ticket = Db::name('crm_service_ticket')
            ->where('store_id', $storeId)->where('id', $bizId)->find();
        if (!$ticket) {
            throw new \app\platform\flow\FlowException('客服单不存在');
        }
        return $ticket;
    }
}
