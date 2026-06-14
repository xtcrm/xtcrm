<?php
namespace mpp\crm\service;

use app\platform\BaseService;
use mpp\crm\model\Contact as ContactModel;

class ContactService extends BaseService
{
    public function getList(int $customerId = 0): array
    {
        $model = new ContactModel();
        $list = $model->getList($customerId);
        return ['success' => true, 'data' => compact('list'), 'error' => ''];
    }

    public function detail(int $id): array
    {
        $model = new ContactModel();
        $detail = $model->detail($id);
        if (!$detail) return ['success' => false, 'data' => null, 'error' => '联系人不存在'];
        return ['success' => true, 'data' => compact('detail'), 'error' => ''];
    }

    public function add(array $data, int $actorUserId): array
    {
        $model = new ContactModel();
        if ($model->add($data)) {
            EventService::record('customer_updated', 'customer', $data['customer_id'] ?? 0, [
                'contact_added' => true, 'contact_id' => $model->id,
            ], $actorUserId);
            return ['success' => true, 'data' => ['id' => $model->id], 'error' => ''];
        }
        return ['success' => false, 'data' => null, 'error' => $model->getError() ?: '添加失败'];
    }

    public function edit(int $id, array $data, int $actorUserId): array
    {
        $model = new ContactModel();
        $detail = $model->detail($id);
        if (!$detail) return ['success' => false, 'data' => null, 'error' => '联系人不存在'];
        if ($detail->edit($data)) {
            EventService::record('customer_updated', 'customer', $detail['customer_id'] ?? 0, [
                'contact_edited' => true, 'contact_id' => $id,
            ], $actorUserId);
            return ['success' => true, 'data' => null, 'error' => ''];
        }
        return ['success' => false, 'data' => null, 'error' => $detail->getError() ?: '更新失败'];
    }

    public function delete(int $id, int $actorUserId): array
    {
        $model = new ContactModel();
        $detail = $model->detail($id);
        if (!$detail) return ['success' => false, 'data' => null, 'error' => '联系人不存在'];
        $customerId = $detail['customer_id'] ?? 0;
        $detail->is_delete = 1;
        if ($detail->save()) {
            EventService::record('customer_updated', 'customer', $customerId, [
                'contact_deleted' => true, 'contact_id' => $id,
            ], $actorUserId);
            return ['success' => true, 'data' => null, 'error' => ''];
        }
        return ['success' => false, 'data' => null, 'error' => '删除失败'];
    }
}
