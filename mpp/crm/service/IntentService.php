<?php
namespace mpp\crm\service;

use app\platform\BaseService;
use mpp\crm\model\Intent as IntentModel;
use mpp\crm\model\Project as ProjectModel;
use think\facade\Db;

/**
 * 需求服务
 */
class IntentService extends BaseService
{
    public function getList(array $params, int $storeId, int $viewerId): array
    {
        $this->bindStore($storeId);

        $where = [];
        foreach (['status', 'assignee_user_id', 'sales_user_id', 'customer_id', 'project_id', 'catalog_id', 'keyword'] as $field) {
            if (isset($params[$field]) && $params[$field] !== '') $where[$field] = $params[$field];
        }
        if (($params['view'] ?? '') === 'mine') {
            $where['view'] = 'mine';
            $where['viewer_user_id'] = $viewerId;
        }

        $participant = new ParticipantService();
        $visibleProjectIds = $participant->visibleProjectIds($viewerId, $storeId);
        if ($visibleProjectIds !== null) {
            $where['scope_project_ids'] = $visibleProjectIds;
            $where['scope_customer_ids'] = $this->ownedCustomerIds($viewerId, $storeId);
            $where['viewer_user_id'] = $viewerId;
        }

        $list = (new IntentModel())->getList($where, $params);
        return ['list' => $list];
    }

    public function detail(int $id, int $storeId, int $viewerId): array
    {
        $this->bindStore($storeId);
        $intent = (new IntentModel())->detail($id);
        if (!$intent) return ['success' => false, 'error' => '需求不存在'];
        if (!$this->canView($intent, $viewerId, $storeId)) {
            return ['success' => false, 'error' => '无权访问该需求'];
        }

        $quotations = Db::name('crm_quotation')->alias('q')
            ->join('crm_quotation_item i', 'i.quotation_id = q.id AND i.is_delete = 0')
            ->where('q.store_id', $storeId)->where('q.is_delete', 0)
            ->where('i.intent_id', $id)
            ->field(['q.id', 'q.quotation_no', 'q.final_amount', 'q.status', 'q.quotation_date'])
            ->order(['q.id' => 'desc'])->select()->toArray();

        $deliveries = Db::name('workorder_ticket')
            ->where('store_id', $storeId)->where('intent_id', $id)->where('is_delete', 0)
            ->field(['id', 'ticket_no', 'title', 'kind', 'period_no', 'status', 'assignee_user_id', 'expect_date'])
            ->order(['id' => 'desc'])->select()->toArray();

        $activities = Db::name('crm_event_log')
            ->where('store_id', $storeId)->where('target_type', 'intent')->where('target_id', $id)
            ->field(['id', 'event_type', 'actor_user_id', 'data', 'create_time'])
            ->order(['id' => 'desc'])->limit(50)->select()->toArray();

        return ['success' => true, 'data' => [
            'intent' => $intent,
            'quotations' => $quotations,
            'deliveries' => $deliveries,
            'activities' => $activities,
        ]];
    }

    public function add(array $data, int $storeId, int $actorId): array
    {
        $this->bindStore($storeId);
        $model = new IntentModel();
        if (!$model->add($data)) {
            return ['success' => false, 'error' => $model->getError() ?: '创建失败'];
        }

        $projectId = (int)($data['project_id'] ?? 0);
        EventService::record('intent_created', 'intent', (int)$model->id, [
            'intent_no' => $model->intent_no,
            'customer_id' => (int)$data['customer_id'],
            'project_id' => $projectId,
            'source' => (int)($data['source'] ?? 1),
            'source_id' => (int)($data['source_id'] ?? 0),
        ], $actorId, $projectId);

        // 指派了承接人 → 草稿直接进入待报价（状态机 I2）
        if (!empty($data['assignee_user_id'])) {
            $model->changeStatus((int)$model->id, IntentModel::STATUS_TO_QUOTE);
            EventService::record('intent_assigned', 'intent', (int)$model->id, [
                'assignee_user_id' => (int)$data['assignee_user_id'],
            ], $actorId, $projectId);
        }

        if ($projectId) (new ProjectService())->syncStatus($projectId, $storeId);

        return ['success' => true, 'data' => ['id' => (int)$model->id, 'intent_no' => $model->intent_no]];
    }

    public function edit(int $id, array $data, int $storeId, int $actorId): array
    {
        $this->bindStore($storeId);
        $intent = (new IntentModel())->detail($id);
        if (!$intent) return ['success' => false, 'error' => '需求不存在'];
        if (!$this->canEdit($intent, $actorId, $storeId)) {
            return ['success' => false, 'error' => '无权编辑该需求'];
        }

        $model = new IntentModel();
        if (!$model->edit($id, $data)) {
            return ['success' => false, 'error' => $model->getError() ?: '保存失败'];
        }
        EventService::record('intent_updated', 'intent', $id, ['fields' => array_keys($data)], $actorId, (int)$intent['project_id']);
        return ['success' => true, 'data' => null];
    }

    public function assign(int $id, int $assigneeUserId, int $storeId, int $actorId): array
    {
        $this->bindStore($storeId);
        $intent = (new IntentModel())->detail($id);
        if (!$intent) return ['success' => false, 'error' => '需求不存在'];
        if (!$this->canEdit($intent, $actorId, $storeId)) {
            return ['success' => false, 'error' => '无权指派该需求'];
        }

        $model = new IntentModel();
        if (!$model->assign($id, $assigneeUserId)) {
            return ['success' => false, 'error' => $model->getError() ?: '指派失败'];
        }
        // 统一带上 assignee_user_id，前端时间线直接读这个字段
        EventService::record('intent_assigned', 'intent', $id, [
            'assignee_user_id' => $assigneeUserId,
            'from' => (int)($intent['assignee_user_id'] ?? 0),
            'to' => $assigneeUserId,
        ], $actorId, (int)$intent['project_id']);

        if ((int)$intent['status'] === IntentModel::STATUS_DRAFT) {
            $model->changeStatus($id, IntentModel::STATUS_TO_QUOTE);
        }
        return ['success' => true, 'data' => null];
    }

    public function hold(int $id, string $reason, ?int $heldUntil, int $storeId, int $actorId): array
    {
        return $this->transition($id, IntentModel::STATUS_HELD, $reason, $heldUntil, $storeId, $actorId, 'intent_held');
    }

    public function resume(int $id, int $storeId, int $actorId): array
    {
        return $this->transition($id, IntentModel::STATUS_TO_QUOTE, '重新启动', null, $storeId, $actorId, 'intent_resumed');
    }

    public function close(int $id, string $reason, int $storeId, int $actorId): array
    {
        return $this->transition($id, IntentModel::STATUS_CLOSED, $reason, null, $storeId, $actorId, 'intent_closed');
    }

    public function confirm(int $id, int $storeId, int $actorId): array
    {
        return $this->transition($id, IntentModel::STATUS_WON, '确认成交', null, $storeId, $actorId, 'intent_won');
    }

    /** 报价发送：待报价 → 已报价（供 QuotationService 调用） */
    public function markQuoted(int $intentId, int $storeId, int $actorId = 0): void
    {
        $this->bindStore($storeId);
        $intent = (new IntentModel())->detail($intentId);
        if (!$intent || (int)$intent['status'] !== IntentModel::STATUS_TO_QUOTE) return;
        (new IntentModel())->changeStatus($intentId, IntentModel::STATUS_QUOTED);
        EventService::record('intent_quoted', 'intent', $intentId, [], $actorId, (int)$intent['project_id']);
    }

    /** 报价确认/订单生效：已报价 → 已成交 */
    public function markWon(int $intentId, int $storeId, int $actorId = 0): void
    {
        $this->bindStore($storeId);
        $intent = (new IntentModel())->detail($intentId);
        if (!$intent || (int)$intent['status'] !== IntentModel::STATUS_QUOTED) return;
        (new IntentModel())->changeStatus($intentId, IntentModel::STATUS_WON);
        EventService::record('intent_won', 'intent', $intentId, [], $actorId, (int)$intent['project_id']);
        if (!empty($intent['project_id'])) (new ProjectService())->syncStatus((int)$intent['project_id'], $storeId);
    }

    /** 报价拒绝/过期：已报价 → 待报价 */
    public function markLost(int $intentId, int $storeId, int $actorId = 0, string $reason = ''): void
    {
        $this->bindStore($storeId);
        $intent = (new IntentModel())->detail($intentId);
        if (!$intent || (int)$intent['status'] !== IntentModel::STATUS_QUOTED) return;
        (new IntentModel())->changeStatus($intentId, IntentModel::STATUS_TO_QUOTE, $reason);
        EventService::record('intent_requote', 'intent', $intentId, ['reason' => $reason], $actorId, (int)$intent['project_id']);
    }

    private function transition(int $id, int $to, string $reason, ?int $heldUntil, int $storeId, int $actorId, string $eventType): array
    {
        $this->bindStore($storeId);
        $intent = (new IntentModel())->detail($id);
        if (!$intent) return ['success' => false, 'error' => '需求不存在'];
        if (!$this->canEdit($intent, $actorId, $storeId)) {
            return ['success' => false, 'error' => '无权操作该需求'];
        }
        if ($to === IntentModel::STATUS_CLOSED && trim($reason) === '') {
            return ['success' => false, 'error' => '请填写关闭原因'];
        }

        $model = new IntentModel();
        if (!$model->changeStatus($id, $to, $reason, $heldUntil)) {
            return ['success' => false, 'error' => $model->getError() ?: '操作失败'];
        }
        EventService::record($eventType, 'intent', $id, ['reason' => $reason], $actorId, (int)$intent['project_id']);
        if (!empty($intent['project_id'])) (new ProjectService())->syncStatus((int)$intent['project_id'], $storeId);
        return ['success' => true, 'data' => null];
    }

    private function canView($intent, int $viewerId, int $storeId): bool
    {
        // 承接人/销售始终能看自己跟的需求（即使不是项目参与人）
        if (in_array($viewerId, [(int)$intent['assignee_user_id'], (int)$intent['sales_user_id']], true)) return true;

        $participant = new ParticipantService();
        if ($participant->canView((int)$intent['project_id'], $viewerId, $storeId)) return true;

        if ((int)$intent['project_id'] === 0) {
            return in_array((int)$intent['customer_id'], $this->ownedCustomerIds($viewerId, $storeId), true);
        }
        return false;
    }

    private function canEdit($intent, int $actorId, int $storeId): bool
    {
        $participant = new ParticipantService();
        if ((int)$intent['project_id'] > 0) {
            if ($participant->canEdit((int)$intent['project_id'], $actorId, $storeId)) return true;
            // 能看不能改时，需求承接人仍可推进自己的需求
            return (int)$intent['assignee_user_id'] === $actorId;
        }
        // 未立项：超管 / 承接人 / 销售 / 客户负责人 / 客户协作人
        if ($participant->canView(0, $actorId, $storeId)) return true;
        if (in_array($actorId, [(int)$intent['assignee_user_id'], (int)$intent['sales_user_id']], true)) return true;
        return in_array((int)$intent['customer_id'], $this->ownedCustomerIds($actorId, $storeId), true);
    }

    /** 当前用户作为负责人/协作人的客户 ID */
    private function ownedCustomerIds(int $viewerId, int $storeId): array
    {
        $owned = Db::name('crm_customer')
            ->where('store_id', $storeId)->where('owner_user_id', $viewerId)->where('is_delete', 0)
            ->column('id');
        $collab = Db::name('crm_customer_collaborator')
            ->where('store_id', $storeId)->where('user_id', $viewerId)->column('customer_id');
        return array_values(array_unique(array_merge(array_map('intval', $owned), array_map('intval', $collab))));
    }

    private function bindStore(int $storeId): void
    {
        IntentModel::$storeId = $storeId;
        ProjectModel::$storeId = $storeId;
    }
}
