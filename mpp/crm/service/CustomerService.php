<?php
namespace mpp\crm\service;

use app\platform\BaseService;
use mpp\crm\model\Customer as CustomerModel;
use app\platform\auth\TokenService;
use app\common\service\AiService;

class CustomerService extends BaseService
{
    /** 列表 */
    public function getList(array $params): array
    {
        $model = new CustomerModel();
        CustomerModel::setCurrentUser(TokenService::getUser());
        $list = $model->getList($params);
        return ['success' => true, 'data' => compact('list'), 'error' => ''];
    }

    /** 下拉选择 */
    public function select(): array
    {
        $model = new CustomerModel();
        $list = $model->select();
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
        return ['success' => true, 'data' => compact('list'), 'error' => ''];
    }

    /** 协作列表 */
    public function getCollabList(array $params): array
    {
        $model = new CustomerModel();
        CustomerModel::setCurrentUser(TokenService::getUser());
        $list = $model->getCollabList($params);
        return ['success' => true, 'data' => compact('list'), 'error' => ''];
    }

    /** 新增 */
    public function add(array $data, int $actorUserId): array
    {
        $model = new CustomerModel();
        if ($model->add($data)) {
            EventService::record('customer_created', 'customer', $model->id, [
                'customer_name' => $data['customer_name'] ?? '',
                'source' => $data['source'] ?? '',
                'industry' => $data['industry'] ?? '',
                'owner_id' => $data['owner_user_id'] ?? 0,
                'creator_id' => $data['creator_user_id'] ?? 0,
            ], $actorUserId);
            return ['success' => true, 'data' => ['id' => $model->id], 'error' => ''];
        }
        return ['success' => false, 'data' => null, 'error' => $model->getError() ?: '添加失败'];
    }

    /** 编辑 */
    public function edit(int $id, array $data, int $actorUserId): array
    {
        $model = new CustomerModel();
        $detail = $model->detail($id);
        if (!$detail) return ['success' => false, 'data' => null, 'error' => '客户不存在'];
        // 检测 owner 变更
        $oldOwner = $detail['owner_user_id'] ?? 0;
        if ($detail->edit($data)) {
            EventService::record('customer_updated', 'customer', $id, [
                'changed_fields' => array_keys($data),
            ], $actorUserId);
            $newOwner = $data['owner_user_id'] ?? $oldOwner;
            if ($oldOwner != $newOwner) {
                EventService::record('customer_updated', 'customer', $id, [
                    'owner_changed' => true, 'from_owner' => $oldOwner, 'to_owner' => $newOwner,
                ], $actorUserId);
            }
            return ['success' => true, 'data' => null, 'error' => ''];
        }
        return ['success' => false, 'data' => null, 'error' => $detail->getError() ?: '更新失败'];
    }

    /** 删除 */
    public function delete(int $id, int $actorUserId): array
    {
        $model = new CustomerModel();
        $detail = $model->detail($id);
        if (!$detail) return ['success' => false, 'data' => null, 'error' => '客户不存在'];
        if ($detail->setDelete($id)) {
            EventService::record('customer_updated', 'customer', $id, ['is_delete' => true], $actorUserId);
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
            try { InsightService::handleEvent('customer_released', $id); } catch (\Throwable $e) {}
            return ['success' => true, 'data' => null, 'error' => ''];
        }
        return ['success' => false, 'data' => null, 'error' => '释放失败'];
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
}
