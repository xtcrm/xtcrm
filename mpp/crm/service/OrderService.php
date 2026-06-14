<?php
namespace mpp\crm\service;

use app\platform\BaseService;
use mpp\crm\model\Order as OrderModel;

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
        $model = new OrderModel();
        if ($model->add($data)) {
            EventService::record('order_created', 'order', $model->id, [
                'customer_id' => $data['customer_id'] ?? 0,
                'quotation_id' => $data['quotation_id'] ?? null,
                'amount' => $data['final_amount'] ?? $data['total_amount'] ?? 0,
                'currency' => $data['currency'] ?? 'CNY',
            ], $actorUserId);
            if (!empty($data['quotation_id'])) {
                EventService::record('quotation_converted', 'quotation', $data['quotation_id'], [
                    'order_id' => $model->id,
                    'customer_id' => $data['customer_id'] ?? 0,
                ], $actorUserId);
            }
            try { InsightService::handleEvent('order_created', $data['customer_id'] ?? 0); } catch (\Throwable $e) {}
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
            return ['success' => true, 'data' => null, 'error' => ''];
        }
        return ['success' => false, 'data' => null, 'error' => '状态更新失败'];
    }
}
