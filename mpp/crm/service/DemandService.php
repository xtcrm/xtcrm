<?php
namespace mpp\crm\service;

use app\platform\BaseService;
use mpp\crm\model\Customer as CustomerModel;
use mpp\crm\model\Demand as DemandModel;
use mpp\crm\model\DemandRule as DemandRuleModel;

class DemandService extends BaseService
{
    public function evaluateCustomer(int $customerId, int $storeId): array
    {
        // 用 Db::name 而非 Model，避免 CLI 下全局 scope 的 store_id 过滤
        $customer = \think\facade\Db::name('crm_customer')->where('id', $customerId)->find();
        if (!$customer) return [];

        $evaluator = new DemandRuleService();
        $hits = $evaluator->evaluate($customer);

        $demandModel = new DemandModel();
        $created = [];
        foreach ($hits as $hit) {
            // 已存在该规则的需求（不管什么状态）都不重复创建
            // status=1 活跃、status=3 已忽略、status=5 已过期——只要同一 rule_key 就跳过
            // 使用 Db::name 直接查，回避 Model scope 的潜在问题
            $storeIdForCheck = $customer['store_id'] ?? $storeId;
            $existing = \think\facade\Db::name('crm_demand')
                ->where('store_id', $storeIdForCheck)
                ->where('customer_id', $customerId)
                ->where('rule_key', $hit['rule_key'])
                ->find();
            if ($existing) continue;

            $data = [
                'store_id' => $customer['store_id'] ?? $storeId,
                'customer_id' => $customerId,
                'rule_key' => $hit['rule_key'],
                'source' => 'rule',
                'confidence' => 1.0,
                'reason' => $hit['reason'],
                'status' => 1,
                'owner_user_id' => $customer['owner_user_id'] ?? 0,
                'create_time' => time(),
            ];
            $demandModel->data($data, true)->save($data);
            $created[] = $hit['rule_key'];
        }

        // Resolve stale demands (no longer matching any rule)
        $activeRuleKeys = array_column($hits, 'rule_key');
        if (!empty($activeRuleKeys)) {
            $demandModel->where('customer_id', $customerId)
                ->where('status', 1)
                ->whereNotIn('rule_key', $activeRuleKeys)
                ->update(['status' => 5, 'update_time' => time()]);
        }

        return $created;
    }

    public function dailyScan(int $storeId): int
    {
        $customers = \think\facade\Db::name('crm_customer')
            ->where('store_id', $storeId)
            ->where('is_delete', 0)
            ->where('status', 1)
            ->field('id')
            ->select()
            ->toArray();

        $totalHits = 0;
        foreach ($customers as $cust) {
            $hits = $this->evaluateCustomer((int)$cust['id'], $storeId);
            $totalHits += count($hits);
        }
        return $totalHits;
    }

    public function getByCustomer(int $customerId): array
    {
        $demandModel = new DemandModel();
        $demands = $demandModel->getCustomerDemands($customerId);

        $ruleModel = new DemandRuleModel();
        $allRules = $ruleModel->where('store_id', $this->storeId ?? 0)
            ->select()
            ->toArray();
        $ruleMap = [];
        foreach ($allRules as $r) {
            $ruleMap[$r['rule_key']] = $r;
        }

        foreach ($demands as &$d) {
            $rule = $ruleMap[$d['rule_key']] ?? null;
            $d['rule_name'] = $rule['rule_name'] ?? $d['rule_key'];
            $d['priority'] = $rule['priority'] ?? 2;
        }

        return ['success' => true, 'data' => ['list' => $demands], 'error' => ''];
    }

    public function dismiss(int $demandId): array
    {
        $model = new DemandModel();
        $row = $model->where('id', $demandId)->find();
        if (!$row) return ['success' => false, 'data' => [], 'error' => '需求不存在'];
        $row->save(['status' => 3, 'update_time' => time()]);
        return ['success' => true, 'data' => [], 'error' => ''];
    }

    public function setStatus(int $demandId, int $status): array
    {
        $model = new DemandModel();
        $row = $model->where('id', $demandId)->find();
        if (!$row) return ['success' => false, 'data' => [], 'error' => '需求不存在'];
        $row->save(['status' => $status, 'update_time' => time()]);
        return ['success' => true, 'data' => [], 'error' => ''];
    }
}
