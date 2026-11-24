<?php
namespace mpp\crm\service;

use app\platform\BaseService;
use mpp\crm\model\Customer as CustomerModel;
use app\platform\auth\TokenService;
use app\common\service\AiService;
use mpp\crm\service\OperationLogService;
use think\facade\Db;

class CustomerService extends BaseService
{
    /** 列表 */
    public function getList(array $params): array
    {
        $model = new CustomerModel();
        CustomerModel::setCurrentUser(TokenService::getUser());

        // 按标签筛选：AND 逻辑，客户必须同时拥有所有选中的标签
        if (!empty($params['tag_ids'])) {
            \mpp\crm\model\CustomerTag::$storeId = $this->storeId;
            $customerIds = (new \mpp\crm\model\CustomerTag())->getCustomerIdsByTagIds((array)$params['tag_ids']);
            if (empty($customerIds)) {
                // 没有匹配的客户，返回空列表
                return ['success' => true, 'data' => ['list' => ['data' => [], 'total' => 0]], 'error' => ''];
            }
            $params['_filter_customer_ids'] = $customerIds;
        }

        $list = $model->getList($params);
        return ['success' => true, 'data' => compact('list'), 'error' => ''];
    }

    /** 下拉选择 */
    public function select(array $params = []): array
    {
        $model = new CustomerModel();
        $where = [];
        if (!empty($params['keyword'])) {
            $where['keyword'] = $params['keyword'];
        }
        $list = $model->select($where);
        return ['success' => true, 'data' => compact('list'), 'error' => ''];
    }

    /** 详情 */
    public function detail(int $id, string $from = ''): array
    {
        $model = new CustomerModel();
        CustomerModel::setCurrentUser(TokenService::getUser());
        $detail = $model->detail($id, $from);
        if (!$detail) return ['success' => false, 'data' => null, 'error' => '客户不存在'];
        return ['success' => true, 'data' => compact('detail'), 'error' => ''];
    }

    /** 公海列表 */
    public function getPoolList(array $params): array
    {
        $model = new CustomerModel();
        $list = $model->getPoolList($params);
        $counts = $model->getPoolCounts();
        return ['success' => true, 'data' => ['list' => $list, 'counts' => $counts], 'error' => ''];
    }

    /** 协作列表 */
    public function getCollabList(array $params): array
    {
        $model = new CustomerModel();
        CustomerModel::setCurrentUser(TokenService::getUser());
        $list = $model->getCollabList($params);
        $counts = $model->getCollabCounts();
        return ['success' => true, 'data' => ['list' => $list, 'counts' => $counts], 'error' => ''];
    }

    /** 新增 */
    public function add(array $data, int $actorUserId): array
    {
        // ThinkPHP 默认 htmlspecialchars 会把 extra JSON 中的 \" 转成 &quot;
        if (!empty($data['extra']) && is_string($data['extra'])) {
            $data['extra'] = htmlspecialchars_decode($data['extra'], ENT_QUOTES);
        }
        // 负责人部门兜底：录了负责人但没带部门时，从用户资料补
        if (!empty($data['owner_user_id']) && empty($data['owner_department_id'])) {
            $data['owner_department_id'] = (int)Db::name('store_user')
                ->where('store_id', $this->storeId)
                ->where('store_user_id', (int)$data['owner_user_id'])
                ->value('department_id');
        }
        // 客服责任人：先校验，再随客户一起落库
        $csUserId = (int)($data['cs_user_id'] ?? 0);
        if ($csUserId > 0) {
            $csCheck = $this->validateCsUser($csUserId);
            if ($csCheck !== true) return ['success' => false, 'data' => null, 'error' => $csCheck];
            $data['cs_department_id'] = (int)Db::name('store_user')->where('store_id', $this->storeId)
                ->where('store_user_id', $csUserId)->value('department_id');
            $data['cs_pool_time'] = 0;
        } else {
            unset($data['cs_user_id']);
        }
        $model = new CustomerModel();
        if ($model->add($data)) {
            if ($csUserId > 0) {
                $this->recordCsChange($model->id, 0, $csUserId, $actorUserId);
            }
            EventService::record('customer_created', 'customer', $model->id, [
                'customer_name' => $data['customer_name'] ?? '',
                'source' => $data['source'] ?? '',
                'industry' => $data['industry'] ?? '',
                'owner_id' => $data['owner_user_id'] ?? 0,
                'creator_id' => $data['creator_user_id'] ?? 0,
            ], $actorUserId);
            OperationLogService::logAdd('客户', $model->id, $data['customer_name'] ?? '');
            try { (new \mpp\crm\service\DemandService())->evaluateCustomer($model->id, $this->storeId); } catch (\Throwable $e) {}
            return ['success' => true, 'data' => ['id' => $model->id], 'error' => ''];
        }
        return ['success' => false, 'data' => null, 'error' => $model->getError() ?: '添加失败'];
    }

    /** 编辑 */
    public function edit(int $id, array $data, int $actorUserId): array
    {
        // ThinkPHP 默认 htmlspecialchars 会把 extra JSON 中的 \" 转成 &quot;
        if (!empty($data['extra']) && is_string($data['extra'])) {
            $data['extra'] = htmlspecialchars_decode($data['extra'], ENT_QUOTES);
        }
        $model = new CustomerModel();
        $detail = $model->detail($id);
        if (!$detail) return ['success' => false, 'data' => null, 'error' => '客户不存在'];
        // 检测 owner 变更
        $oldOwner = $detail['owner_user_id'] ?? 0;
        // 负责人部门兜底：负责人变了但没带部门时，从用户资料补
        if (array_key_exists('owner_user_id', $data) && empty($data['owner_department_id'])) {
            $data['owner_department_id'] = (int)Db::name('store_user')
                ->where('store_id', $this->storeId)
                ->where('store_user_id', (int)$data['owner_user_id'])
                ->value('department_id');
        }
        // 客服责任人变更
        $oldCs = (int)($detail['cs_user_id'] ?? 0);
        $newCs = array_key_exists('cs_user_id', $data) ? (int)$data['cs_user_id'] : $oldCs;
        if ($newCs > 0 && $newCs !== $oldCs) {
            $csCheck = $this->validateCsUser($newCs);
            if ($csCheck !== true) return ['success' => false, 'data' => null, 'error' => $csCheck];
            $data['cs_department_id'] = (int)Db::name('store_user')->where('store_id', $this->storeId)
                ->where('store_user_id', $newCs)->value('department_id');
            $data['cs_pool_time'] = 0;
        }
        if ($newCs === 0) {
            $data['cs_department_id'] = 0;
        }
        if ($detail->edit($data)) {
            EventService::record('customer_updated', 'customer', $id, [
                'changed_fields' => array_keys($data),
            ], $actorUserId);
            $name = $data['customer_name'] ?? ($detail['customer_name'] ?? '');
            OperationLogService::logEdit('客户', $id, $name, $data);
            $newOwner = $data['owner_user_id'] ?? $oldOwner;
            if ($oldOwner != $newOwner) {
                EventService::record('customer_updated', 'customer', $id, [
                    'owner_changed' => true, 'from_owner' => $oldOwner, 'to_owner' => $newOwner,
                ], $actorUserId);
            }
            if ($newCs !== $oldCs) {
                $this->recordCsChange($id, $oldCs, $newCs, $actorUserId);
            }
            try { (new \mpp\crm\service\DemandService())->evaluateCustomer($id, $this->storeId); } catch (\Throwable $e) {}
            return ['success' => true, 'data' => null, 'error' => ''];
        }
        return ['success' => false, 'data' => null, 'error' => $detail->getError() ?: '更新失败'];
    }

    /** 校验客服人员可用 */
    private function validateCsUser(int $csUserId)
    {
        $user = Db::name('store_user')->where('store_id', $this->storeId)
            ->where('store_user_id', $csUserId)->where('is_delete', 0)->find();
        if (!$user) return '客服人员不存在';
        if (empty($user['department_id'])) return '该用户未分配部门，无法确定客服归属';
        return true;
    }

    /** 客服责任人变更留痕，并补齐项目的客服快照 */
    private function recordCsChange(int $customerId, int $oldCs, int $newCs, int $actorUserId): void
    {
        Db::name('crm_customer_claim_log')->insert([
            'store_id'     => $this->storeId,
            'customer_id'  => $customerId,
            'from_user_id' => $oldCs,
            'to_user_id'   => $newCs,
            'action'       => 4,
            'slot'         => 2,
            'remark'       => $oldCs > 0 ? '变更服务责任人' : '新增客户时指定客服',
            'create_time'  => time(),
        ]);
        if ($newCs > 0) {
            Db::name('crm_project')->where('store_id', $this->storeId)->where('customer_id', $customerId)
                ->where('cs_user_id', 0)->where('is_delete', 0)
                ->update(['cs_user_id' => $newCs, 'update_time' => time()]);
        }
        EventService::record('customer_cs_assigned', 'customer', $customerId, [
            'from_user_id' => $oldCs, 'to_user_id' => $newCs,
        ], $actorUserId);
    }

    /** 删除 */
    public function delete(int $id, int $actorUserId): array
    {
        $model = new CustomerModel();
        $detail = $model->detail($id);
        if (!$detail) return ['success' => false, 'data' => null, 'error' => '客户不存在'];
        if ($detail->setDelete($id)) {
            CustomerLogService::record($id, 'customer_deleted', '删除客户', [
                'customer_name' => $detail['customer_name'] ?? '',
            ], $actorUserId, $this->storeId);
            OperationLogService::logDelete('客户', $id, $detail['customer_name'] ?? '');
            return ['success' => true, 'data' => null, 'error' => ''];
        }
        return ['success' => false, 'data' => null, 'error' => $detail->getError() ?: '删除失败'];
    }

    /** 认领 */
    public function claim(int $id, int $actorUserId): array
    {
        $customer = (new CustomerModel())->where('id', $id)->find();
        if (!$customer) return ['success' => false, 'data' => null, 'error' => '客户不存在'];
        if ($customer['owner_user_id'] != 0) return ['success' => false, 'data' => null, 'error' => '该客户不属于公海'];
        $oldOwner = $customer['owner_user_id'];
        $customer->owner_user_id = $actorUserId;
        $customer->enter_pool_time = null;
        $customer->last_followup_time = time();
        if ($customer->save()) {
            EventService::record('customer_claimed', 'customer', $id, [
                'from_user_id' => $oldOwner, 'to_user_id' => $actorUserId,
            ], $actorUserId);
            OperationLogService::log('claim', '客户', $id, "认领客户「{$customer['customer_name']}」");
            try { InsightService::handleEvent('customer_claimed', $id); } catch (\Throwable $e) {}
            return ['success' => true, 'data' => null, 'error' => ''];
        }
        return ['success' => false, 'data' => null, 'error' => '认领失败'];
    }

    /** 释放到公海 */
    public function release(int $id, int $actorUserId): array
    {
        $customer = (new CustomerModel())->where('id', $id)->find();
        if (!$customer) return ['success' => false, 'data' => null, 'error' => '客户不存在'];
        $oldOwner = $customer['owner_user_id'];
        $customer->owner_user_id = 0;
        $customer->enter_pool_time = time();
        if ($customer->save()) {
            EventService::record('customer_released', 'customer', $id, [
                'from_user_id' => $oldOwner, 'reason' => '主动释放',
            ], $actorUserId);
            OperationLogService::log('release', '客户', $id, "释放客户「{$customer['customer_name']}」到公海");
            try { InsightService::handleEvent('customer_released', $id); } catch (\Throwable $e) {}
            return ['success' => true, 'data' => null, 'error' => ''];
        }
        return ['success' => false, 'data' => null, 'error' => '释放失败'];
    }

    // ------------------------------------------------------------------
    // 客服责任位（待分配池 · D12）
    // ------------------------------------------------------------------

    /**
     * 待分配客服的客户列表
     * 存量客户 cs_pool_time = 0：只进管理员清单，不计时、不开放认领
     */
    public function getAssignList(array $params): array
    {
        $query = Db::name('crm_customer')
            ->where('store_id', $this->storeId)
            ->where('is_delete', 0)
            ->where('owner_user_id', '>', 0)
            ->where('cs_user_id', 0);

        $poolType = (string)($params['pool_type'] ?? '');
        if ($poolType === 'legacy') $query->where('cs_pool_time', 0);
        if ($poolType === 'new')    $query->where('cs_pool_time', '>', 0);

        if (!empty($params['keyword'])) {
            $kw = (string)$params['keyword'];
            $query->where(function ($q) use ($kw) {
                $q->where('customer_name', 'like', "%{$kw}%")
                  ->whereOr('customer_code', 'like', "%{$kw}%")
                  ->whereOr('telephone', 'like', "%{$kw}%");
            });
        }
        if (!empty($params['owner_user_id'])) $query->where('owner_user_id', (int)$params['owner_user_id']);

        $page = max(1, (int)($params['page'] ?? 1));
        $size = min(100, max(10, (int)($params['list_rows'] ?? 20)));
        $total = (clone $query)->count();
        $list = $query->field('id,customer_name,customer_code,industry,province,city,owner_user_id,cs_user_id,cs_pool_time,last_followup_time,create_time')
            ->order(['cs_pool_time' => 'desc', 'id' => 'desc'])->page($page, $size)->select()->toArray();

        $ownerIds = array_values(array_unique(array_column($list, 'owner_user_id')));
        $users = empty($ownerIds) ? [] : Db::name('store_user')->where('store_id', $this->storeId)
            ->whereIn('store_user_id', $ownerIds)->column('user_name,real_name', 'store_user_id');
        foreach ($list as &$row) {
            $u = isset($users[(int)$row['owner_user_id']]) ? $users[(int)$row['owner_user_id']] : [];
            $row['owner_user_name'] = (string)(($u['real_name'] ?? '') ?: ($u['user_name'] ?? ''));
            $row['is_legacy'] = (int)$row['cs_pool_time'] === 0;
            $row['pool_days'] = (int)$row['cs_pool_time'] > 0
                ? (int)floor((time() - (int)$row['cs_pool_time']) / 86400) : 0;
        }
        unset($row);
        // 池子总览（不受筛选影响，用于页面顶部卡片）
        $countRow = Db::name('crm_customer')
            ->where('store_id', $this->storeId)->where('is_delete', 0)
            ->where('owner_user_id', '>', 0)->where('cs_user_id', 0)
            ->field('COUNT(*) AS total, SUM(cs_pool_time > 0) AS new_cnt, SUM(cs_pool_time = 0) AS legacy_cnt')
            ->find();
        $counts = [
            'all'    => (int)($countRow['total'] ?? 0),
            'new'    => (int)($countRow['new_cnt'] ?? 0),
            'legacy' => (int)($countRow['legacy_cnt'] ?? 0),
        ];
        return ['success' => true, 'data' => [
            'list' => $list, 'total' => $total, 'page' => $page, 'list_rows' => $size, 'counts' => $counts,
        ], 'error' => ''];
    }

    /** 指定 / 变更客服责任人 */
    public function assignCs(int $id, int $csUserId, int $actorUserId): array
    {
        $customer = Db::name('crm_customer')->where('store_id', $this->storeId)->where('id', $id)->where('is_delete', 0)->find();
        if (!$customer) return ['success' => false, 'data' => null, 'error' => '客户不存在'];

        $user = Db::name('store_user')->where('store_id', $this->storeId)
            ->where('store_user_id', $csUserId)->where('is_delete', 0)->find();
        if (!$user) return ['success' => false, 'data' => null, 'error' => '客服人员不存在'];
        if (empty($user['department_id'])) return ['success' => false, 'data' => null, 'error' => '该用户未分配部门，无法确定客服归属'];

        $old = (int)$customer['cs_user_id'];
        Db::startTrans();
        try {
            Db::name('crm_customer')->where('store_id', $this->storeId)->where('id', $id)->update([
                'cs_user_id'       => $csUserId,
                'cs_department_id' => (int)$user['department_id'],
                'cs_pool_time'     => 0,
                'update_time'      => time(),
            ]);
            // 项目里的客服快照只补空缺，不覆盖已有负责人
            Db::name('crm_project')->where('store_id', $this->storeId)->where('customer_id', $id)
                ->where('cs_user_id', 0)->where('is_delete', 0)
                ->update(['cs_user_id' => $csUserId, 'update_time' => time()]);
            Db::name('crm_customer_claim_log')->insert([
                'store_id'     => $this->storeId,
                'customer_id'  => $id,
                'from_user_id' => $old,
                'to_user_id'   => $csUserId,
                'action'       => 4,
                'slot'         => 2,
                'remark'       => $old > 0 ? '变更服务责任人' : '分配客服',
                'create_time'  => time(),
            ]);
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            return ['success' => false, 'data' => null, 'error' => '分配失败：' . $e->getMessage()];
        }
        EventService::record('customer_cs_assigned', 'customer', $id, [
            'from_user_id' => $old, 'to_user_id' => $csUserId,
        ], $actorUserId);
        OperationLogService::log('assign_cs', '客户', $id, '指定客服责任人');
        return ['success' => true, 'data' => null, 'error' => ''];
    }

    /** 组内认领：只允许新单待办，存量客户由管理员指定 */
    public function claimCs(int $id, int $actorUserId): array
    {
        $customer = Db::name('crm_customer')->where('store_id', $this->storeId)->where('id', $id)->where('is_delete', 0)->find();
        if (!$customer) return ['success' => false, 'data' => null, 'error' => '客户不存在'];
        if ((int)$customer['cs_user_id'] > 0) return ['success' => false, 'data' => null, 'error' => '该客户已有客服责任人'];
        if ((int)$customer['cs_pool_time'] === 0) return ['success' => false, 'data' => null, 'error' => '存量客户不开放认领，请联系管理员指定'];
        return $this->assignCs($id, $actorUserId, $actorUserId);
    }

    /** 状态变更 */
    public function changeStatus(int $id, int $status, int $actorUserId): array
    {
        $customer = (new CustomerModel())->where('id', $id)->find();
        if (!$customer) return ['success' => false, 'data' => null, 'error' => '客户不存在'];
        $oldStatus = $customer['status'];
        $customer->status = $status;
        if ($customer->save()) {
            EventService::record('customer_updated', 'customer', $id, [
                'status_changed' => true, 'from_status' => $oldStatus, 'to_status' => $status,
            ], $actorUserId);
            OperationLogService::log('edit', '客户', $id, "变更客户状态「{$customer['customer_name']}」: {$oldStatus}→{$status}");
            return ['success' => true, 'data' => null, 'error' => ''];
        }
        return ['success' => false, 'data' => null, 'error' => '更新失败'];
    }

    /** AI分析 */
    public function analyze(int $id): array
    {
        $customer = (new CustomerModel())->where('id', $id)->find();
        if (!$customer) return ['success' => false, 'data' => null, 'error' => '客户不存在'];

        $followups = \think\facade\Db::name('crm_followup')
            ->where('customer_id', $id)->where('is_delete', 0)
            ->order(['follow_date' => 'desc'])->limit(10)
            ->field(['follow_type', 'follow_content', 'result', 'follow_date'])
            ->select()->toArray();
        $fuText = '';
        foreach ($followups as $fu) {
            $fuText .= date('Y-m-d', $fu['follow_date'])
                . ' [' . $fu['follow_type'] . '/' . $fu['result'] . '] '
                . mb_substr($fu['follow_content'], 0, 200) . "\n";
        }

        $customerData = $customer->toArray();
        $customerData['recent_followups'] = $fuText;

        $result = AiService::analyzeCustomer($customerData);
        if (!$result['success']) return ['success' => false, 'data' => null, 'error' => 'AI分析失败：' . $result['error']];

        $aiData = $result['data'];
        $customer->ai_score = intval($aiData['score'] ?? 0);
        $customer->ai_tags = is_array($aiData['tags'] ?? null) ? implode(',', $aiData['tags']) : '';
        $customer->ai_suggestion = $aiData['suggestion'] ?? '';
        $customer->ai_analysis_time = time();
        $customer->save();

        return ['success' => true, 'data' => [
            'ai_score' => $customer->ai_score,
            'ai_tags' => $customer->ai_tags,
            'ai_suggestion' => $customer->ai_suggestion,
            'ai_analysis_time' => $customer->ai_analysis_time,
        ], 'error' => ''];
    }

    /** AI画像 */
    public function portrait(int $id): array
    {
        $customer = (new CustomerModel())->where('id', $id)->find();
        if (!$customer) return ['success' => false, 'data' => null, 'error' => '客户不存在'];

        $followups = \think\facade\Db::name('crm_followup')
            ->where('customer_id', $id)->where('is_delete', 0)
            ->order(['follow_date' => 'desc'])->limit(10)
            ->field(['follow_type', 'follow_content', 'result', 'follow_date'])
            ->select()->toArray();
        $fuText = '';
        foreach ($followups as $fu) {
            $fuText .= date('Y-m-d', $fu['follow_date'])
                . ' [' . $fu['follow_type'] . '/' . $fu['result'] . '] '
                . mb_substr($fu['follow_content'], 0, 200) . "\n";
        }

        $customerData = $customer->toArray();
        $customerData['recent_followups'] = $fuText;

        $result = AiService::generatePortrait($customerData);
        if (!$result['success']) return ['success' => false, 'data' => null, 'error' => '画像生成失败：' . $result['error']];

        $customer->ai_portrait = json_encode($result['data'], JSON_UNESCAPED_UNICODE);
        $customer->save();

        return ['success' => true, 'data' => ['portrait' => $result['data']], 'error' => ''];
    }

    /** AI智能搜索 */
    public function smartSearch(string $query): array
    {
        if (empty($query)) return ['success' => false, 'data' => null, 'error' => '请输入搜索内容'];
        $result = AiService::smartSearch($query);
        if (!$result['success']) return ['success' => false, 'data' => null, 'error' => '智能搜索失败：' . $result['error']];
        return ['success' => true, 'data' => ['filters' => $result['data']], 'error' => ''];
    }

    // ------------------------------------------------------------------
    // 批量转让（销售责任人）
    // ------------------------------------------------------------------

    /**
     * 批量转让客户负责人
     *
     * 规则（2026-11 定）：
     *  - 只转客户归属（owner_user_id / owner_department_id），项目、报价、订单、
     *    合同、跟进记录一概不动，仍按各自的负责人走。
     *  - 数据范围与列表可见性一致：超管=全部；部门经理=本部门及子部门成员名下；
     *    其他人=自己负责的客户。入口由菜单权限控制，这里再兜一道。
     *  - 勾选转让单次最多 2000 个；按负责人整体转让单次最多 5000 个。
     */
    public function batchTransfer(array $ids, int $toUserId, string $remark, int $actorUserId): array
    {
        return $this->executeTransfer($ids, $toUserId, $remark, $actorUserId, 2000);
    }

    /**
     * 按原负责人整体转让：把该负责人名下全部客户一次性转给新负责人
     */
    public function transferByOwner(int $fromUserId, int $toUserId, string $remark, int $actorUserId): array
    {
        if ($fromUserId <= 0) {
            return ['success' => false, 'data' => null, 'error' => '请选择原负责人'];
        }
        $from = Db::name('store_user')
            ->where('store_id', $this->storeId)
            ->where('store_user_id', $fromUserId)
            ->find();
        if (!$from) {
            return ['success' => false, 'data' => null, 'error' => '原负责人不存在或已删除'];
        }

        $allowedOwnerIds = $this->getTransferAllowedOwnerIds($actorUserId);
        if ($allowedOwnerIds === []) {
            return ['success' => false, 'data' => null, 'error' => '当前账号没有可转让的客户'];
        }
        if ($allowedOwnerIds !== null && !in_array($fromUserId, $allowedOwnerIds, true)) {
            return ['success' => false, 'data' => null, 'error' => '该负责人不在你的数据范围内'];
        }

        $ids = Db::name('crm_customer')
            ->where('store_id', $this->storeId)
            ->where('is_delete', 0)
            ->where('owner_user_id', $fromUserId)
            ->column('id');
        if (empty($ids)) {
            return ['success' => false, 'data' => null, 'error' => '该负责人名下没有客户'];
        }

        return $this->executeTransfer($ids, $toUserId, $remark, $actorUserId, 5000);
    }

    /** 转让执行：校验 + 落库（$maxCount 控制单次上限） */
    private function executeTransfer(array $ids, int $toUserId, string $remark, int $actorUserId, int $maxCount): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (empty($ids)) {
            return ['success' => false, 'data' => null, 'error' => '请选择要转让的客户'];
        }
        if (count($ids) > $maxCount) {
            return ['success' => false, 'data' => null, 'error' => "单次最多转让 {$maxCount} 个客户，请分批操作"];
        }
        if ($toUserId <= 0) {
            return ['success' => false, 'data' => null, 'error' => '请选择新的负责人'];
        }

        $target = Db::name('store_user')
            ->where('store_id', $this->storeId)
            ->where('store_user_id', $toUserId)
            ->where('is_delete', 0)
            ->find();
        if (!$target) {
            return ['success' => false, 'data' => null, 'error' => '新负责人不存在或已删除'];
        }
        if ((int)($target['is_supplier'] ?? 0) === 1) {
            return ['success' => false, 'data' => null, 'error' => '不能转让给供应商账号'];
        }

        // 数据范围：null = 不限制（超管）
        $allowedOwnerIds = $this->getTransferAllowedOwnerIds($actorUserId);
        if ($allowedOwnerIds === []) {
            return ['success' => false, 'data' => null, 'error' => '当前账号没有可转让的客户'];
        }
        $allowedSet = $allowedOwnerIds === null ? null : array_flip($allowedOwnerIds);

        $rows = Db::name('crm_customer')
            ->where('store_id', $this->storeId)
            ->where('is_delete', 0)
            ->whereIn('id', $ids)
            ->field('id,customer_name,owner_user_id,owner_department_id')
            ->select()->toArray();

        $targets   = [];
        $forbidden = 0;
        $unchanged = 0;
        $missing   = count($ids) - count($rows);
        foreach ($rows as $row) {
            $oldOwner = (int)$row['owner_user_id'];
            if ($oldOwner === $toUserId) {
                $unchanged++;
                continue;
            }
            if ($allowedSet !== null && !isset($allowedSet[$oldOwner])) {
                $forbidden++;
                continue;
            }
            $targets[] = $row;
        }

        if (empty($targets)) {
            $msg = '没有可转让的客户';
            if ($unchanged > 0) $msg .= "（{$unchanged} 个已属于该负责人）";
            if ($forbidden > 0) $msg .= "（{$forbidden} 个不在你的数据范围内）";
            if ($missing > 0)   $msg .= "（{$missing} 个不存在或已删除）";
            return ['success' => false, 'data' => null, 'error' => $msg];
        }

        $toDeptId   = (int)($target['department_id'] ?? 0);
        $targetName = (string)(($target['real_name'] ?? '') ?: ($target['user_name'] ?? ''));
        $now        = time();
        $remark     = trim($remark);
        if (mb_strlen($remark) > 200) $remark = mb_substr($remark, 0, 200);
        $logRemark  = '批量转让给' . $targetName . ($remark !== '' ? '：' . $remark : '');

        // 原负责人姓名，用于日志标题
        $fromOwnerIds = array_values(array_unique(array_map(function ($row) {
            return (int)$row['owner_user_id'];
        }, $targets)));
        $fromOwnerNames = [];
        if (!empty($fromOwnerIds)) {
            $fromUsers = Db::name('store_user')
                ->where('store_id', $this->storeId)
                ->whereIn('store_user_id', $fromOwnerIds)
                ->field('store_user_id,real_name,user_name')
                ->select()->toArray();
            foreach ($fromUsers as $u) {
                $fromOwnerNames[(int)$u['store_user_id']] = (string)(($u['real_name'] ?? '') ?: ($u['user_name'] ?? ''));
            }
        }

        Db::startTrans();
        try {
            foreach (array_chunk($targets, 500) as $chunk) {
                $chunkIds = array_map('intval', array_column($chunk, 'id'));
                Db::name('crm_customer')
                    ->where('store_id', $this->storeId)
                    ->whereIn('id', $chunkIds)
                    ->update([
                        'owner_user_id'       => $toUserId,
                        'owner_department_id' => $toDeptId,
                        'update_time'         => $now,
                    ]);

                $logs = [];
                foreach ($chunk as $row) {
                    $logs[] = [
                        'store_id'     => $this->storeId,
                        'customer_id'  => (int)$row['id'],
                        'from_user_id' => (int)$row['owner_user_id'],
                        'to_user_id'   => $toUserId,
                        'action'       => 4,  // 4=管理员/管理人分配
                        'slot'         => 1,  // 1=销售责任位
                        'remark'       => $logRemark,
                        'create_time'  => $now,
                    ];
                }
                Db::name('crm_customer_claim_log')->insertAll($logs);

                foreach ($chunk as $row) {
                    $fromOwner = (int)$row['owner_user_id'];
                    CustomerLogService::record(
                        (int)$row['id'],
                        'customer_transferred',
                        '转让负责人：' . ($fromOwnerNames[$fromOwner] ?? '未分配') . ' → ' . $targetName,
                        [
                            'from_owner' => $fromOwner,
                            'to_owner'   => $toUserId,
                            'remark'     => $remark,
                        ],
                        $actorUserId,
                        $this->storeId
                    );
                }
            }
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            return ['success' => false, 'data' => null, 'error' => '转让失败：' . $e->getMessage()];
        }

        $count = count($targets);
        OperationLogService::log('transfer', '客户', 0, "批量转让 {$count} 个客户给「{$targetName}」", [
            'to_user_id'   => $toUserId,
            'customer_ids' => array_map('intval', array_column($targets, 'id')),
            'skipped'      => ['forbidden' => $forbidden, 'unchanged' => $unchanged, 'missing' => $missing],
            'remark'       => $remark,
        ], $actorUserId, '', $this->storeId);

        return ['success' => true, 'data' => [
            'count'        => $count,
            'forbidden'    => $forbidden,
            'unchanged'    => $unchanged,
            'missing'      => $missing,
            'to_user_name' => $targetName,
        ], 'error' => ''];
    }

    /**
     * 转让时允许操作的「原负责人」集合，null 表示不限制（超管）
     */
    private function getTransferAllowedOwnerIds(int $actorUserId): ?array
    {
        $actor = Db::name('store_user')
            ->where('store_id', $this->storeId)
            ->where('store_user_id', $actorUserId)
            ->find();
        if (!$actor) return [];
        if (!empty($actor['is_super'])) return null;

        $deptIds = $this->getManagedDeptIds($actorUserId);
        if (empty($deptIds)) return [$actorUserId];

        $ownerIds = Db::name('store_user')
            ->where('store_id', $this->storeId)
            ->where('is_delete', 0)
            ->whereIn('department_id', $deptIds)
            ->column('store_user_id');
        $ownerIds[] = $actorUserId;
        return array_values(array_unique(array_map('intval', $ownerIds)));
    }

    /**
     * 我管辖的部门子树（可能同时管多个中心）
     */
    private function getManagedDeptIds(int $userId): array
    {
        $ids = Db::name('store_department')
            ->where('store_id', $this->storeId)
            ->where('manager_id', $userId)
            ->where('is_delete', 0)
            ->column('id');
        $ids = array_map('intval', $ids);
        if (empty($ids)) return [];

        $queue = $ids;
        while (!empty($queue)) {
            $children = Db::name('store_department')
                ->where('store_id', $this->storeId)
                ->whereIn('parent_id', $queue)
                ->where('is_delete', 0)
                ->column('id');
            $children = array_values(array_diff(array_map('intval', $children), $ids));
            if (empty($children)) break;
            $ids   = array_merge($ids, $children);
            $queue = $children;
        }
        return array_values(array_unique($ids));
    }

    // ------------------------------------------------------------------
    // 客户日志（统一时间轴：crm_touchpoint）
    // ------------------------------------------------------------------

    /**
     * 客户日志（详情页「客户日志」页签）
     *
     * 数据来自 crm_touchpoint（触点投影）；客户事件由 EventService 自动投影，
     * 客服单等新模块直接写触点。历史数据回填见迁移 047。
     */
    public function getRecords(int $customerId, int $page = 1, int $pageSize = 20, string $category = ''): array
    {
        if ($customerId <= 0) {
            return ['success' => false, 'data' => null, 'error' => '缺少客户ID'];
        }
        $exists = Db::name('crm_customer')
            ->where('store_id', $this->storeId)
            ->where('id', $customerId)
            ->find();
        if (!$exists) {
            return ['success' => false, 'data' => null, 'error' => '客户不存在'];
        }

        $data = (new TouchpointService())->forStore((int)$this->storeId)->getTimeline($customerId, [
            'page'     => $page,
            'limit'    => $pageSize,
            'category' => $category,
        ]);

        return ['success' => true, 'data' => $data, 'error' => ''];
    }
}
