<?php
namespace mpp\crm\service;

use app\platform\BaseService;
use think\facade\Db;
use think\facade\Log;
use skills\workorder\service\TicketService;

/**
 * 交付生成
 *
 * 周期订单（服务期 + 按月/季/年计价）按服务期拆成 N 张 kind=2 的工单；
 * 一次性订单不在这里生成，由人工建工单或后续扩展。
 */
class DeliveryService extends BaseService
{
    public function generatePeriodsFromOrder(int $orderId, int $storeId, int $actorId): array
    {
        $order = Db::name('crm_order')
            ->where('store_id', $storeId)->where('id', $orderId)->where('is_delete', 0)->find();
        if (!$order) return ['success' => false, 'error' => '订单不存在'];
        if ((int)$order['status'] === 6) return ['success' => false, 'error' => '已取消的订单不能生成期次'];

        $start = (int)($order['service_start_time'] ?? 0);
        $end = (int)($order['service_end_time'] ?? 0);
        if ($start <= 0 || $end <= 0 || $end < $start) {
            return ['success' => false, 'error' => '订单没有设置服务期，无法生成期次'];
        }

        $item = Db::name('crm_order_item')
            ->where('store_id', $storeId)->where('order_id', $orderId)->where('is_delete', 0)
            ->where('catalog_id', '>', 0)->find();
        if (!$item) return ['success' => false, 'error' => '订单没有关联服务品类，无法生成期次'];

        $typeId = (int)$item['catalog_id'];
        $type = Db::name('workorder_type')
            ->where('store_id', $storeId)->where('id', $typeId)->where('is_delete', 0)->find();
        if (!$type) return ['success' => false, 'error' => '服务品类不存在'];

        $cycle = (int)($type['billing_cycle'] ?? 1);
        $periods = $this->buildPeriods($start, $end, $cycle);

        $ticketService = (new TicketService())->forStore($storeId);
        $materialsRes = $ticketService->getMaterialList(['type_id' => $typeId, 'status' => 1, 'page' => 1, 'limit' => 200]);
        $materialRows = $materialsRes['data']['list']['data'] ?? [];
        $items = [];
        foreach ($materialRows as $row) {
            $items[] = [
                'item_key' => (string)($row['item_key'] ?? ''),
                'name' => (string)($row['name'] ?? ''),
                'is_required' => (int)($row['is_required'] ?? 0),
                'source' => 1,
            ];
        }

        $actor = $this->actor($actorId, $storeId);
        $assignee = (int)($type['default_assignee_user_id'] ?? 0) ?: (int)$order['owner_user_id'];
        $created = 0;
        $skipped = 0;
        $errors = [];

        foreach ($periods as $i => [$periodStart, $periodEnd]) {
            $periodNo = $i + 1;
            $exists = Db::name('workorder_ticket')
                ->where('store_id', $storeId)->where('order_id', $orderId)
                ->where('period_no', $periodNo)->where('is_delete', 0)->count();
            if ($exists) { $skipped++; continue; }

            $payload = [
                'type_id' => $typeId,
                'kind' => 2,
                'order_id' => $orderId,
                'period_no' => $periodNo,
                'project_id' => (int)$order['project_id'],
                'intent_id' => (int)($item['intent_id'] ?? 0),
                'customer_id' => (int)$order['customer_id'],
                'title' => $type['name'] . ' 第' . $periodNo . '期',
                'content' => '由订单 ' . $order['order_no'] . ' 自动生成（' . date('Y-m-d', $periodStart) . ' ~ ' . date('Y-m-d', $periodEnd) . '）',
                'expect_date' => date('Y-m-d', $periodEnd),
                'assignee_user_id' => $assignee,
                'items' => $items,
            ];
            $result = $ticketService->add($payload, $actor);
            if ($result['success']) $created++;
            else {
                $errors[] = '第' . $periodNo . '期：' . $result['error'];
                Log::error('[期次生成] 订单 ' . $orderId . ' 第' . $periodNo . '期失败：' . $result['error']);
            }
        }

        EventService::record('order_periods_generated', 'order', $orderId, [
            'created' => $created, 'skipped' => $skipped, 'cycle' => $cycle,
            'project_id' => (int)$order['project_id'],
        ], $actorId, (int)$order['project_id']);

        return ['success' => true, 'data' => [
            'created' => $created,
            'skipped' => $skipped,
            'errors' => $errors,
        ]];
    }

    /** 按计费周期切分服务期 */
    private function buildPeriods(int $start, int $end, int $cycle): array
    {
        $periods = [];
        $cursor = $start;
        $guard = 0;
        while ($cursor <= $end && $guard < 60) {
            $next = $this->nextPeriodStart($cursor, $cycle);
            if ($next <= $cursor) $next = $end + 1;
            $periodEnd = min($next - 1, $end);
            $periods[] = [$cursor, $periodEnd];
            $cursor = $next;
            $guard++;
        }
        return $periods ?: [[$start, $end]];
    }

    private function nextPeriodStart(int $timestamp, int $cycle): int
    {
        $y = (int)date('Y', $timestamp);
        $m = (int)date('n', $timestamp);
        switch ($cycle) {
            case 2: // 按月
                $ny = $m === 12 ? $y + 1 : $y;
                $nm = $m === 12 ? 1 : $m + 1;
                return strtotime(sprintf('%04d-%02d-01 00:00:00', $ny, $nm));
            case 3: // 按季
                $total = $y * 12 + ($m - 1) + 3;
                return strtotime(sprintf('%04d-%02d-01 00:00:00', intdiv($total, 12), $total % 12 + 1));
            case 4: // 按年
                return strtotime(sprintf('%04d-%02d-01 00:00:00', $y + 1, $m));
            default: // 一次性 / 按次：整段一个期次
                return $timestamp + 86400;
        }
    }

    private function actor(int $actorId, int $storeId): array
    {
        $user = Db::name('store_user')
            ->where('store_id', $storeId)->where('store_user_id', $actorId)
            ->field(['real_name', 'user_name'])->find();
        return ['type' => 2, 'id' => $actorId, 'name' => $user ? (string)($user['real_name'] ?: $user['user_name']) : ''];
    }
}
