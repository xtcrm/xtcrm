<?php
namespace mpp\crm\service;

use app\platform\BaseService;
use mpp\crm\model\Contract as ContractModel;

class ContractService extends BaseService
{
    public function getList(array $params): array
    {
        $model = new ContractModel();
        $list = $model->getList($params);
        return ['success' => true, 'data' => compact('list'), 'error' => ''];
    }

    public function detail(int $id): array
    {
        $model = new ContractModel();
        $detail = $model->detail($id);
        if (!$detail) return ['success' => false, 'data' => null, 'error' => '合同不存在'];
        return ['success' => true, 'data' => compact('detail'), 'error' => ''];
    }

    public function add(array $data, int $actorUserId): array
    {
        $model = new ContractModel();
        if ($model->add($data)) {
            EventService::record('contract_signed', 'contract', $model->id, [
                'customer_id' => $data['customer_id'] ?? 0,
                'amount' => $data['contract_amount'] ?? 0,
                'currency' => $data['currency'] ?? 'CNY',
            ], $actorUserId);
            return ['success' => true, 'data' => ['id' => $model->id], 'error' => ''];
        }
        return ['success' => false, 'data' => null, 'error' => $model->getError() ?: '添加失败'];
    }

    public function edit(int $id, array $data, int $actorUserId): array
    {
        $model = new ContractModel();
        $detail = $model->detail($id);
        if (!$detail) return ['success' => false, 'data' => null, 'error' => '合同不存在'];
        if ($detail->edit($data)) {
            return ['success' => true, 'data' => null, 'error' => ''];
        }
        return ['success' => false, 'data' => null, 'error' => $detail->getError() ?: '更新失败'];
    }

    public function delete(int $id, int $actorUserId): array
    {
        $model = new ContractModel();
        $detail = $model->detail($id);
        if (!$detail) return ['success' => false, 'data' => null, 'error' => '合同不存在'];
        if ($detail->setDelete($id)) {
            EventService::record('contract_signed', 'contract', $id, [
                'customer_id' => $detail['customer_id'] ?? 0, 'deleted' => true,
            ], $actorUserId);
            return ['success' => true, 'data' => null, 'error' => ''];
        }
        return ['success' => false, 'data' => null, 'error' => $detail->getError() ?: '删除失败'];
    }

    public function changeStatus(int $id, int $status, int $actorUserId): array
    {
        $model = new ContractModel();
        $detail = $model->detail($id);
        if (!$detail) return ['success' => false, 'data' => null, 'error' => '合同不存在'];
        $oldStatus = $detail['status'] ?? 0;
        $detail->status = $status;
        if ($detail->save()) {
            if ($status == 2) {
                EventService::record('contract_signed', 'contract', $id, [
                    'customer_id' => $detail['customer_id'] ?? 0,
                    'amount' => $detail['contract_amount'] ?? 0,
                ], $actorUserId);
            }
            return ['success' => true, 'data' => null, 'error' => ''];
        }
        return ['success' => false, 'data' => null, 'error' => '状态更新失败'];
    }
}
