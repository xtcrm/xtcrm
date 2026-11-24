<?php
namespace mpp\crm\service;

use app\platform\BaseService;
use mpp\crm\service\OperationLogService;
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
            ], $actorUserId, (int)($data['project_id'] ?? 0));
            $contractNo = $data['contract_no'] ?? ('CT-' . $model->id);
            CustomerLogService::record(
                (int)($data['customer_id'] ?? 0),
                'contract_created',
                "新建合同 {$contractNo}",
                ['contract_id' => $model->id, 'no' => $contractNo, 'amount' => $data['contract_amount'] ?? 0],
                $actorUserId,
                $this->storeId,
                (int)($data['project_id'] ?? 0)
            );
            OperationLogService::logAdd('合同', $model->id, $contractNo);
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
            OperationLogService::logEdit('合同', $id, $data['contract_no'] ?? ($detail['contract_no'] ?? ''), $data);
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
            CustomerLogService::record(
                (int)($detail['customer_id'] ?? 0),
                'contract_deleted',
                '删除合同 ' . ($detail['contract_no'] ?? ''),
                ['contract_id' => $id],
                $actorUserId,
                $this->storeId
            );
            OperationLogService::logDelete('合同', $id, $detail['contract_no'] ?? '');
            EventService::record('contract_signed', 'contract', $id, [
                'customer_id' => $detail['customer_id'] ?? 0, 'deleted' => true,
            ], $actorUserId, (int)($detail['project_id'] ?? 0));
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
            $statusNames = [1 => '草稿', 2 => '已签订', 3 => '履行中', 4 => '已完成', 5 => '已终止'];
            CustomerLogService::record(
                (int)($detail['customer_id'] ?? 0),
                $status == 2 ? 'contract_signed' : 'contract_status_changed',
                '合同 ' . ($detail['contract_no'] ?? '') . '：' . ($statusNames[$status] ?? ('状态' . $status)),
                ['contract_id' => $id, 'from_status' => $oldStatus, 'to_status' => $status],
                $actorUserId,
                $this->storeId,
                (int)($detail['project_id'] ?? 0)
            );
            if ($status == 2) {
                EventService::record('contract_signed', 'contract', $id, [
                    'customer_id' => $detail['customer_id'] ?? 0,
                    'amount' => $detail['contract_amount'] ?? 0,
                    'to_status' => $status,
                ], $actorUserId, (int)($detail['project_id'] ?? 0));
            }
            return ['success' => true, 'data' => null, 'error' => ''];
        }
        return ['success' => false, 'data' => null, 'error' => '状态更新失败'];
    }
}
