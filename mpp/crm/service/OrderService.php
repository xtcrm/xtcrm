<?php
namespace mpp\crm\service;

use app\platform\BaseService;
use mpp\crm\service\OperationLogService;
use mpp\crm\model\Order as OrderModel;
use think\facade\Db;

class OrderService extends BaseService
{
    public function getList(array $params): array
    {
        $model = new OrderModel();
        $list = $model->getList($params);
        return ['success' => true, 'data' => compact('list'), 'error' => ''];
    }

    public function detail(int $id): array
    {
        $model = new OrderModel();
        $detail = $model->detail($id);
        if (!$detail) return ['success' => false, 'data' => null, 'error' => '订单不存在'];
        return ['success' => true, 'data' => compact('detail'), 'error' => ''];
    }

    public function add(array $data, int $actorUserId): array
    {
        // 从报价转订单：继承项目
        if (empty($data['project_id']) && !empty($data['quotation_id'])) {
            $projectId = Db::name('crm_quotation')
                ->where('store_id', (int)$this->storeId)->where('id', $data['quotation_id'])
                ->value('project_id');
            if (!empty($projectId)) $data['project_id'] = (int)$projectId;
        }

        $model = new OrderModel();
        if ($model->add($data)) {
            EventService::record('order_created', 'order', $model->id, [
                'customer_id' => $data['customer_id'] ?? 0,
                'quotation_id' => $data['quotation_id'] ?? null,
                'amount' => $data['final_amount'] ?? $data['total_amount'] ?? 0,
                'currency' => $data['currency'] ?? 'CNY',
            ], $actorUserId, (int)($data['project_id'] ?? 0));
            if (!empty($data['quotation_id'])) {
                EventService::record('quotation_converted', 'quotation', $data['quotation_id'], [
                    'order_id' => $model->id,
                    'customer_id' => $data['customer_id'] ?? 0,
                ], $actorUserId, (int)($data['project_id'] ?? 0));
                $this->syncIntentsFromQuotation((int)$data['quotation_id'], $actorUserId);
            }
            try { InsightService::handleEvent('order_created', $data['customer_id'] ?? 0); } catch (\Throwable $e) {}
            OperationLogService::logAdd('订单', $model->id, $data['order_no'] ?? ('SO-'.$model->id));
            return ['success' => true, 'data' => ['id' => $model->id], 'error' => ''];
        }
        return ['success' => false, 'data' => null, 'error' => $model->getError() ?: '添加失败'];
    }

    public function edit(int $id, array $data, int $actorUserId): array
    {
        $model = new OrderModel();
        $detail = $model->detail($id);
        if (!$detail) return ['success' => false, 'data' => null, 'error' => '订单不存在'];
        if ($detail->edit($data)) {
            OperationLogService::logEdit('订单', $id, $data['order_no'] ?? ($detail['order_no'] ?? ''), $data);
            return ['success' => true, 'data' => null, 'error' => ''];
        }
        return ['success' => false, 'data' => null, 'error' => $detail->getError() ?: '更新失败'];
    }

    public function delete(int $id, int $actorUserId): array
    {
        $model = new OrderModel();
        $detail = $model->detail($id);
        if (!$detail) return ['success' => false, 'data' => null, 'error' => '订单不存在'];
        if ($detail->setDelete($id)) {
            OperationLogService::logDelete('订单', $id, $detail['order_no'] ?? '');
            EventService::record('order_status_changed', 'order', $id, [
                'customer_id' => $detail['customer_id'] ?? 0, 'deleted' => true,
            ], $actorUserId);
            return ['success' => true, 'data' => null, 'error' => ''];
        }
        return ['success' => false, 'data' => null, 'error' => $detail->getError() ?: '删除失败'];
    }

    public function changeStatus(int $id, int $status, int $actorUserId): array
    {
        $model = new OrderModel();
        $detail = $model->detail($id);
        if (!$detail) return ['success' => false, 'data' => null, 'error' => '订单不存在'];
        $oldStatus = $detail['status'] ?? 0;
        $detail->status = $status;
        if ($detail->save()) {
            EventService::record('order_status_changed', 'order', $id, [
                'customer_id' => $detail['customer_id'] ?? 0,
                'from_status' => $oldStatus,
                'to_status' => $status,
            ], $actorUserId);
            try { InsightService::handleEvent('order_status_changed', $detail['customer_id'] ?? 0); } catch (\Throwable $e) {}
            if (!empty($detail['project_id'])) {
                (new ProjectService())->syncStatus((int)$detail['project_id'], (int)$this->storeId);
            }
            OperationLogService::log('edit', '订单', $id, "订单状态变更「{$detail['order_no']}」: {$oldStatus}→{$status}");
            // 订单进入进行中 → 周期服务按服务期生成期次
            if ($status === 2 && !empty($detail['service_start_time']) && !empty($detail['service_end_time'])) {
                try {
                    (new DeliveryService())->generatePeriodsFromOrder($id, (int)$this->storeId, $actorUserId);
                } catch (\Throwable $e) {
                    \think\facade\Log::error('[订单] 生成期次失败 ' . $id . ': ' . $e->getMessage());
                }
            }
            return ['success' => true, 'data' => null, 'error' => ''];
        }
        return ['success' => false, 'data' => null, 'error' => '状态更新失败'];
    }

    /** 手动生成期次（幂等；订单确认时也会自动调用） */
    public function generatePeriods(int $orderId, int $actorUserId): array
    {
        return (new DeliveryService())->generatePeriodsFromOrder($orderId, (int)$this->storeId, $actorUserId);
    }

    /** 报价转订单后：关联需求置为已成交，并刷新项目状态 */
    private function syncIntentsFromQuotation(int $quotationId, int $actorUserId): void
    {
        $storeId = (int)$this->storeId;
        $rows = Db::name('crm_quotation_item')->alias('i')
            ->join('crm_intent n', 'n.id = i.intent_id')
            ->where('i.store_id', $storeId)->where('i.quotation_id', $quotationId)
            ->where('i.is_delete', 0)->where('i.intent_id', '>', 0)
            ->field(['i.intent_id', 'n.project_id'])
            ->select()->toArray();
        if (empty($rows)) return;

        $intentService = new IntentService();
        $projectIds = [];
        foreach ($rows as $row) {
            $intentService->markWon((int)$row['intent_id'], $storeId, $actorUserId);
            if (!empty($row['project_id'])) $projectIds[(int)$row['project_id']] = true;
        }
        foreach (array_keys($projectIds) as $projectId) {
            (new ProjectService())->syncStatus($projectId, $storeId);
        }
    }
}
