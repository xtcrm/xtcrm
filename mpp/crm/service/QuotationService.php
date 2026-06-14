<?php
namespace mpp\crm\service;

use app\platform\BaseService;
use mpp\crm\model\Quotation as QuotationModel;
use think\facade\Db;

class QuotationService extends BaseService
{
    public function getList(array $params): array
    {
        $model = new QuotationModel();
        $list = $model->getList($params);
        return ['success' => true, 'data' => compact('list'), 'error' => ''];
    }

    public function detail(int $id): array
    {
        $model = new QuotationModel();
        $detail = $model->detail($id);
        if (!$detail) return ['success' => false, 'data' => null, 'error' => '报价单不存在'];
        return ['success' => true, 'data' => compact('detail'), 'error' => ''];
    }

    public function add(array $data, int $actorUserId): array
    {
        $model = new QuotationModel();
        if ($model->add($data)) {
            EventService::record('quotation_created', 'quotation', $model->id, [
                'customer_id' => $data['customer_id'] ?? 0,
                'amount' => $data['final_amount'] ?? $data['total_amount'] ?? 0,
                'currency' => $data['currency'] ?? 'CNY',
            ], $actorUserId);
            return ['success' => true, 'data' => ['id' => $model->id], 'error' => ''];
        }
        return ['success' => false, 'data' => null, 'error' => $model->getError() ?: '添加失败'];
    }

    public function edit(int $id, array $data, int $actorUserId): array
    {
        $model = new QuotationModel();
        $detail = $model->detail($id);
        if (!$detail) return ['success' => false, 'data' => null, 'error' => '报价单不存在'];
        if ($detail->edit($data)) {
            return ['success' => true, 'data' => null, 'error' => ''];
        }
        return ['success' => false, 'data' => null, 'error' => $detail->getError() ?: '更新失败'];
    }

    public function delete(int $id, int $actorUserId): array
    {
        $model = new QuotationModel();
        $detail = $model->detail($id);
        if (!$detail) return ['success' => false, 'data' => null, 'error' => '报价单不存在'];
        if ($detail->setDelete($id)) {
            EventService::record('quotation_rejected', 'quotation', $id, [
                'customer_id' => $detail['customer_id'] ?? 0, 'deleted' => true,
            ], $actorUserId);
            return ['success' => true, 'data' => null, 'error' => ''];
        }
        return ['success' => false, 'data' => null, 'error' => $detail->getError() ?: '删除失败'];
    }

    public function changeStatus(int $id, int $status, int $actorUserId): array
    {
        $model = new QuotationModel();
        $detail = $model->detail($id);
        if (!$detail) return ['success' => false, 'data' => null, 'error' => '报价单不存在'];
        $oldStatus = $detail['status'] ?? 0;
        if ($model->changeStatus($id, $status)) {
            // 按状态触发对应事件
            $eventMap = [
                2 => 'quotation_sent',
                3 => 'quotation_confirmed',
                4 => 'quotation_rejected',
                5 => 'quotation_converted',
            ];
            $eventType = $eventMap[$status] ?? null;
            if ($eventType) {
                EventService::record($eventType, 'quotation', $id, [
                    'customer_id' => $detail['customer_id'] ?? 0,
                    'amount' => $detail['final_amount'] ?? 0,
                    'from_status' => $oldStatus,
                ], $actorUserId);
                try { InsightService::handleEvent($eventType, $detail['customer_id'] ?? 0); } catch (\Throwable $e) {}
            }
            return ['success' => true, 'data' => null, 'error' => ''];
        }
        return ['success' => false, 'data' => null, 'error' => $model->getError() ?: '状态更新失败'];
    }
}
