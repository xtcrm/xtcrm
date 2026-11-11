<?php
namespace mpp\crm\service;

use app\platform\BaseService;
use mpp\crm\model\Project as ProjectModel;
use mpp\crm\model\Intent as IntentModel;
use mpp\crm\model\ProjectReview as ProjectReviewModel;
use think\facade\Db;

/**
 * 项目服务
 *
 * 项目是需求/承诺/交付/活动的聚合根；状态由子对象推导，不手工维护。
 */
class ProjectService extends BaseService
{
    public function getList(array $params, int $storeId, int $viewerId): array
    {
        $this->bindStore($storeId);
        $participant = new ParticipantService();
        $visible = $participant->visibleProjectIds($viewerId, $storeId);
        $view = (string)($params['view'] ?? 'all');

        $where = [];
        foreach (['keyword', 'status', 'customer_id', 'archived'] as $field) {
            if (isset($params[$field]) && $params[$field] !== '') $where[$field] = $params[$field];
        }

        if ($view === 'mine') {
            $where['mine_user_id'] = $viewerId;
        } elseif ($view === 'joined') {
            $joined = Db::name('crm_project_participant')
                ->where('store_id', $storeId)->where('user_id', $viewerId)->where('status', 1)
                ->column('project_id');
            if (empty($joined)) {
                return ['list' => ['data' => [], 'total' => 0], 'stats' => $this->stats($storeId, $viewerId)];
            }
            $where['project_ids'] = $joined;
        } elseif ($visible !== null) {
            if (empty($visible)) {
                return ['list' => ['data' => [], 'total' => 0], 'stats' => $this->stats($storeId, $viewerId)];
            }
            $where['project_ids'] = $visible;
        }

        $list = (new ProjectModel())->getList($where, $params);
        $list = $this->enrichList($list, $storeId);
        return ['list' => $list, 'stats' => $this->stats($storeId, $viewerId)];
    }

    public function detail(int $id, int $storeId, int $viewerId): array
    {
        $this->bindStore($storeId);
        $project = (new ProjectModel())->detail($id);
        if (!$project) return ['success' => false, 'error' => '项目不存在'];
        if (!(new ParticipantService())->canView($id, $viewerId, $storeId)) {
            return ['success' => false, 'error' => '无权访问该项目'];
        }

        $intents = (new IntentModel())
            ->where('project_id', $id)->where('is_delete', 0)
            ->order(['id' => 'desc'])->select()->toArray();

        $quotations = Db::name('crm_quotation')
            ->where('store_id', $storeId)->where('project_id', $id)->where('is_delete', 0)
            ->field(['id', 'quotation_no', 'final_amount', 'status', 'quotation_date', 'create_time'])
            ->order(['id' => 'desc'])->select()->toArray();
        // 报价关联的需求（用于"已有报价草稿/已发送"的提示）
        $quoteIds = array_map('intval', array_column($quotations, 'id'));
        $quoteIntents = [];
        if (!empty($quoteIds)) {
            $rows = Db::name('crm_quotation_item')->alias('i')
                ->leftJoin('crm_intent n', 'n.id = i.intent_id AND n.store_id = i.store_id')
                ->where('i.store_id', $storeId)->whereIn('i.quotation_id', $quoteIds)->where('i.is_delete', 0)
                ->where('i.intent_id', '>', 0)
                ->field(['i.quotation_id', 'i.intent_id', 'n.intent_no', 'n.title'])
                ->select()->toArray();
            foreach ($rows as $row) {
                $quoteIntents[(int)$row['quotation_id']][] = [
                    'intent_id' => (int)$row['intent_id'],
                    'intent_no' => $row['intent_no'] ?? '',
                    'title' => $row['title'] ?? '',
                ];
            }
        }
        foreach ($quotations as &$quote) {
            $quote['intents'] = $quoteIntents[(int)$quote['id']] ?? [];
        }
        unset($quote);

        $orders = Db::name('crm_order')
            ->where('store_id', $storeId)->where('project_id', $id)->where('is_delete', 0)
            ->field(['id', 'order_no', 'final_amount', 'paid_amount', 'unpaid_amount', 'status', 'payment_status', 'service_start_time', 'service_end_time', 'create_time'])
            ->order(['id' => 'desc'])->select()->toArray();
        $contracts = Db::name('crm_contract')
            ->where('store_id', $storeId)->where('project_id', $id)->where('is_delete', 0)
            ->field(['id', 'contract_no', 'contract_name', 'contract_amount', 'status', 'create_time', 'project_id'])
            ->order(['id' => 'desc'])->select()->toArray();

        $tickets = Db::name('workorder_ticket')
            ->where('store_id', $storeId)->where('project_id', $id)->where('is_delete', 0)
            ->field(['id', 'ticket_no', 'title', 'kind', 'period_no', 'status', 'assignee_user_id', 'expect_date', 'finish_time', 'create_time', 'project_id', 'intent_id'])
            ->order(['id' => 'desc'])->select()->toArray();

        $participants = (new ParticipantService())->listByProject($id, $storeId);

        $followups = Db::name('crm_followup')->alias('f')
            ->leftJoin('store_user u', 'u.store_user_id = f.owner_user_id AND u.store_id = f.store_id')
            ->leftJoin('crm_intent n', 'n.id = f.intent_id AND n.store_id = f.store_id')
            ->where('f.store_id', $storeId)->where('f.project_id', $id)->where('f.is_delete', 0)
            ->field(['f.id', 'f.follow_type', 'f.result', 'f.follow_content', 'f.attachment',
                     'f.next_follow_date', 'f.next_follow_content', 'f.follow_date', 'f.owner_user_id',
                     'f.intent_id', 'n.intent_no', 'n.title AS intent_title',
                     'u.real_name', 'u.user_name'])
            ->order(['f.follow_date' => 'desc', 'f.id' => 'desc'])->limit(100)
            ->select()->toArray();

        $payments = Db::name('crm_payment')->alias('p')
            ->leftJoin('crm_order o', 'o.id = p.order_id AND o.store_id = p.store_id')
            ->leftJoin('store_user u', 'u.store_user_id = p.actor_user_id AND u.store_id = p.store_id')
            ->where('p.store_id', $storeId)->where('p.project_id', $id)->where('p.is_delete', 0)
            ->field(['p.id', 'p.payment_no', 'p.order_id', 'p.amount', 'p.method', 'p.paid_at', 'p.remark',
                     'p.actor_user_id', 'o.order_no', 'u.real_name', 'u.user_name'])
            ->order(['p.paid_at' => 'desc', 'p.id' => 'desc'])->select()->toArray();
        $receivedAmount = 0;
        foreach ($payments as $payment) $receivedAmount += (float)$payment['amount'];

        $timeline = $this->timeline($id, $storeId);

        $won = 0;
        $openIntent = 0;
        foreach ($intents as $row) {
            if ((int)$row['status'] === IntentModel::STATUS_WON) $won++;
            if (in_array((int)$row['status'], [IntentModel::STATUS_DRAFT, IntentModel::STATUS_TO_QUOTE, IntentModel::STATUS_QUOTED, IntentModel::STATUS_HELD], true)) $openIntent++;
        }
        $orderAmount = 0;
        $unpaidAmount = 0;
        foreach ($orders as $row) {
            if ((int)$row['status'] === 6) continue;
            $orderAmount += (float)$row['final_amount'];
            $unpaidAmount += (float)($row['unpaid_amount'] ?? 0);
        }

        // 客户级但未归属项目的历史业务：不硬塞进项目，提示用户去客户详情看
        $unlinked = [
            'quotations' => Db::name('crm_quotation')->where('store_id', $storeId)
                ->where('customer_id', $project['customer_id'])->where('project_id', 0)->where('is_delete', 0)->count(),
            'orders' => Db::name('crm_order')->where('store_id', $storeId)
                ->where('customer_id', $project['customer_id'])->where('project_id', 0)->where('is_delete', 0)->count(),
            'contracts' => Db::name('crm_contract')->where('store_id', $storeId)
                ->where('customer_id', $project['customer_id'])->where('project_id', 0)->where('is_delete', 0)->count(),
            'order_amount' => (float)Db::name('crm_order')->where('store_id', $storeId)
                ->where('customer_id', $project['customer_id'])->where('project_id', 0)
                ->where('is_delete', 0)->where('status', '<>', 6)->sum('final_amount'),
        ];

        return ['success' => true, 'data' => [
            'project' => $project,
            'intents' => $intents,
            'commitments' => ['quotations' => $quotations, 'orders' => $orders, 'contracts' => $contracts],
            'deliveries' => $tickets,
            'payments' => $payments,
            'followups' => $followups,
            'participants' => $participants,
            'unlinked' => $unlinked,
            'timeline' => $timeline,
            'stats' => [
                'intent_total' => count($intents),
                'intent_won' => $won,
                'intent_open' => $openIntent,
                'order_amount' => round($orderAmount, 2),
                'unpaid_amount' => round($unpaidAmount, 2),
                'received_amount' => round($receivedAmount, 2),
                'amount_total' => round($orderAmount, 2),
                'delivery_total' => count($tickets),
                'delivery_done' => count(array_filter($tickets, fn($t) => (int)$t['status'] === 4)),
            ],
        ]];
    }

    public function add(array $data, int $storeId, int $actorId): array
    {
        $this->bindStore($storeId);
        $model = new ProjectModel();
        if (!$model->add($data)) {
            return ['success' => false, 'error' => $model->getError() ?: '创建失败'];
        }
        EventService::record('project_created', 'project', (int)$model->id, [
            'project_no' => $model->project_no,
            'customer_id' => (int)$data['customer_id'],
        ], $actorId, (int)$model->id);

        return ['success' => true, 'data' => ['id' => (int)$model->id, 'project_no' => $model->project_no]];
    }

    public function edit(int $id, array $data, int $storeId, int $actorId): array
    {
        $this->bindStore($storeId);
        if (!(new ParticipantService())->canEdit($id, $actorId, $storeId)) {
            return ['success' => false, 'error' => '无权编辑该项目'];
        }
        $model = new ProjectModel();
        if (!$model->edit($id, $data)) {
            return ['success' => false, 'error' => $model->getError() ?: '保存失败'];
        }
        EventService::record('project_updated', 'project', $id, ['fields' => array_keys($data)], $actorId, $id);
        return ['success' => true, 'data' => null];
    }

    public function cancel(int $id, string $reason, int $storeId, int $actorId): array
    {
        $this->bindStore($storeId);
        if (!$this->canManage($id, $actorId, $storeId)) {
            return ['success' => false, 'error' => '只有项目负责人可以取消项目'];
        }
        if (trim($reason) === '') return ['success' => false, 'error' => '请填写取消原因'];

        Db::startTrans();
        try {
            $model = new ProjectModel();
            if (!$model->setStatus($id, ProjectModel::STATUS_CANCELLED)) {
                Db::rollback();
                return ['success' => false, 'error' => $model->getError() ?: '操作失败'];
            }

            // 未完成需求：关闭
            $intents = Db::name('crm_intent')
                ->where('store_id', $storeId)->where('project_id', $id)->where('is_delete', 0)
                ->whereNotIn('status', [IntentModel::STATUS_WON, IntentModel::STATUS_CLOSED])
                ->column('id');
            foreach ($intents as $intentId) {
                Db::name('crm_intent')->where('store_id', $storeId)->where('id', $intentId)->update([
                    'status' => IntentModel::STATUS_CLOSED,
                    'closed_reason' => '项目取消：' . $reason,
                    'update_time' => time(),
                ]);
                EventService::record('intent_closed', 'intent', (int)$intentId, ['reason' => $reason], $actorId, $id);
            }

            // 未完成交付：取消（走工单服务，保留时间线）
            $tickets = Db::name('workorder_ticket')
                ->where('store_id', $storeId)->where('project_id', $id)->where('is_delete', 0)
                ->whereIn('status', [1, 2, 3])->select()->toArray();
            $actor = $this->actor($actorId, $storeId);
            foreach ($tickets as $ticket) {
                try {
                    \skills\workorder\service\TicketService::forStore($storeId)
                        ->changeStatus((int)$ticket['id'], 5, '项目取消：' . $reason, $actor);
                } catch (\Throwable $e) {
                    // 单张工单失败不阻断项目取消，但保留日志
                    \think\facade\Log::error('[项目取消] 工单取消失败 ' . $ticket['id'] . ': ' . $e->getMessage());
                }
            }

            EventService::record('project_cancelled', 'project', $id, ['reason' => $reason], $actorId, $id);
            Db::commit();
            return ['success' => true, 'data' => null];
        } catch (\Throwable $e) {
            Db::rollback();
            return ['success' => false, 'error' => '取消失败：' . $e->getMessage()];
        }
    }

    /** 开始 / 完成 / 重新打开（手动状态操作） */
    /** AI 项目摘要：现状 + 风险 + 下一步，只读项目内数据 */
    public function aiSummary(int $id, int $storeId): array
    {
        $this->bindStore($storeId);
        $project = (new ProjectModel())->detail($id);
        if (!$project) return ['success' => false, 'error' => '项目不存在'];
        if (!\app\platform\ai\AiConfig::isConfigured()) {
            return ['success' => false, 'error' => '还没有配置 AI，请到「设置 → AI配置」填写 Key'];
        }

        $intents = Db::name('crm_intent')->where('store_id', $storeId)->where('project_id', $id)->where('is_delete', 0)
            ->field('title,status,quantity,unit')->select()->toArray();
        $quotes = Db::name('crm_quotation')->where('store_id', $storeId)->where('project_id', $id)->where('is_delete', 0)
            ->field('quotation_no,status,final_amount')->select()->toArray();
        $orders = Db::name('crm_order')->where('store_id', $storeId)->where('project_id', $id)->where('is_delete', 0)
            ->field('order_no,status,final_amount,paid_amount,unpaid_amount,service_start_time,service_end_time')->select()->toArray();
        $tickets = Db::name('workorder_ticket')->where('store_id', $storeId)->where('project_id', $id)->where('is_delete', 0)
            ->field('ticket_no,kind,period_no,status,expect_date')->select()->toArray();
        $followups = Db::name('crm_followup')->where('store_id', $storeId)->where('project_id', $id)->where('is_delete', 0)
            ->order(['follow_date' => 'desc'])->limit(10)
            ->field('follow_type,result,follow_content,next_follow_date,next_follow_content,follow_date')
            ->select()->toArray();

        foreach ($followups as &$f) {
            $f['follow_content'] = mb_substr(trim(preg_replace('/\s+/u', ' ', strip_tags((string)$f['follow_content']))), 0, 100);
        }
        unset($f);

        $context = [
            '项目' => [
                '名称' => $project['title'],
                '状态' => ProjectModel::statusMap()[(int)$project['status']] ?? '',
                '客户' => $project['customer_name'] ?? '',
                '服务期' => $project['service_start_time'] ? date('Y-m-d', (int)$project['service_start_time']) . ' ~ ' . date('Y-m-d', (int)$project['service_end_time']) : '未设置',
            ],
            '需求' => $intents,
            '报价' => $quotes,
            '订单' => $orders,
            '交付' => $tickets,
            '最近沟通' => $followups,
        ];

        $system = '你是 CRM 项目助理。只根据给定的项目数据回答，不要编造数据，不要复述字段名。用中文输出 3-5 句话：1）当前进展；2）主要风险或卡点；3）下一步建议（要具体、可执行）。';
        $user = "项目数据（JSON）：
" . json_encode($context, JSON_UNESCAPED_UNICODE);
        $result = \app\platform\ai\AiGateway::request($system, $user, ['temperature' => 0.3, 'max_tokens' => 500], $storeId);
        if (!$result->success) return ['success' => false, 'error' => 'AI 生成失败：' . $result->error];

        return ['success' => true, 'data' => ['summary' => trim((string)$result->content)]];
    }

    public function changeProjectStatus(int $id, string $action, int $storeId, int $actorId): array
    {
        $this->bindStore($storeId);
        if (!$this->canManage($id, $actorId, $storeId)) {
            return ['success' => false, 'error' => '只有项目负责人可以操作'];
        }
        $model = new ProjectModel();
        $before = $model->detail($id);
        if (!$before) return ['success' => false, 'error' => '项目不存在'];

        // 全检硬约束：未通过审核不得完成
        if ($action === 'complete' && !$this->isReviewPassed($id, $storeId)) {
            return ['success' => false, 'error' => '项目尚未通过审核，请先送审'];
        }

        $ok = false;
        if ($action === 'start') $ok = $model->start($id);
        elseif ($action === 'complete') $ok = $model->complete($id);
        elseif ($action === 'reopen') $ok = $model->reopen($id);
        else return ['success' => false, 'error' => '未知操作'];

        if (!$ok) return ['success' => false, 'error' => $model->getError() ?: '操作失败'];

        EventService::record('project_status_changed', 'project', $id, [
            'from' => (int)$before['status'],
            'to' => (int)$model->where('id', $id)->value('status'),
            'manual' => true,
            'action' => $action,
        ], $actorId, $id);
        return ['success' => true, 'data' => null];
    }

    public function archive(int $id, int $storeId, int $actorId): array
    {
        $this->bindStore($storeId);
        if (!$this->canManage($id, $actorId, $storeId)) {
            return ['success' => false, 'error' => '只有项目负责人可以归档'];
        }
        $model = new ProjectModel();
        if (!$model->archive($id)) {
            return ['success' => false, 'error' => $model->getError() ?: '操作失败'];
        }
        EventService::record('project_archived', 'project', $id, [], $actorId, $id);
        return ['success' => true, 'data' => null];
    }

    /**
     * 由子对象推导项目状态（潜在/进行中/已完成/已取消）
     */
    public function syncStatus(int $id, int $storeId): void
    {
        $this->bindStore($storeId);
        $project = Db::name('crm_project')
            ->where('store_id', $storeId)->where('id', $id)->where('is_delete', 0)->find();
        if (!$project) return;
        if ((int)$project['status'] === ProjectModel::STATUS_CANCELLED) return;

        $intentStatuses = Db::name('crm_intent')
            ->where('store_id', $storeId)->where('project_id', $id)->where('is_delete', 0)
            ->column('status');
        $orderStatuses = Db::name('crm_order')
            ->where('store_id', $storeId)->where('project_id', $id)->where('is_delete', 0)
            ->column('status');
        $ticketStatuses = Db::name('workorder_ticket')
            ->where('store_id', $storeId)->where('project_id', $id)->where('is_delete', 0)
            ->column('status');

        $hasWon = in_array(IntentModel::STATUS_WON, array_map('intval', $intentStatuses), true)
            || !empty(array_intersect(array_map('intval', $orderStatuses), [2, 3, 4, 5]));

        $active = !empty(array_intersect(array_map('intval', $intentStatuses), [2, 3]))
            || !empty(array_intersect(array_map('intval', $orderStatuses), [2, 3, 4]))
            || !empty(array_intersect(array_map('intval', $ticketStatuses), [2, 3]));

        $intentsEnded = empty(array_diff(array_map('intval', $intentStatuses), [IntentModel::STATUS_WON, IntentModel::STATUS_CLOSED]));
        $ordersEnded = empty(array_diff(array_map('intval', $orderStatuses), [5, 6]));
        $ticketsEnded = empty(array_diff(array_map('intval', $ticketStatuses), [4, 5]));

        // 成交了但还没有任何订单/交付 → 仍在履约中，不能算完成
        $hasExecution = !empty($orderStatuses) || !empty($ticketStatuses);

        if ($hasWon && $intentsEnded && $ordersEnded && $ticketsEnded && $hasExecution) {
            $status = ProjectModel::STATUS_DONE;
        } elseif ($hasWon || $active) {
            $status = ProjectModel::STATUS_ACTIVE;
        } else {
            $status = ProjectModel::STATUS_POTENTIAL;
        }

        // 自动推导出的「完成」同样必须先过审，否则全检形同虚设
        if ($status === ProjectModel::STATUS_DONE && !$this->isReviewPassed($id, $storeId)) {
            $status = ProjectModel::STATUS_ACTIVE;
        }

        if ($status !== (int)$project['status']) {
            Db::name('crm_project')->where('store_id', $storeId)->where('id', $id)->update([
                'status' => $status,
                'started_at' => $status === ProjectModel::STATUS_ACTIVE && empty($project['started_at']) ? time() : $project['started_at'],
                'completed_at' => $status === ProjectModel::STATUS_DONE ? time() : null,
                'update_time' => time(),
            ]);
            EventService::record('project_status_changed', 'project', $id, [
                'from' => (int)$project['status'], 'to' => $status,
            ], 0, $id);
        }
    }

    /** 列表补充需求数与在途金额（一次分组查询，避免 N+1） */
    private function enrichList($list, int $storeId): array
    {
        $page = $list->toArray();
        $rows = $page['data'] ?? [];
        if (empty($rows)) return $page;

        $ids = array_map('intval', array_column($rows, 'id'));

        $intentCounts = [];
        $rowsCount = Db::name('crm_intent')
            ->where('store_id', $storeId)->whereIn('project_id', $ids)->where('is_delete', 0)
            ->field('project_id, COUNT(*) AS cnt')->group('project_id')->select()->toArray();
        foreach ($rowsCount as $r) $intentCounts[(int)$r['project_id']] = (int)$r['cnt'];

        $amounts = [];
        $rowsAmount = Db::name('crm_order')
            ->where('store_id', $storeId)->whereIn('project_id', $ids)->where('is_delete', 0)
            ->where('status', '<>', 6)
            ->field('project_id, SUM(final_amount) AS amount')->group('project_id')->select()->toArray();
        foreach ($rowsAmount as $r) $amounts[(int)$r['project_id']] = (float)$r['amount'];

        foreach ($page['data'] as &$row) {
            $pid = (int)$row['id'];
            $row['intent_count'] = $intentCounts[$pid] ?? 0;
            $row['amount_total'] = round($amounts[$pid] ?? 0, 2);
        }
        unset($row);

        return $page;
    }

    private function stats(int $storeId, int $viewerId): array
    {
        $participant = new ParticipantService();
        $visible = $participant->visibleProjectIds($viewerId, $storeId);
        if ($visible !== null && empty($visible)) {
            $all = 0;
        } else {
            $query = Db::name('crm_project')->where('store_id', $storeId)
                ->where('is_delete', 0)->whereNull('archived_at');
            if ($visible !== null) $query->whereIn('id', $visible);
            $all = $query->count();
        }

        $mine = Db::name('crm_project')->where('store_id', $storeId)->where('is_delete', 0)->whereNull('archived_at')
            ->where(function ($q) use ($viewerId) {
                $q->where('owner_user_id', $viewerId)->whereOr('sales_user_id', $viewerId);
            })->count();

        $joined = Db::name('crm_project_participant')->where('store_id', $storeId)
            ->where('user_id', $viewerId)->where('status', 1)->count();

        $archived = Db::name('crm_project')->where('store_id', $storeId)->where('is_delete', 0)
            ->whereNotNull('archived_at')->count();

        return ['all' => (int)$all, 'mine' => (int)$mine, 'joined' => (int)$joined, 'archived' => (int)$archived];
    }

    private function canManage(int $projectId, int $actorId, int $storeId): bool
    {
        $project = Db::name('crm_project')
            ->where('store_id', $storeId)->where('id', $projectId)->where('is_delete', 0)->find();
        if (!$project) return false;
        $isSuper = (int)Db::name('store_user')
            ->where('store_id', $storeId)->where('store_user_id', $actorId)->value('is_super') === 1;
        if ($isSuper) return true;
        return in_array($actorId, [(int)$project['owner_user_id'], (int)$project['sales_user_id']], true);
    }

    private function actor(int $actorId, int $storeId): array
    {
        $user = Db::name('store_user')
            ->where('store_id', $storeId)->where('store_user_id', $actorId)
            ->field(['real_name', 'user_name'])->find();
        return ['type' => 2, 'id' => $actorId, 'name' => $user ? (string)($user['real_name'] ?: $user['user_name']) : ''];
    }

    /** 合并事件与拜访为项目时间线 */
    private function timeline(int $projectId, int $storeId): array
    {
        // 沟通记录本身单独合并，不再重复展示 follow_up_completed 事件
        $events = Db::name('crm_event_log')->alias('e')
            ->leftJoin('store_user u', 'u.store_user_id = e.actor_user_id AND u.store_id = e.store_id')
            ->where('e.store_id', $storeId)->where('e.project_id', $projectId)
            ->where('e.event_type', '<>', 'follow_up_completed')
            ->field(['e.id', 'e.event_type', 'e.target_type', 'e.target_id', 'e.actor_user_id',
                     'e.data', 'e.create_time', 'u.real_name', 'u.user_name'])
            ->order(['e.id' => 'desc'])->limit(50)->select()->toArray();

        $followups = Db::name('crm_followup')->alias('f')
            ->leftJoin('crm_intent n', 'n.id = f.intent_id AND n.store_id = f.store_id')
            ->leftJoin('store_user u', 'u.store_user_id = f.owner_user_id AND u.store_id = f.store_id')
            ->where('f.store_id', $storeId)->where('f.project_id', $projectId)->where('f.is_delete', 0)
            ->field(['f.id', 'f.follow_type', 'f.result', 'f.follow_content', 'f.owner_user_id', 'f.follow_date',
                     'f.next_follow_date', 'f.next_follow_content',
                     'f.intent_id', 'n.intent_no', 'n.title AS intent_title',
                     'u.real_name', 'u.user_name'])
            ->order(['f.id' => 'desc'])->limit(50)->select()->toArray();

        $items = [];
        foreach ($events as $row) {
            $items[] = ['kind' => 'event', 'time' => (int)$row['create_time'], 'data' => $row];
        }
        foreach ($followups as $row) {
            $items[] = ['kind' => 'followup', 'time' => (int)$row['follow_date'], 'data' => $row];
        }
        usort($items, fn($a, $b) => $b['time'] <=> $a['time']);
        return array_slice($items, 0, 50);
    }


    // ------------------------------------------------------------------
    // 审核（全检 · 指派制 D11）
    // ------------------------------------------------------------------

    /** 审核必检开关（配置 review_required，默认 1） */
    public function reviewRequired(int $storeId): bool
    {
        $value = Db::name('config')->where('config_type', 'crm_setting')
            ->where('config_name', 'review_required')
            ->where(function ($q) use ($storeId) {
                $q->where('store_id', $storeId)->whereOr('store_id', 0);
            })
            ->order(['store_id' => 'desc'])
            ->value('config_value');
        if ($value === null || $value === '') return true;
        return (string)$value === '1';
    }

    /** 审核是否已通过（关闭全检时恒为 true） */
    public function isReviewPassed(int $id, int $storeId): bool
    {
        if (!$this->reviewRequired($storeId)) return true;
        $status = Db::name('crm_project')->where('store_id', $storeId)->where('id', $id)->value('review_status');
        return (int)$status === 2;
    }

    /** 送审：进行中 → 待审核（每轮一条记录，round_no 递增） */
    public function submitReview(int $id, int $storeId, int $actorId): array
    {
        $project = Db::name('crm_project')->where('store_id', $storeId)->where('id', $id)->where('is_delete', 0)->find();
        if (!$project) return ['success' => false, 'error' => '项目不存在'];
        if ((int)$project['status'] !== ProjectModel::STATUS_ACTIVE) return ['success' => false, 'error' => '只有进行中的项目可以送审'];
        if ((int)$project['review_status'] === 1) return ['success' => false, 'error' => '项目已在待审核'];

        $round = (int)Db::name('crm_project_review')->where('store_id', $storeId)
            ->where('project_id', $id)->max('round_no') + 1;
        $now = time();
        Db::startTrans();
        try {
            $reviewId = Db::name('crm_project_review')->insertGetId([
                'store_id' => $storeId, 'project_id' => $id, 'workorder_id' => 0,
                'customer_id' => (int)$project['customer_id'],
                'submit_user_id' => $actorId, 'submit_time' => $now,
                'reviewer_user_id' => 0, 'assign_user_id' => 0, 'assign_time' => 0,
                'review_time' => 0, 'result' => ProjectReviewModel::RESULT_PENDING, 'reason' => '',
                'round_no' => $round, 'create_time' => $now, 'update_time' => $now,
            ]);
            Db::name('crm_project')->where('store_id', $storeId)->where('id', $id)->update([
                'review_status' => 1, 'auditor_user_id' => 0, 'update_time' => $now,
            ]);
            EventService::record('project_review_submitted', 'project', $id, [
                'review_id' => $reviewId, 'round' => $round,
            ], $actorId, $id);
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            return ['success' => false, 'error' => '送审失败：' . $e->getMessage()];
        }
        return ['success' => true, 'data' => ['review_id' => $reviewId, 'round_no' => $round]];
    }

    /** 指派审核人（组长）；送审人不能审自己 */
    public function assignReviewer(int $projectId, int $reviewerId, int $storeId, int $actorId): array
    {
        $project = Db::name('crm_project')->where('store_id', $storeId)->where('id', $projectId)->where('is_delete', 0)->find();
        if (!$project) return ['success' => false, 'error' => '项目不存在'];
        if ((int)$project['review_status'] !== 1) return ['success' => false, 'error' => '项目不在待审核状态'];
        if ($reviewerId <= 0) return ['success' => false, 'error' => '请选择审核人'];

        $review = Db::name('crm_project_review')->where('store_id', $storeId)
            ->where('project_id', $projectId)->where('result', ProjectReviewModel::RESULT_PENDING)
            ->order(['id' => 'desc'])->find();
        if (!$review) return ['success' => false, 'error' => '没有待审记录'];
        if ((int)$review['submit_user_id'] === $reviewerId) {
            return ['success' => false, 'error' => '送审人不能审核自己的项目'];
        }

        $user = Db::name('store_user')->where('store_id', $storeId)
            ->where('store_user_id', $reviewerId)->where('is_delete', 0)->find();
        if (!$user) return ['success' => false, 'error' => '审核人不存在'];

        $now = time();
        Db::startTrans();
        try {
            Db::name('crm_project_review')->where('store_id', $storeId)->where('id', (int)$review['id'])->update([
                'reviewer_user_id' => $reviewerId, 'assign_user_id' => $actorId,
                'assign_time' => $now, 'update_time' => $now,
            ]);
            Db::name('crm_project')->where('store_id', $storeId)->where('id', $projectId)->update([
                'auditor_user_id' => $reviewerId, 'update_time' => $now,
            ]);
            EventService::record('project_review_assigned', 'project', $projectId, [
                'review_id' => (int)$review['id'], 'reviewer' => $reviewerId,
            ], $actorId, $projectId);
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            return ['success' => false, 'error' => '指派失败：' . $e->getMessage()];
        }
        return ['success' => true, 'data' => null];
    }

    /** 审核：通过 / 退回。只有被指派人可操作，送审人除外 */
    public function review(int $reviewId, bool $pass, string $reason, int $storeId, int $actorId): array
    {
        $review = Db::name('crm_project_review')->where('store_id', $storeId)->where('id', $reviewId)->find();
        if (!$review) return ['success' => false, 'error' => '审核记录不存在'];
        if ((int)$review['result'] !== ProjectReviewModel::RESULT_PENDING) return ['success' => false, 'error' => '该轮次已审核'];
        if ((int)$review['submit_user_id'] === $actorId) return ['success' => false, 'error' => '送审人不能审核自己的项目'];
        if (!$pass && trim($reason) === '') return ['success' => false, 'error' => '退回必须填写原因'];

        $project = Db::name('crm_project')->where('store_id', $storeId)
            ->where('id', (int)$review['project_id'])->where('is_delete', 0)->find();
        if (!$project) return ['success' => false, 'error' => '项目不存在'];

        $isSuper = (int)Db::name('store_user')->where('store_id', $storeId)
            ->where('store_user_id', $actorId)->value('is_super') === 1;
        if ((int)$project['auditor_user_id'] !== $actorId && !$isSuper) {
            return ['success' => false, 'error' => '只有被指派的审核人可以操作'];
        }

        $now = time();
        Db::startTrans();
        try {
            Db::name('crm_project_review')->where('store_id', $storeId)->where('id', $reviewId)->update([
                'reviewer_user_id' => (int)$review['reviewer_user_id'] ?: $actorId,
                'review_time' => $now,
                'result' => $pass ? ProjectReviewModel::RESULT_PASS : ProjectReviewModel::RESULT_REJECT,
                'reason' => $pass ? '' : $reason,
                'update_time' => $now,
            ]);
            Db::name('crm_project')->where('store_id', $storeId)->where('id', (int)$review['project_id'])->update([
                'review_status' => $pass ? 2 : 3,
                'update_time' => $now,
            ]);
            EventService::record($pass ? 'project_review_passed' : 'project_review_rejected', 'project', (int)$review['project_id'], [
                'review_id' => $reviewId, 'round' => (int)$review['round_no'], 'reason' => $pass ? '' : $reason,
            ], $actorId, (int)$review['project_id']);
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            return ['success' => false, 'error' => '审核失败：' . $e->getMessage()];
        }
        return ['success' => true, 'data' => null];
    }

    /** 待审队列：审核组可见全部；未指派也能看，但不能操作 */
    public function getReviewList(array $params, int $storeId): array
    {
        $result = (isset($params['result']) && $params['result'] !== '') ? (int)$params['result'] : 0;
        $query = Db::name('crm_project_review')->where('store_id', $storeId)->where('result', $result);
        if (!empty($params['project_id'])) $query->where('project_id', (int)$params['project_id']);

        $page = max(1, (int)($params['page'] ?? 1));
        $size = min(100, max(10, (int)($params['list_rows'] ?? 20)));
        $total = (clone $query)->count();
        $list = $query->order(['id' => 'desc'])->page($page, $size)->select()->toArray();

        $projectIds = array_column($list, 'project_id');
        $projects = empty($projectIds) ? [] : Db::name('crm_project')->where('store_id', $storeId)
            ->whereIn('id', $projectIds)->column('project_no,title,owner_user_id,auditor_user_id,customer_id,status,review_status', 'id');
        $userIds = array_unique(array_merge(array_column($list, 'submit_user_id'), array_column($list, 'reviewer_user_id')));
        $users = empty($userIds) ? [] : Db::name('store_user')->where('store_id', $storeId)
            ->whereIn('store_user_id', $userIds)->column('user_name,real_name', 'store_user_id');

        foreach ($list as &$row) {
            $p = isset($projects[(int)$row['project_id']]) ? $projects[(int)$row['project_id']] : [];
            $row['project_no'] = isset($p['project_no']) ? $p['project_no'] : '';
            $row['title'] = isset($p['title']) ? $p['title'] : '';
            $row['project_status'] = isset($p['status']) ? (int)$p['status'] : 0;
            $row['submit_user_name'] = $this->nameOf($users, (int)$row['submit_user_id']);
            $row['reviewer_user_name'] = $this->nameOf($users, (int)$row['reviewer_user_id']);
            $row['waiting_seconds'] = (int)$row['review_time'] > 0
                ? max(0, (int)$row['review_time'] - (int)$row['submit_time'])
                : max(0, time() - (int)$row['submit_time']);
        }
        unset($row);
        return ['success' => true, 'data' => ['list' => $list, 'total' => $total, 'page' => $page, 'list_rows' => $size]];
    }

    /** 项目的审核历史 */
    public function getReviewLog(int $projectId, int $storeId): array
    {
        $list = Db::name('crm_project_review')->where('store_id', $storeId)
            ->where('project_id', $projectId)->order(['round_no' => 'desc'])->select()->toArray();
        return ['success' => true, 'data' => ['list' => $list]];
    }

    private function nameOf(array $users, int $userId): string
    {
        if ($userId <= 0 || !isset($users[$userId])) return '';
        $u = $users[$userId];
        return (string)(($u['real_name'] ?? '') ?: ($u['user_name'] ?? ''));
    }

    private function bindStore(int $storeId): void
    {
        ProjectModel::$storeId = $storeId;
        IntentModel::$storeId = $storeId;
    }
}
