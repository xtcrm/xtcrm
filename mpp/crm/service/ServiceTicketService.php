<?php

namespace mpp\crm\service;

use app\platform\BaseService;
use app\platform\auth\TokenService;
use app\platform\flow\FlowRegistry;
use app\platform\flow\service\InstanceService;
use app\platform\flow\service\TemplateService;
use think\facade\Db;

/**
 * 客服单（试点）
 *
 * 数据范围（2026-11 定）：
 *  - 超管：全部
 *  - 部门经理：本部门及子部门处理人的单 + 自己创建的单 + 自己可见客户的单
 *  - 其他人：指派给我的 + 我创建的 + 我可见客户的单（销售可看自己客户的客服单，只读）
 *  - 未指派单：管理员/经理/客户可见人可见；操作仍限管理员、经理、创建人/处理人
 */
class ServiceTicketService extends BaseService
{
    const STATUS_LABELS = [1 => '新建', 2 => '处理中', 3 => '待确认', 4 => '已解决', 5 => '已关闭'];
    const CATEGORY_LABELS = ['consult' => '咨询', 'complaint' => '投诉', 'suggestion' => '建议', 'visit' => '回访'];
    const CHANNEL_LABELS = ['phone' => '电话', 'wechat' => '微信', 'email' => '邮件', 'visit' => '上门', 'other' => '其他'];
    const PRIORITY_LABELS = [1 => '低', 2 => '中', 3 => '高', 4 => '紧急'];

    /** @var array 单次请求内的用户/范围缓存 */
    private $user = null;
    private $managedDeptCache = [];
    private $visibleCustomerCache = [];

    /** 列表 */
    public function getList(array $params): array
    {
        $query = Db::name('crm_service_ticket')->alias('t')
            ->leftJoin('crm_customer c', 'c.id = t.customer_id AND c.store_id = t.store_id')
            ->leftJoin('store_user u', 'u.store_user_id = t.assignee_user_id AND u.store_id = t.store_id')
            ->where('t.store_id', $this->storeId)
            ->where('t.is_delete', 0);

        if (!empty($params['customer_id'])) $query->where('t.customer_id', (int)$params['customer_id']);
        if (!empty($params['status']))      $query->where('t.status', (int)$params['status']);
        if (!empty($params['category']))    $query->where('t.category', (string)$params['category']);
        if (!empty($params['channel']))     $query->where('t.channel', (string)$params['channel']);
        if (!empty($params['priority']))    $query->where('t.priority', (int)$params['priority']);
        if (!empty($params['assignee_user_id'])) {
            $query->where('t.assignee_user_id', (int)$params['assignee_user_id']);
        }
        if (!empty($params['keyword'])) {
            $kw = (string)$params['keyword'];
            $query->where(function ($q) use ($kw) {
                $q->where('t.ticket_no', 'like', "%{$kw}%")
                  ->whereOr('t.title', 'like', "%{$kw}%")
                  ->whereOr('t.content', 'like', "%{$kw}%")
                  ->whereOr('c.customer_name', 'like', "%{$kw}%");
            });
        }

        $user = $this->currentUser();
        $this->applyScope($query, $user);

        $page = max(1, (int)($params['page'] ?? 1));
        $size = min(100, max(1, (int)($params['limit'] ?? 20)));
        $total = (clone $query)->count();
        $rows = $query
            ->field('t.*, c.customer_name, u.real_name, u.user_name')
            ->order(['t.id' => 'desc'])
            ->page($page, $size)
            ->select()->toArray();

        foreach ($rows as &$row) {
            $this->decorate($row);
            $row['can_operate'] = $this->canOperate($row, $user) ? 1 : 0;
            $row['can_delete']  = $this->canDelete($row, $user) ? 1 : 0;
        }
        unset($row);

        return ['list' => $rows, 'total' => (int)$total, 'page' => $page, 'page_size' => $size];
    }

    /** 详情（含客户资料、联系人、处理记录） */
    public function detail(int $id): array
    {
        $row = Db::name('crm_service_ticket')->alias('t')
            ->leftJoin('crm_customer c', 'c.id = t.customer_id AND c.store_id = t.store_id')
            ->leftJoin('crm_contact ct', 'ct.id = t.contact_id AND ct.store_id = t.store_id')
            ->leftJoin('store_user u', 'u.store_user_id = t.assignee_user_id AND u.store_id = t.store_id')
            ->leftJoin('store_user cu', 'cu.store_user_id = c.owner_user_id AND cu.store_id = c.store_id')
            ->where('t.store_id', $this->storeId)
            ->where('t.id', $id)
            ->where('t.is_delete', 0)
            ->field('t.*, c.customer_name, c.customer_code, c.short_name, c.industry, c.level_name,
                     c.customer_group, c.source, c.telephone, c.province, c.city, c.area, c.address,
                     ct.contact_name, ct.mobile AS contact_mobile,
                     u.real_name, u.user_name,
                     cu.real_name AS customer_owner_real_name, cu.user_name AS customer_owner_user_name')
            ->find();
        if (!$row) return ['success' => false, 'data' => null, 'error' => '客服单不存在'];

        $user = $this->currentUser();
        if (!$this->canView($row, $user)) {
            // 不在数据范围内时不暴露单据存在性
            return ['success' => false, 'data' => null, 'error' => '客服单不存在'];
        }

        $this->decorate($row);
        $row['can_operate'] = $this->canOperate($row, $user) ? 1 : 0;
        $row['can_delete']  = $this->canDelete($row, $user) ? 1 : 0;

        // 客户底账（详情页顶部展示，不跳转也能看清是谁）
        $row['customer'] = [
            'id'           => (int)$row['customer_id'],
            'name'         => (string)($row['customer_name'] ?? ''),
            'code'         => (string)($row['customer_code'] ?? ''),
            'short_name'   => (string)($row['short_name'] ?? ''),
            'industry'     => (string)($row['industry'] ?? ''),
            'level_name'   => (string)($row['level_name'] ?? ''),
            'group'        => (string)($row['customer_group'] ?? ''),
            'source'       => (string)($row['source'] ?? ''),
            'telephone'    => (string)($row['telephone'] ?? ''),
            'region'       => implode(' / ', array_filter([$row['province'] ?? '', $row['city'] ?? '', $row['area'] ?? ''])),
            'address'      => (string)($row['address'] ?? ''),
            'owner_name'   => (string)(($row['customer_owner_real_name'] ?? '') ?: ($row['customer_owner_user_name'] ?? '')),
            'contact_name' => (string)($row['contact_name'] ?? ''),
            'contact_mobile' => (string)($row['contact_mobile'] ?? ''),
        ];

        // 可选流程信息
        $row['flow'] = $this->flowInfo($id);

        // 处理记录（触点：建单/指派/状态/跟进/评价/流程）
        $row['logs'] = TouchpointService::getBySource($this->storeId, 'service_ticket', $id, 100);

        return ['success' => true, 'data' => ['detail' => $row], 'error' => ''];
    }

    /** 新建 */
    public function add(array $data, int $actorUserId): array
    {
        $customerId = (int)($data['customer_id'] ?? 0);
        if ($customerId <= 0) return ['success' => false, 'data' => null, 'error' => '请选择客户'];
        $title = trim((string)($data['title'] ?? ''));
        if ($title === '') return ['success' => false, 'data' => null, 'error' => '请填写客服单标题'];

        $customer = Db::name('crm_customer')
            ->where('store_id', $this->storeId)->where('id', $customerId)->where('is_delete', 0)->find();
        if (!$customer) return ['success' => false, 'data' => null, 'error' => '客户不存在'];

        $assigneeId = (int)($data['assignee_user_id'] ?? 0);
        $deptId = 0;
        if ($assigneeId > 0) {
            $user = Db::name('store_user')->where('store_id', $this->storeId)
                ->where('store_user_id', $assigneeId)->where('is_delete', 0)->find();
            if (!$user) return ['success' => false, 'data' => null, 'error' => '处理人不存在'];
            if ((int)($user['is_supplier'] ?? 0) === 1) {
                return ['success' => false, 'data' => null, 'error' => '不能指派给供应商账号'];
            }
            $deptId = (int)($user['department_id'] ?? 0);
        }

        $now = time();
        $ticketNo = $this->generateNo();
        $id = (int)Db::name('crm_service_ticket')->insertGetId([
            'store_id'        => $this->storeId,
            'ticket_no'       => $ticketNo,
            'customer_id'     => $customerId,
            'contact_id'      => (int)($data['contact_id'] ?? 0),
            'channel'         => $this->pick($data['channel'] ?? '', self::CHANNEL_LABELS, 'other'),
            'category'        => $this->pick($data['category'] ?? '', self::CATEGORY_LABELS, 'consult'),
            'priority'        => min(4, max(1, (int)($data['priority'] ?? 2))),
            'title'           => mb_substr($title, 0, 200),
            'content'         => (string)($data['content'] ?? ''),
            'expect_at'       => (int)($data['expect_at'] ?? 0),
            'assignee_user_id' => $assigneeId,
            'dept_id'         => $deptId,
            'status'          => $assigneeId > 0 ? 2 : 1,
            'resolution'      => '',
            'root_cause'      => (string)($data['root_cause'] ?? ''),
            'csat'            => 0,
            'created_by'      => $actorUserId,
            'resolved_at'     => 0,
            'closed_at'       => 0,
            'is_delete'       => 0,
            'create_time'     => $now,
            'update_time'     => $now,
        ]);

        TouchpointService::record(
            $customerId,
            'service',
            'created',
            "新建客服单 {$ticketNo}",
            [
                'ticket_id'   => $id,
                'ticket_no'   => $ticketNo,
                'category'    => $data['category'] ?? 'consult',
                'priority'    => (int)($data['priority'] ?? 2),
                'assignee_id' => $assigneeId,
            ],
            $actorUserId,
            $this->storeId,
            [
                'source_type' => 'service_ticket',
                'source_id'   => $id,
                'action_url'  => '/crm/service/ticket?id=' . $id,
                'owner_user_id' => $assigneeId,
                'dept_id'     => $deptId,
                'occurred_at' => $now,
                'dedup_key'   => 'created',
            ]
        );

        return ['success' => true, 'data' => ['id' => $id, 'ticket_no' => $ticketNo], 'error' => ''];
    }

    /** 编辑 */
    public function edit(int $id, array $data, int $actorUserId): array
    {
        $ticket = $this->findTicket($id);
        if (!$ticket) return ['success' => false, 'data' => null, 'error' => '客服单不存在'];
        if (!$this->canOperate($ticket, $this->currentUser())) {
            return ['success' => false, 'data' => null, 'error' => '无权编辑该客服单'];
        }

        $update = ['update_time' => time()];
        if (array_key_exists('title', $data)) {
            $title = trim((string)$data['title']);
            if ($title === '') return ['success' => false, 'data' => null, 'error' => '标题不能为空'];
            $update['title'] = mb_substr($title, 0, 200);
        }
        if (array_key_exists('content', $data))      $update['content'] = (string)$data['content'];
        if (array_key_exists('channel', $data))      $update['channel'] = $this->pick($data['channel'], self::CHANNEL_LABELS, 'other');
        if (array_key_exists('category', $data))     $update['category'] = $this->pick($data['category'], self::CATEGORY_LABELS, 'consult');
        if (array_key_exists('priority', $data))     $update['priority'] = min(4, max(1, (int)$data['priority']));
        if (array_key_exists('expect_at', $data))    $update['expect_at'] = (int)$data['expect_at'];
        if (array_key_exists('root_cause', $data))   $update['root_cause'] = (string)$data['root_cause'];
        if (array_key_exists('resolution', $data))   $update['resolution'] = (string)$data['resolution'];

        $assigneeChanged = false;
        if (array_key_exists('assignee_user_id', $data)) {
            $newAssignee = (int)$data['assignee_user_id'];
            if ($newAssignee !== (int)$ticket['assignee_user_id']) {
                $lock = $this->lockCheck($ticket);
                if ($lock !== true) return ['success' => false, 'data' => null, 'error' => $lock];
                $deptId = 0;
                if ($newAssignee > 0) {
                    $user = Db::name('store_user')->where('store_id', $this->storeId)
                        ->where('store_user_id', $newAssignee)->where('is_delete', 0)->find();
                    if (!$user) return ['success' => false, 'data' => null, 'error' => '处理人不存在'];
                    if ((int)($user['is_supplier'] ?? 0) === 1) {
                        return ['success' => false, 'data' => null, 'error' => '不能指派给供应商账号'];
                    }
                    $deptId = (int)($user['department_id'] ?? 0);
                }
                $update['assignee_user_id'] = $newAssignee;
                $update['dept_id'] = $deptId;
                if ($newAssignee > 0 && (int)$ticket['status'] === 1) {
                    $update['status'] = 2;
                }
                $assigneeChanged = true;
            }
        }

        Db::name('crm_service_ticket')
            ->where('store_id', $this->storeId)->where('id', $id)
            ->update($update);

        if ($assigneeChanged) {
            $this->logAssigned($ticket, (int)$update['assignee_user_id'], $actorUserId);
        }
        $isResultUpdate = array_key_exists('resolution', $data) || array_key_exists('root_cause', $data);
        $logDetail = ['ticket_id' => $id, 'ticket_no' => $ticket['ticket_no']];
        if ($isResultUpdate) {
            $logDetail['content'] = (string)($data['resolution'] ?? ($ticket['resolution'] ?? ''));
            $logDetail['remark'] = mb_substr($logDetail['content'], 0, 200);
        }

        TouchpointService::record(
            (int)$ticket['customer_id'],
            'service',
            'updated',
            $isResultUpdate
                ? ('客服单 ' . $ticket['ticket_no'] . '：更新处理结果')
                : ('编辑客服单 ' . $ticket['ticket_no']),
            $logDetail,
            $actorUserId,
            $this->storeId,
            [
                'source_type' => 'service_ticket',
                'source_id'   => $id,
                'action_url'  => '/crm/service/ticket?id=' . $id,
                'dedup_key'   => $this->dedup('updated'),
            ]
        );

        return ['success' => true, 'data' => null, 'error' => ''];
    }

    /** 指派 */
    public function assign(int $id, int $assigneeId, int $actorUserId): array
    {
        $ticket = $this->findTicket($id);
        if (!$ticket) return ['success' => false, 'data' => null, 'error' => '客服单不存在'];
        if (!$this->canOperate($ticket, $this->currentUser())) {
            return ['success' => false, 'data' => null, 'error' => '无权指派该客服单'];
        }
        $lock = $this->lockCheck($ticket);
        if ($lock !== true) return ['success' => false, 'data' => null, 'error' => $lock];

        $deptId = 0;
        if ($assigneeId > 0) {
            $user = Db::name('store_user')->where('store_id', $this->storeId)
                ->where('store_user_id', $assigneeId)->where('is_delete', 0)->find();
            if (!$user) return ['success' => false, 'data' => null, 'error' => '处理人不存在'];
            if ((int)($user['is_supplier'] ?? 0) === 1) {
                return ['success' => false, 'data' => null, 'error' => '不能指派给供应商账号'];
            }
            $deptId = (int)($user['department_id'] ?? 0);
        }

        $update = [
            'assignee_user_id' => $assigneeId,
            'dept_id'          => $deptId,
            'update_time'      => time(),
        ];
        if ($assigneeId > 0 && (int)$ticket['status'] === 1) {
            $update['status'] = 2;
        }
        Db::name('crm_service_ticket')
            ->where('store_id', $this->storeId)->where('id', $id)
            ->update($update);

        if ($assigneeId > 0) {
            $this->logAssigned($ticket, $assigneeId, $actorUserId);
        } else {
            TouchpointService::record(
                (int)$ticket['customer_id'],
                'service',
                'assigned',
                '客服单 ' . $ticket['ticket_no'] . '：取消指派',
                ['ticket_id' => $id],
                $actorUserId,
                $this->storeId,
                [
                    'source_type' => 'service_ticket',
                    'source_id'   => $id,
                    'action_url'  => '/crm/service/ticket?id=' . $id,
                    'dedup_key'   => $this->dedup('assigned'),
                ]
            );
        }

        return ['success' => true, 'data' => null, 'error' => ''];
    }

    /** 状态变更 / 解决 / 评价 */
    public function changeStatus(int $id, int $status, array $data, int $actorUserId): array
    {
        if (!isset(self::STATUS_LABELS[$status])) {
            return ['success' => false, 'data' => null, 'error' => '不支持的状态'];
        }
        $ticket = $this->findTicket($id);
        if (!$ticket) return ['success' => false, 'data' => null, 'error' => '客服单不存在'];
        if (!$this->canOperate($ticket, $this->currentUser())) {
            return ['success' => false, 'data' => null, 'error' => '无权变更该客服单状态'];
        }
        $lock = $this->lockCheck($ticket);
        if ($lock !== true) return ['success' => false, 'data' => null, 'error' => $lock];

        $now = time();
        $update = ['status' => $status, 'update_time' => $now];
        if (array_key_exists('resolution', $data)) $update['resolution'] = (string)$data['resolution'];
        if (array_key_exists('root_cause', $data)) $update['root_cause'] = (string)$data['root_cause'];
        if ($status === 4 && empty($ticket['resolved_at'])) $update['resolved_at'] = $now;
        if ($status === 5 && empty($ticket['closed_at']))   $update['closed_at'] = $now;

        $csat = (int)($data['csat'] ?? 0);
        if ($csat > 0) $update['csat'] = min(5, $csat);

        Db::name('crm_service_ticket')
            ->where('store_id', $this->storeId)->where('id', $id)
            ->update($update);

        $fromStatus = (int)$ticket['status'];
        $fromLabel = self::STATUS_LABELS[$fromStatus] ?? '未知';
        $toLabel = self::STATUS_LABELS[$status];
        $resolutionText = trim((string)($data['resolution'] ?? ''));

        if ($status !== $fromStatus) {
            TouchpointService::record(
                (int)$ticket['customer_id'],
                'service',
                'status_changed',
                '客服单 ' . $ticket['ticket_no'] . "：{$fromLabel} → {$toLabel}",
                [
                    'ticket_id'   => $id,
                    'from_status' => $fromStatus,
                    'to_status'   => $status,
                    'resolution'  => $resolutionText,
                ],
                $actorUserId,
                $this->storeId,
                [
                    'source_type' => 'service_ticket',
                    'source_id'   => $id,
                    'action_url'  => '/crm/service/ticket?id=' . $id,
                    'occurred_at' => $now,
                    'dedup_key'   => $this->dedup('status'),
                ]
            );
        } elseif ($resolutionText !== '') {
            TouchpointService::record(
                (int)$ticket['customer_id'],
                'service',
                'updated',
                '客服单 ' . $ticket['ticket_no'] . '：更新处理结果',
                ['ticket_id' => $id, 'content' => $resolutionText, 'remark' => $resolutionText],
                $actorUserId,
                $this->storeId,
                [
                    'source_type' => 'service_ticket',
                    'source_id'   => $id,
                    'action_url'  => '/crm/service/ticket?id=' . $id,
                    'occurred_at' => $now,
                    'dedup_key'   => $this->dedup('resolution'),
                ]
            );
        }

        if ($csat > 0) {
            TouchpointService::record(
                (int)$ticket['customer_id'],
                'service',
                'rated',
                '客服单 ' . $ticket['ticket_no'] . "：满意度 {$csat} 分",
                ['ticket_id' => $id, 'csat' => $csat],
                $actorUserId,
                $this->storeId,
                [
                    'source_type' => 'service_ticket',
                    'source_id'   => $id,
                    'action_url'  => '/crm/service/ticket?id=' . $id,
                    'occurred_at' => $now,
                    'csat'        => $csat,
                    'dedup_key'   => $this->dedup('rated'),
                ]
            );
        }

        return ['success' => true, 'data' => ['status_changed' => $status !== $fromStatus], 'error' => ''];
    }

    /** 可选的流程模板（没有就自动生成系统预置模板） */
    public function flowTemplates(): array
    {
        FlowRegistry::ensureDefaultTemplate((int)$this->storeId, 'crm_service_ticket');

        $res = (new TemplateService((int)$this->storeId))->getList([
            'biz_type' => 'crm_service_ticket',
            'status'   => 'published',
        ], 1, 100);

        $list = [];
        foreach (($res['list'] ?? []) as $t) {
            $list[] = [
                'id'          => (int)$t['id'],
                'code'        => (string)$t['code'],
                'name'        => (string)$t['name'],
                'description' => (string)($t['description'] ?? ''),
            ];
        }
        return ['success' => true, 'data' => ['list' => $list], 'error' => ''];
    }

    /** 发起流程（可选动作） */
    public function startFlow(int $id, string $templateCode, int $actorUserId): array
    {
        $ticket = $this->findTicket($id);
        if (!$ticket) return ['success' => false, 'data' => null, 'error' => '客服单不存在'];
        if (!$this->canOperate($ticket, $this->currentUser())) {
            return ['success' => false, 'data' => null, 'error' => '无权发起该客服单的流程'];
        }
        if (in_array((int)$ticket['status'], [4, 5], true)) {
            return ['success' => false, 'data' => null, 'error' => '已解决/已关闭的客服单不能发起流程'];
        }

        $lock = $this->lockCheck($ticket);
        if ($lock !== true) return ['success' => false, 'data' => null, 'error' => '流程已在进行中'];

        $user = $this->currentUser();
        $actor = [
            'id'       => $actorUserId,
            'name'     => $this->userName($actorUserId),
            'dept_id'  => (int)($user['department_id'] ?? 0),
            'is_super' => $this->isAdminUser($user),
        ];

        try {
            $result = (new InstanceService((int)$this->storeId, $actorUserId, $this->isAdminUser($user)))
                ->start('crm_service_ticket', $id, $actor, $templateCode);
        } catch (\Throwable $e) {
            return ['success' => false, 'data' => null, 'error' => $e->getMessage() ?: '发起流程失败'];
        }

        return ['success' => true, 'data' => $result, 'error' => ''];
    }

    /** 撤回进行中的流程 */
    public function cancelFlow(int $id, string $reason, int $actorUserId): array
    {
        $ticket = $this->findTicket($id);
        if (!$ticket) return ['success' => false, 'data' => null, 'error' => '客服单不存在'];
        if (!$this->canOperate($ticket, $this->currentUser())) {
            return ['success' => false, 'data' => null, 'error' => '无权撤回该客服单的流程'];
        }

        $instance = Db::name('flow_instance')
            ->where('store_id', $this->storeId)
            ->where('biz_type', 'crm_service_ticket')
            ->where('biz_id', $id)
            ->where('status', 'running')
            ->order(['id' => 'desc'])->find();
        if (!$instance) {
            return ['success' => false, 'data' => null, 'error' => '没有进行中的流程'];
        }

        $user = $this->currentUser();
        $actor = [
            'id'       => $actorUserId,
            'name'     => $this->userName($actorUserId),
            'dept_id'  => (int)($user['department_id'] ?? 0),
            'is_super' => $this->isAdminUser($user),
        ];
        $reason = trim($reason) !== '' ? trim($reason) : '客服撤回流程';

        try {
            (new InstanceService((int)$this->storeId, $actorUserId, $this->isAdminUser($user)))
                ->terminate((int)$instance['id'], $reason, $actor);
        } catch (\Throwable $e) {
            return ['success' => false, 'data' => null, 'error' => $e->getMessage() ?: '撤回失败'];
        }

        return ['success' => true, 'data' => null, 'error' => ''];
    }

    /** 转办：把整张单交给别人（写记录 + 通知） */
    public function transfer(int $id, int $toUserId, string $reason, int $actorUserId): array
    {
        $ticket = $this->findTicket($id);
        if (!$ticket) return ['success' => false, 'data' => null, 'error' => '客服单不存在'];
        if (!$this->canOperate($ticket, $this->currentUser())) {
            return ['success' => false, 'data' => null, 'error' => '无权转办该客服单'];
        }
        if ((int)$ticket['status'] === 5) {
            return ['success' => false, 'data' => null, 'error' => '已关闭的客服单不能转办'];
        }
        if ($toUserId <= 0) {
            return ['success' => false, 'data' => null, 'error' => '请选择转办人'];
        }
        $reason = trim($reason);
        if ($reason === '') {
            return ['success' => false, 'data' => null, 'error' => '请填写转办原因'];
        }
        if ($toUserId === (int)$ticket['assignee_user_id']) {
            return ['success' => false, 'data' => null, 'error' => '该客服单已经由此人处理'];
        }

        $user = Db::name('store_user')->where('store_id', $this->storeId)
            ->where('store_user_id', $toUserId)->where('is_delete', 0)->find();
        if (!$user) return ['success' => false, 'data' => null, 'error' => '转办人不存在'];
        if ((int)($user['is_supplier'] ?? 0) === 1) {
            return ['success' => false, 'data' => null, 'error' => '不能转办给供应商账号'];
        }

        $auth = $this->lockCheck($ticket);
        if ($auth !== true) return ['success' => false, 'data' => null, 'error' => $auth];

        $fromUserId = (int)$ticket['assignee_user_id'];
        $fromName = $this->userName($fromUserId);
        $toName = (string)(($user['real_name'] ?? '') ?: ($user['user_name'] ?? ''));
        $deptId = (int)($user['department_id'] ?? 0);
        $now = time();

        $update = [
            'assignee_user_id' => $toUserId,
            'dept_id'          => $deptId,
            'update_time'      => $now,
        ];
        $status = (int)$ticket['status'];
        if (in_array($status, [1, 4], true)) {
            $update['status'] = 2; // 新建/已解决 转办后回到处理中
        }
        Db::name('crm_service_ticket')
            ->where('store_id', $this->storeId)->where('id', $id)
            ->update($update);

        TouchpointService::record(
            (int)$ticket['customer_id'],
            'service',
            'transferred',
            '客服单 ' . $ticket['ticket_no'] . "：转办给{$toName}",
            [
                'ticket_id' => $id,
                'from_user_id' => $fromUserId,
                'to_user_id'   => $toUserId,
                'reason'       => mb_substr($reason, 0, 500),
                'remark'        => mb_substr($reason, 0, 200),
            ],
            $actorUserId,
            $this->storeId,
            [
                'source_type'   => 'service_ticket',
                'source_id'     => $id,
                'action_url'    => '/crm/service/ticket?id=' . $id,
                'owner_user_id' => $toUserId,
                'dept_id'       => $deptId,
                'occurred_at'   => $now,
                'dedup_key'     => $this->dedup('transferred'),
            ]
        );

        (new NotificationService())->push(
            $toUserId,
            $this->storeId,
            '客服单转办：' . $ticket['ticket_no'],
            ($fromName !== '' ? $fromName . ' 把客服单转办给你：' : '客服单转办给你：') . $reason,
            'service_ticket_transfer',
            'service_ticket',
            $id,
            '/crm/service/ticket?id=' . $id
        );

        return ['success' => true, 'data' => null, 'error' => ''];
    }

    /** 新增一条处理/沟通记录 */
    public function addComment(int $id, string $content, int $actorUserId): array
    {
        $content = trim($content);
        if ($content === '') {
            return ['success' => false, 'data' => null, 'error' => '请填写跟进内容'];
        }
        $ticket = $this->findTicket($id);
        if (!$ticket) return ['success' => false, 'data' => null, 'error' => '客服单不存在'];
        if (!$this->canOperate($ticket, $this->currentUser())) {
            return ['success' => false, 'data' => null, 'error' => '无权记录该客服单'];
        }

        $now = time();
        Db::name('crm_service_ticket')
            ->where('store_id', $this->storeId)->where('id', $id)
            ->update(['update_time' => $now]);

        TouchpointService::record(
            (int)$ticket['customer_id'],
            'service',
            'comment',
            '客服单 ' . $ticket['ticket_no'] . '：新增跟进',
            [
                'ticket_id' => $id,
                'content'   => mb_substr($content, 0, 2000),
                'remark'    => mb_substr($content, 0, 200),
            ],
            $actorUserId,
            $this->storeId,
            [
                'source_type' => 'service_ticket',
                'source_id'   => $id,
                'action_url'  => '/crm/service/ticket?id=' . $id,
                'occurred_at' => $now,
                'dedup_key'   => $this->dedup('comment'),
            ]
        );

        return ['success' => true, 'data' => null, 'error' => ''];
    }

    /** 单独保存满意度评价 */
    public function rate(int $id, int $csat, int $actorUserId): array
    {
        if ($csat < 1 || $csat > 5) {
            return ['success' => false, 'data' => null, 'error' => '满意度必须是 1-5 分'];
        }
        $ticket = $this->findTicket($id);
        if (!$ticket) return ['success' => false, 'data' => null, 'error' => '客服单不存在'];
        if (!$this->canOperate($ticket, $this->currentUser())) {
            return ['success' => false, 'data' => null, 'error' => '无权评价该客服单'];
        }

        Db::name('crm_service_ticket')
            ->where('store_id', $this->storeId)->where('id', $id)
            ->update(['csat' => $csat, 'update_time' => time()]);

        TouchpointService::record(
            (int)$ticket['customer_id'],
            'service',
            'rated',
            '客服单 ' . $ticket['ticket_no'] . "：满意度 {$csat} 分",
            ['ticket_id' => $id, 'csat' => $csat],
            $actorUserId,
            $this->storeId,
            [
                'source_type' => 'service_ticket',
                'source_id'   => $id,
                'action_url'  => '/crm/service/ticket?id=' . $id,
                'occurred_at' => time(),
                'csat'        => $csat,
                'dedup_key'   => $this->dedup('rated'),
            ]
        );

        return ['success' => true, 'data' => ['csat' => $csat], 'error' => ''];
    }

    /** 删除（软删除 + 触点作废） */
    public function delete(int $id, int $actorUserId): array
    {
        $ticket = $this->findTicket($id);
        if (!$ticket) return ['success' => false, 'data' => null, 'error' => '客服单不存在'];
        if (!$this->canDelete($ticket, $this->currentUser())) {
            return ['success' => false, 'data' => null, 'error' => '无权删除该客服单'];
        }

        $now = time();
        Db::name('crm_service_ticket')
            ->where('store_id', $this->storeId)->where('id', $id)
            ->update(['is_delete' => 1, 'update_time' => $now]);

        Db::name('crm_touchpoint')
            ->where('store_id', $this->storeId)
            ->where('source_type', 'service_ticket')
            ->where('source_id', $id)
            ->where('voided_at', 0)
            ->update(['voided_at' => $now]);

        TouchpointService::record(
            (int)$ticket['customer_id'],
            'service',
            'deleted',
            '删除客服单 ' . $ticket['ticket_no'],
            ['ticket_id' => $id],
            $actorUserId,
            $this->storeId,
            [
                'source_type' => 'service_ticket',
                'source_id'   => $id,
                'dedup_key'   => $this->dedup('deleted'),
            ]
        );

        return ['success' => true, 'data' => null, 'error' => ''];
    }

    // ------------------------------------------------------------------
    // 数据范围
    // ------------------------------------------------------------------

    private function currentUser(): array
    {
        if ($this->user === null) {
            $this->user = TokenService::getUser() ?: [];
        }
        return $this->user;
    }

    private function isAdminUser(array $user): bool
    {
        return !empty($user['is_super']);
    }

    /** 列表范围：管理员全部；其他人 = 我处理/我创建/我可见客户/我管理部门 */
    private function applyScope($query, array $user): void
    {
        if ($this->isAdminUser($user)) return;

        $userId = (int)($user['store_user_id'] ?? 0);
        if ($userId <= 0) {
            $query->whereRaw('1=0');
            return;
        }

        $visibleCustomers = $this->visibleCustomerIds($userId);
        $deptIds = $this->managedDeptIds($userId);

        $query->where(function ($q) use ($userId, $visibleCustomers, $deptIds) {
            $q->where('t.assignee_user_id', $userId)
              ->whereOr('t.created_by', $userId);
            if (!empty($visibleCustomers)) {
                $q->whereOr('t.customer_id', 'in', $visibleCustomers);
            }
            if (!empty($deptIds)) {
                $q->whereOr('t.dept_id', 'in', $deptIds);
            }
        });
    }

    private function canView(array $ticket, array $user): bool
    {
        if ($this->isAdminUser($user)) return true;
        $userId = (int)($user['store_user_id'] ?? 0);
        if ($userId <= 0) return false;
        if ((int)$ticket['assignee_user_id'] === $userId) return true;
        if ((int)$ticket['created_by'] === $userId) return true;

        $deptIds = $this->managedDeptIds($userId);
        if (!empty($deptIds) && in_array((int)$ticket['dept_id'], $deptIds, true)) return true;

        return in_array((int)$ticket['customer_id'], $this->visibleCustomerIds($userId), true);
    }

    /** 能操作：管理员、处理人、创建人、管理部门；销售只读 */
    private function canOperate(array $ticket, array $user): bool
    {
        if ($this->isAdminUser($user)) return true;
        $userId = (int)($user['store_user_id'] ?? 0);
        if ($userId <= 0) return false;
        if ((int)$ticket['assignee_user_id'] === $userId) return true;
        if ((int)$ticket['created_by'] === $userId) return true;

        $deptIds = $this->managedDeptIds($userId);
        return !empty($deptIds) && in_array((int)$ticket['dept_id'], $deptIds, true);
    }

    /** 能删除：管理员、创建人、管理部门（处理人不能删别人的单） */
    private function canDelete(array $ticket, array $user): bool
    {
        if ($this->isAdminUser($user)) return true;
        $userId = (int)($user['store_user_id'] ?? 0);
        if ($userId <= 0) return false;
        if ((int)$ticket['created_by'] === $userId) return true;

        $deptIds = $this->managedDeptIds($userId);
        return !empty($deptIds) && in_array((int)$ticket['dept_id'], $deptIds, true);
    }

    private function managedDeptIds(int $userId): array
    {
        if (isset($this->managedDeptCache[$userId])) return $this->managedDeptCache[$userId];

        $ids = Db::name('store_department')
            ->where('store_id', $this->storeId)
            ->where('manager_id', $userId)
            ->where('is_delete', 0)
            ->column('id');
        $ids = array_map('intval', $ids);
        if (empty($ids)) {
            return $this->managedDeptCache[$userId] = [];
        }

        $queue = $ids;
        while (!empty($queue)) {
            $children = Db::name('store_department')
                ->where('store_id', $this->storeId)
                ->whereIn('parent_id', $queue)
                ->where('is_delete', 0)
                ->column('id');
            $children = array_values(array_diff(array_map('intval', $children), $ids));
            if (empty($children)) break;
            $ids = array_merge($ids, $children);
            $queue = $children;
        }
        return $this->managedDeptCache[$userId] = array_values(array_unique($ids));
    }

    private function visibleCustomerIds(int $userId): array
    {
        if (isset($this->visibleCustomerCache[$userId])) return $this->visibleCustomerCache[$userId];

        $model = new \mpp\crm\model\Customer();
        \mpp\crm\model\Customer::setCurrentUser($this->currentUser());
        \mpp\crm\model\Customer::$storeId = $this->storeId;

        return $this->visibleCustomerCache[$userId] = $model->getVisibleIds();
    }

    // ------------------------------------------------------------------

    private function flowInfo(int $ticketId): array
    {
        $instance = Db::name('flow_instance')
            ->where('store_id', $this->storeId)
            ->where('biz_type', 'crm_service_ticket')
            ->where('biz_id', $ticketId)
            ->order(['id' => 'desc'])->find();

        if (!$instance) {
            return ['active' => 0, 'instance_id' => 0, 'status' => '', 'status_text' => '', 'pending_tasks' => [], 'can_cancel' => 0];
        }

        $map = ['running' => '进行中', 'completed' => '已完成', 'rejected' => '已驳回', 'cancelled' => '已取消', 'terminated' => '已终止'];
        $user = $this->currentUser();
        $userId = (int)($user['store_user_id'] ?? 0);

        $tasks = [];
        if ((string)$instance['status'] === 'running') {
            $tasks = Db::name('flow_task')
                ->where('store_id', $this->storeId)
                ->where('instance_id', (int)$instance['id'])
                ->where('status', 'pending')
                ->field('id,node_key,node_name,assignee_name,assignee_id')
                ->select()->toArray();
        }

        return [
            'active'          => (string)$instance['status'] === 'running' ? 1 : 0,
            'instance_id'     => (int)$instance['id'],
            'status'          => (string)$instance['status'],
            'status_text'     => $map[(string)$instance['status']] ?? (string)$instance['status'],
            'started_by'      => (int)$instance['started_by'],
            'started_at'      => (int)$instance['started_at'],
            'started_at_text' => date('Y-m-d H:i', (int)$instance['started_at']),
            'pending_tasks'   => $tasks,
            'can_cancel'      => ((string)$instance['status'] === 'running'
                && ($this->isAdminUser($user) || (int)$instance['started_by'] === $userId)) ? 1 : 0,
        ];
    }

    private function userName(int $userId): string
    {
        if ($userId <= 0) return '';
        $u = Db::name('store_user')->where('store_id', $this->storeId)
            ->where('store_user_id', $userId)->field('real_name,user_name')->find();
        return (string)(($u['real_name'] ?? '') ?: ($u['user_name'] ?? ''));
    }

    /** 有进行中的流程时，禁止手动指派/改状态/转办 */
    private function lockCheck(array $ticket)
    {
        $running = (int)Db::name('flow_instance')
            ->where('store_id', $this->storeId)
            ->where('biz_type', 'crm_service_ticket')
            ->where('biz_id', (int)$ticket['id'])
            ->where('status', 'running')
            ->value('id');
        if ($running > 0) return '流程进行中，请在流程中处理';
        return true;
    }

    private function findTicket(int $id): array
    {
        $row = Db::name('crm_service_ticket')
            ->where('store_id', $this->storeId)->where('id', $id)->where('is_delete', 0)->find();
        return $row ?: [];
    }

    private function logAssigned(array $ticket, int $assigneeId, int $actorUserId): void
    {
        $user = Db::name('store_user')->where('store_id', $this->storeId)
            ->where('store_user_id', $assigneeId)->field('real_name,user_name,department_id')->find();
        $name = (string)(($user['real_name'] ?? '') ?: ($user['user_name'] ?? ''));

        (new NotificationService())->push(
            $assigneeId,
            $this->storeId,
            '客服单指派给你：' . $ticket['ticket_no'],
            '请打开处理：' . ($ticket['title'] ?? ''),
            'service_ticket_assigned',
            'service_ticket',
            (int)$ticket['id'],
            '/crm/service/ticket?id=' . $ticket['id']
        );

        TouchpointService::record(
            (int)$ticket['customer_id'],
            'service',
            'assigned',
            '客服单 ' . $ticket['ticket_no'] . '：指派给' . ($name !== '' ? $name : ('用户#' . $assigneeId)),
            ['ticket_id' => (int)$ticket['id'], 'assignee_user_id' => $assigneeId],
            $actorUserId,
            $this->storeId,
            [
                'source_type'   => 'service_ticket',
                'source_id'     => (int)$ticket['id'],
                'action_url'    => '/crm/service/ticket?id=' . $ticket['id'],
                'owner_user_id' => $assigneeId,
                'dept_id'       => (int)($user['department_id'] ?? 0),
                'dedup_key'     => $this->dedup('assigned'),
            ]
        );
    }

    private function generateNo(): string
    {
        $prefix = 'ST-' . date('Ymd') . '-';
        $count = Db::name('crm_service_ticket')
            ->where('store_id', $this->storeId)
            ->where('ticket_no', 'like', $prefix . '%')
            ->count();
        $seq = (int)$count + 1;
        while (true) {
            $no = $prefix . str_pad((string)$seq, 3, '0', STR_PAD_LEFT);
            $exists = Db::name('crm_service_ticket')
                ->where('store_id', $this->storeId)->where('ticket_no', $no)->count();
            if (!$exists) return $no;
            $seq++;
        }
    }

    private function decorate(array &$row): void
    {
        $row['status_text']   = self::STATUS_LABELS[(int)$row['status']] ?? '';
        $row['category_text'] = self::CATEGORY_LABELS[$row['category']] ?? $row['category'];
        $row['channel_text']  = self::CHANNEL_LABELS[$row['channel']] ?? $row['channel'];
        $row['priority_text'] = self::PRIORITY_LABELS[(int)$row['priority']] ?? '';
        $row['assignee_name'] = (string)(($row['real_name'] ?? '') ?: ($row['user_name'] ?? ''));
        $row['expect_at_text'] = !empty($row['expect_at']) ? date('Y-m-d H:i', (int)$row['expect_at']) : '';
        $row['overdue'] = 0;
        $row['due_soon'] = 0;
        if (!empty($row['expect_at']) && !in_array((int)$row['status'], [4, 5], true)) {
            $now = time();
            if ($now >= (int)$row['expect_at']) {
                $row['overdue'] = 1;
            } elseif ($now >= (int)$row['expect_at'] - 3600) {
                $row['due_soon'] = 1;
            }
        }
    }

    private function pick($value, array $allowed, string $default): string
    {
        $value = (string)$value;
        return isset($allowed[$value]) ? $value : $default;
    }

    private function dedup(string $prefix): string
    {
        return $prefix . ':' . str_replace('.', '', (string)microtime(true));
    }
}
