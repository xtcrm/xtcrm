<?php
namespace mpp\crm\service;

use app\platform\BaseService;
use mpp\crm\model\Projection as ProjectionModel;
use think\facade\Db;

class ProjectionService extends BaseService
{
    /** 刷新某租户的全部投影 */
    public function refresh(int $storeId): array
    {
        $stats = [];
        $now = time();

        $this->refreshFunnel($storeId, $now);
        $stats[] = 'funnel_stats';

        $this->refreshUserPerformance($storeId, $now);
        $stats[] = 'user_performance';

        $this->refreshCustomerHealth($storeId, $now);
        $stats[] = 'customer_health';

        $this->refreshTeamRanking($storeId, $now);
        $stats[] = 'team_ranking';

        $this->refreshRevenueTrend($storeId, $now);
        $stats[] = 'revenue_trend';

        $this->refreshKpiDashboard($storeId, $now);
        $stats[] = 'kpi_dashboard';

        return $stats;
    }

    /** 漏斗各阶段客户数 */
    protected function refreshFunnel(int $storeId, int $now): void
    {
        $rows = Db::name('crm_customer')
            ->where('store_id', $storeId)->where('is_delete', 0)->where('status', 1)
            ->field('funnel_stage, COUNT(*) as cnt')
            ->group('funnel_stage')->select()->toArray();

        $stages = [1 => '初步接触', 2 => '需求确认', 3 => '报价', 4 => '谈判', 5 => '成交'];
        $data = ['stages' => []];
        $total = 0;
        foreach ($rows as $r) {
            $stage = intval($r['funnel_stage']);
            $data['stages'][$stage] = ['name' => $stages[$stage] ?? "阶段{$stage}", 'count' => intval($r['cnt'])];
            $total += intval($r['cnt']);
        }
        $data['total'] = $total;

        $this->saveProjection('funnel_stats', 'overall', $data, $storeId, $now);
    }

    /** 业务员业绩 */
    protected function refreshUserPerformance(int $storeId, int $now): void
    {
        // 本月订单按负责人汇总
        $monthStart = strtotime(date('Y-m-01'));
        $rows = Db::name('crm_order')
            ->where('store_id', $storeId)->where('is_delete', 0)
            ->where('create_time', '>=', $monthStart)
            ->field('owner_user_id, COUNT(*) as order_count, SUM(final_amount) as total_amount')
            ->group('owner_user_id')->select()->toArray();

        foreach ($rows as $r) {
            $data = [
                'order_count' => intval($r['order_count']),
                'total_amount' => floatval($r['total_amount']),
                'month' => date('Y-m'),
            ];
            $this->saveProjection('user_performance', 'user:' . $r['owner_user_id'], $data, $storeId, $now);
        }
    }

    /** 客户健康度分布 */
    protected function refreshCustomerHealth(int $storeId, int $now): void
    {
        // 简化健康度：基于跟进频率评分
        $customers = Db::name('crm_customer')
            ->where('store_id', $storeId)->where('is_delete', 0)->where('status', 1)
            ->field('id, owner_user_id, last_followup_time, create_time')
            ->select()->toArray();

        $distribution = ['优' => 0, '良' => 0, '差' => 0, '新' => 0];
        foreach ($customers as $c) {
            $daysSinceCreated = intval(($now - $c['create_time']) / 86400);
            if ($daysSinceCreated < 30) { $distribution['新']++; continue; }

            $lastFollowup = $c['last_followup_time'] ?: $c['create_time'];
            $daysSinceFollowup = intval(($now - $lastFollowup) / 86400);

            if ($daysSinceFollowup <= 7) $distribution['优']++;
            elseif ($daysSinceFollowup <= 30) $distribution['良']++;
            else $distribution['差']++;
        }

        $this->saveProjection('customer_health', 'overall', $distribution, $storeId, $now);
    }

    /** 团队排名 */
    protected function refreshTeamRanking(int $storeId, int $now): void
    {
        $monthStart = strtotime(date('Y-m-01'));
        $rows = Db::name('crm_order')
            ->where('store_id', $storeId)->where('is_delete', 0)
            ->where('create_time', '>=', $monthStart)
            ->field('owner_user_id, SUM(final_amount) as total_amount')
            ->group('owner_user_id')
            ->order('total_amount', 'desc')->select()->toArray();

        $ranking = [];
        foreach ($rows as $i => $r) {
            $user = Db::name('store_user')
                ->where('store_user_id', $r['owner_user_id'])
                ->field('real_name, user_name')->find();
            $ranking[] = [
                'rank' => $i + 1,
                'user_id' => intval($r['owner_user_id']),
                'name' => $user['real_name'] ?? $user['user_name'] ?? '未知',
                'amount' => floatval($r['total_amount']),
                'month' => date('Y-m'),
            ];
        }

        $this->saveProjection('team_ranking', 'overall', $ranking, $storeId, $now);
    }

    /** 近12个月营收趋势 */
    protected function refreshRevenueTrend(int $storeId, int $now): void
    {
        $trend = [];
        $base = strtotime(date('Y-m-01'));
        for ($i = 11; $i >= 0; $i--) {
            $m = date('Y-m', strtotime("-{$i} months", $base));
            $start = strtotime($m . '-01');
            $end = strtotime($m . '-01 +1 month');
            $amount = Db::name('crm_order')
                ->where('store_id', $storeId)->where('is_delete', 0)
                ->where('create_time', '>=', $start)
                ->where('create_time', '<', $end)
                ->sum('final_amount');
            $trend[] = ['month' => $m, 'amount' => floatval($amount ?: 0)];
        }

        $this->saveProjection('revenue_trend', 'overall', $trend, $storeId, $now);
    }

    /** 本月 KPI */
    protected function refreshKpiDashboard(int $storeId, int $now): void
    {
        $monthStart = strtotime(date('Y-m-01'));
        $lastMonthStart = strtotime(date('Y-m-01', strtotime('-1 month')));

        $thisMonth = Db::name('crm_order')
            ->where('store_id', $storeId)->where('is_delete', 0)
            ->where('create_time', '>=', $monthStart)
            ->field('COUNT(*) as cnt, SUM(final_amount) as amount')->find();

        $lastMonth = Db::name('crm_order')
            ->where('store_id', $storeId)->where('is_delete', 0)
            ->where('create_time', '>=', $lastMonthStart)->where('create_time', '<', $monthStart)
            ->field('COUNT(*) as cnt, SUM(final_amount) as amount')->find();

        $newCustomers = Db::name('crm_customer')
            ->where('store_id', $storeId)->where('is_delete', 0)
            ->where('create_time', '>=', $monthStart)->count();

        $data = [
            'month' => date('Y-m'),
            'order_count' => intval($thisMonth['cnt'] ?? 0),
            'revenue' => floatval($thisMonth['amount'] ?? 0),
            'last_month_revenue' => floatval($lastMonth['amount'] ?? 0),
            'new_customers' => $newCustomers,
        ];

        $this->saveProjection('kpi_dashboard', 'overall', $data, $storeId, $now);
    }

    protected function saveProjection(string $type, string $key, $data, int $storeId, int $calculatedAt): void
    {
        $exists = ProjectionModel::where('type', $type)->where('key', $key)
            ->where('store_id', $storeId)->find();
        if ($exists) {
            $exists->data = json_encode($data, JSON_UNESCAPED_UNICODE);
            $exists->calculated_at = $calculatedAt;
            $exists->save();
        } else {
            $model = new ProjectionModel();
            $model->type = $type;
            $model->key = $key;
            $model->data = json_encode($data, JSON_UNESCAPED_UNICODE);
            $model->calculated_at = $calculatedAt;
            $model->store_id = $storeId;
            $model->save();
        }
    }
}
