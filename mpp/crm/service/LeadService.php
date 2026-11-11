<?php
namespace mpp\crm\service;

use app\platform\BaseService;
use mpp\crm\model\Lead as LeadModel;
use mpp\crm\service\OperationLogService;

class LeadService extends BaseService
{
    public function getList(array $params): array
    {
        $model = new LeadModel();
        $list = $model->getList($params);
        return ['success' => true, 'data' => compact('list'), 'error' => ''];
    }

    public function detail(int $id): array
    {
        $model = new LeadModel();
        $detail = $model->detail($id);
        if (!$detail) return ['success' => false, 'data' => null, 'error' => '线索不存在'];
        return ['success' => true, 'data' => compact('detail'), 'error' => ''];
    }

    public function add(array $data, int $actorUserId): array
    {
        $model = new LeadModel();
        if ($model->add($data)) {
            EventService::record('customer_created', 'lead', $model->id, [
                'lead_name' => $data['lead_name'] ?? '', 'owner_id' => $actorUserId,
            ], $actorUserId);
            OperationLogService::logAdd('线索', $model->id, $data['lead_name'] ?? '');
            return ['success' => true, 'data' => ['id' => $model->id], 'error' => ''];
        }
        return ['success' => false, 'data' => null, 'error' => $model->getError() ?: '添加失败'];
    }

    public function edit(int $id, array $data, int $actorUserId): array
    {
        $model = new LeadModel();
        $detail = $model->detail($id);
        if (!$detail) return ['success' => false, 'data' => null, 'error' => '线索不存在'];
        if ($detail->edit($data)) {
            OperationLogService::logEdit('线索', $id, $data['lead_name'] ?? ($detail['lead_name'] ?? ''), $data);
            return ['success' => true, 'data' => null, 'error' => ''];
        }
        return ['success' => false, 'data' => null, 'error' => $detail->getError() ?: '更新失败'];
    }

    public function delete(int $id, int $actorUserId): array
    {
        $model = new LeadModel();
        $detail = $model->detail($id);
        if (!$detail) return ['success' => false, 'data' => null, 'error' => '线索不存在'];
        if ($detail->setDelete($id)) {
            OperationLogService::logDelete('线索', $id, $detail['lead_name'] ?? '');
            return ['success' => true, 'data' => null, 'error' => ''];
        }
        return ['success' => false, 'data' => null, 'error' => $detail->getError() ?: '删除失败'];
    }

    /** 线索转客户 */
    public function convert(int $id, int $actorUserId): array
    {
        $model = new LeadModel();
        $result = $model->convertToCustomer($id);
        if ($result) {
            EventService::record('lead_converted', 'lead', $id, [
                'customer_id' => $result['customer_id'],
                'customer_name' => $result['customer_name'],
            ], $actorUserId);
            OperationLogService::log('convert', '线索', $id, "线索转化客户「{$result['customer_name']}」");
            EventService::record('customer_created', 'customer', $result['customer_id'], [
                'from_lead_id' => $id,
                'customer_name' => $result['customer_name'],
            ], $actorUserId);
            return ['success' => true, 'data' => $result, 'error' => ''];
        }
        return ['success' => false, 'data' => null, 'error' => $model->getError() ?: '转换失败'];
    }
}
