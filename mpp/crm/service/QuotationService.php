<?php
namespace mpp\crm\service;

use app\platform\BaseService;
use mpp\crm\model\Quotation as QuotationModel;
use mpp\crm\service\OperationLogService;
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

        $data = $detail->toArray();
        $storeId = (int)$this->storeId;

        // 所属项目名（列表/详情用标题比裸 ID 有用）
        $data['project_name'] = '';
        if (!empty($data['project_id'])) {
            $data['project_name'] = (string)Db::name('crm_project')
                ->where('store_id', $storeId)->where('id', $data['project_id'])->value('title');
        }

        // 明细行关联的需求信息
        foreach (($data['items'] ?? []) as &$item) {
            $item['intent_no'] = '';
            $item['intent_title'] = '';
            if (!empty($item['intent_id'])) {
                $intent = Db::name('crm_intent')
                    ->where('store_id', $storeId)->where('id', $item['intent_id'])
                    ->field(['intent_no', 'title'])->find();
                if ($intent) {
                    $item['intent_no'] = $intent['intent_no'];
                    $item['intent_title'] = $intent['title'];
                }
            }
        }
        unset($item);

        return ['success' => true, 'data' => ['detail' => $data], 'error' => ''];
    }

    public function add(array $data, int $actorUserId): array
    {
        // 从需求带过来的报价：允许不带 project_id，按第一条关联需求补齐
        if (empty($data['project_id']) && !empty($data['items'])) {
            foreach ($data['items'] as $item) {
                $intentId = (int)($item['intent_id'] ?? 0);
                if ($intentId <= 0) continue;
                $projectId = Db::name('crm_intent')
                    ->where('store_id', (int)$this->storeId)->where('id', $intentId)
                    ->value('project_id');
                if (!empty($projectId)) { $data['project_id'] = (int)$projectId; break; }
            }
        }

        $model = new QuotationModel();
        if ($model->add($data)) {
            EventService::record('quotation_created', 'quotation', $model->id, [
                'customer_id' => $data['customer_id'] ?? 0,
                'amount' => $data['final_amount'] ?? $data['total_amount'] ?? 0,
                'currency' => $data['currency'] ?? 'CNY',
            ], $actorUserId, (int)($data['project_id'] ?? 0));
            OperationLogService::logAdd('报价单', $model->id, $data['quotation_no'] ?? ('Q-' . $model->id));
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
            OperationLogService::logEdit('报价单', $id, $data['quotation_no'] ?? ($detail['quotation_no'] ?? ''), $data);
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
            OperationLogService::logDelete('报价单', $id, $detail['quotation_no'] ?? '');
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
                    'to_status' => $status,
                ], $actorUserId, (int)($detail['project_id'] ?? 0));
                try { InsightService::handleEvent($eventType, $detail['customer_id'] ?? 0); } catch (\Throwable $e) {}
            }

            $this->syncIntentStatus($id, $status, (int)$actorUserId);

            return ['success' => true, 'data' => null, 'error' => ''];
        }
        return ['success' => false, 'data' => null, 'error' => $model->getError() ?: '状态更新失败'];
    }

    /**
     * 报价状态变化时回写关联需求（通过明细行的 intent_id）
     */
    private function syncIntentStatus(int $quotationId, int $status, int $actorUserId): void
    {
        $storeId = (int)$this->storeId;
        $intentIds = Db::name('crm_quotation_item')
            ->where('store_id', $storeId)->where('quotation_id', $quotationId)
            ->where('is_delete', 0)->where('intent_id', '>', 0)
            ->column('intent_id');
        $intentIds = array_values(array_unique(array_map('intval', $intentIds)));
        if (empty($intentIds)) return;

        $intentService = new IntentService();
        if ($status === 2) {
            foreach ($intentIds as $intentId) $intentService->markQuoted($intentId, $storeId, $actorUserId);
        } elseif ($status === 3 || $status === 5) {
            foreach ($intentIds as $intentId) $intentService->markWon($intentId, $storeId, $actorUserId);
        } elseif ($status === 4 || $status === 6) {
            foreach ($intentIds as $intentId) {
                $other = Db::name('crm_quotation_item')->alias('i')
                    ->join('crm_quotation q', 'q.id = i.quotation_id')
                    ->where('i.store_id', $storeId)->where('i.intent_id', $intentId)->where('i.is_delete', 0)
                    ->where('q.store_id', $storeId)->where('q.is_delete', 0)
                    ->where('q.id', '<>', $quotationId)->whereIn('q.status', [2, 3, 5])
                    ->count();
                if ($other === 0) $intentService->markLost($intentId, $storeId, $actorUserId, $status === 4 ? '报价被拒绝' : '报价已过期');
            }
        }
    }
}
