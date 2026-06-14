<?php
namespace mpp\crm\service;

use app\platform\BaseService;
use mpp\crm\model\FollowUp as FollowUpModel;

class FollowUpService extends BaseService
{
    public function getList(int $customerId = 0): array
    {
        $model = new FollowUpModel();
        $list = $model->getList($customerId);
        return ['success' => true, 'data' => compact('list'), 'error' => ''];
    }

    public function add(array $data, int $actorUserId): array
    {
        $data['owner_user_id'] = $actorUserId;
        $model = new FollowUpModel();
        if ($model->add($data)) {
            EventService::record('follow_up_completed', 'customer', $data['customer_id'] ?? 0, [
                'followup_id' => $model->id,
                'follow_type' => $data['follow_type'] ?? '',
                'result' => $data['result'] ?? '',
            ], $actorUserId);
            try { InsightService::handleEvent('follow_up_completed', $data['customer_id'] ?? 0); } catch (\Throwable $e) {}
            return ['success' => true, 'data' => ['id' => $model->id], 'error' => ''];
        }
        return ['success' => false, 'data' => null, 'error' => $model->getError() ?: '添加失败'];
    }

    public function edit(int $id, array $data, int $actorUserId): array
    {
        $model = new FollowUpModel();
        $detail = $model->where('id', $id)->find();
        if (!$detail) return ['success' => false, 'data' => null, 'error' => '跟进记录不存在'];
        if ($detail->edit($data)) {
            return ['success' => true, 'data' => null, 'error' => ''];
        }
        return ['success' => false, 'data' => null, 'error' => $detail->getError() ?: '更新失败'];
    }

    public function delete(int $id, int $actorUserId): array
    {
        $model = new FollowUpModel();
        $detail = $model->where('id', $id)->find();
        if (!$detail) return ['success' => false, 'data' => null, 'error' => '跟进记录不存在'];
        $customerId = $detail['customer_id'] ?? 0;
        $detail->is_delete = 1;
        if ($detail->save()) {
            EventService::record('customer_updated', 'customer', $customerId, [
                'followup_deleted' => true, 'followup_id' => $id,
            ], $actorUserId);
            return ['success' => true, 'data' => null, 'error' => ''];
        }
        return ['success' => false, 'data' => null, 'error' => '删除失败'];
    }
}
