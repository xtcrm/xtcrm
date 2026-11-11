<?php
namespace mpp\crm\service;

use app\platform\BaseService;
use think\facade\Db;

/**
 * 报表聚合
 *
 * 口径统一：营收 = 实际到账（crm_payment），业绩 = 回款确认（crm_commission_record），
 * 交付 = 项目 + 审核记录。全部按 store_id 隔离，MySQL 5.7 不写窗口函数。
 */
class ReportService extends BaseService
{
    /**
     * 数据范围：老板 / 管理员 = 全公司；部门经理 = 管辖部门子树；其他人 = 仅本人
     * 报表必须按角色收窄，否则销售能看到全公司业绩。
     */
    private function scope(): array
    {
        $user = \app\platform\auth\TokenService::getUser();
        $uid = (int)($user['store_user_id'] ?? ($user['uid'] ?? 0));
        if (!empty($user['is_super']) || (int)($user['role_id'] ?? 0) === 1) {
            return ['all' => true, 'uid' => $uid, 'user_ids' => [], 'label' => '全公司'];
        }
        $rootIds = Db::name('store_department')->where('store_id', $this->storeId)
            ->where('manager_id', $uid)->where('is_delete', 0)->column('id');
        $deptIds = array_map('intval', $rootIds);
        foreach ($rootIds as $rid) {
            $this->collectDeptChildren((int)$rid, $deptIds);
        }
        if (!empty($deptIds)) {
            $userIds = Db::name('store_user')->where('store_id', $this->storeId)
                ->whereIn('department_id', $deptIds)->where('is_delete', 0)->column('store_user_id');
            $userIds[] = $uid;
            return ['all' => false, 'uid' => $uid,
                'user_ids' => array_values(array_unique(array_map('intval', $userIds))),
                'label' => '我管辖的部门'];
        }
        return ['all' => false, 'uid' => $uid, 'user_ids' => [$uid], 'label' => '仅本人'];
    }

    private function collectDeptChildren(int $parentId, array &$ids): void
    {
        $children = Db::name('store_department')->where('store_id', $this->storeId)
            ->where('parent_id', $parentId)->where('is_delete', 0)->column('id');
        foreach ($children as $cid) {
            $ids[] = (int)$cid;
            $this->collectDeptChildren((int)$cid, $ids);
        }
    }

    // ------------------------------------------------------------------
    // 经营总览
    // ------------------------------------------------------------------
    public function overview(int $storeId): array
    {
        $month = date('Y-m');
        $monthStart = strtotime(date('Y-m-01'));
        $lastStart = strtotime(date('Y-m-01', strtotime('-1 month')));

        $revenue = (float)Db::name('crm_payment')->where('store_id', $storeId)->where('is_delete', 0)
            ->where('paid_at', '>=', $monthStart)->sum('amount');
        $lastRevenue = (float)Db::name('crm_payment')->where('store_id', $storeId)->where('is_delete', 0)
            ->where('paid_at', '>=', $lastStart)->where('paid_at', '<', $monthStart)->sum('amount');
        $scope = $this->scope();
        $cQuery = Db::name('crm_commission_record')->where('store_id', $storeId)->where('confirm_month', $month);
        if (!$scope['all']) $cQuery->whereIn('user_id', $scope['user_ids']);
        $commission = (float)(clone $cQuery)->sum('amount');
        $byBiz = (clone $cQuery)->field('biz_type, SUM(amount) AS amount, COUNT(*) AS cnt')
            ->group('biz_type')->select()->toArray();

        $newCustomers = Db::name('crm_customer')->where('store_id', $storeId)->where('is_delete', 0)
            ->where('create_time', '>=', $monthStart)->count();
        $poolCount = Db::name('crm_customer')->where('store_id', $storeId)->where('is_delete', 0)
            ->where('owner_user_id', 0)->count();
        $pendingCs = Db::name('crm_customer')->where('store_id', $storeId)->where('is_delete', 0)
            ->where('owner_user_id', '>', 0)->where('cs_user_id', 0)->count();
        $receivable = (float)Db::name('crm_order')->where('store_id', $storeId)->where('is_delete', 0)
            ->where('status', '<>', 6)->sum('unpaid_amount');

        // 客户资产结构（A/B/C 级占比）
        $levels = Db::name('crm_customer')->where('store_id', $storeId)->where('is_delete', 0)
            ->where('status', 1)
            ->field('IFNULL(level_name, "" ) AS level_name, COUNT(*) AS cnt')
            ->group('level_name')->order('cnt', 'desc')->select()->toArray();

        return ['success' => true, 'data' => [
            'month' => $month,
            'scope' => $scope['label'],
            'finance_visible' => $scope['all'],
            'revenue' => $scope['all'] ? $revenue : null,
            'last_revenue' => $lastRevenue,
            'revenue_growth' => $lastRevenue > 0 ? round(($revenue - $lastRevenue) / $lastRevenue * 100, 1) : null,
            'commission' => $commission,
            'by_biz' => $byBiz,
            'new_customers' => $newCustomers,
            'pool_count' => $poolCount,
            'pending_cs' => $pendingCs,
            'receivable' => $receivable,
            'levels' => $levels,
        ]];
    }

    // ------------------------------------------------------------------
    // 营收趋势（近 12 个月：回款 + 业绩）
    // ------------------------------------------------------------------
    public function revenueTrend(int $storeId): array
    {
        $rows = [];
        $base = strtotime(date('Y-m-01'));
        for ($i = 11; $i >= 0; $i--) {
            $m = date('Y-m', strtotime("-{$i} months", $base));
            $start = strtotime($m . '-01');
            $end = strtotime($m . '-01 +1 month');
            $revenue = (float)Db::name('crm_payment')->where('store_id', $storeId)->where('is_delete', 0)
                ->where('paid_at', '>=', $start)->where('paid_at', '<', $end)->sum('amount');
            $commission = (float)Db::name('crm_commission_record')->where('store_id', $storeId)
                ->where('confirm_month', $m)->sum('amount');
            $rows[] = ['month' => $m, 'revenue' => $revenue, 'commission' => $commission];
        }
        return ['success' => true, 'data' => $rows];
    }

    // ------------------------------------------------------------------
    // 业绩排行（人 / 岗位）
    // ------------------------------------------------------------------
    public function ranking(int $storeId, string $month = ''): array
    {
        $month = $month ?: date('Y-m');
        $scope = $this->scope();
        $query = Db::name('crm_commission_record')->where('store_id', $storeId)
            ->where('confirm_month', $month);
        if (!$scope['all']) $query->whereIn('user_id', $scope['user_ids']);
        $rows = $query->field('user_id, post_code, SUM(amount) AS amount, COUNT(*) AS cnt')
            ->group('user_id, post_code')->order('amount', 'desc')->select()->toArray();

        $userIds = array_values(array_unique(array_column($rows, 'user_id')));
        $users = empty($userIds) ? [] : Db::name('store_user')->where('store_id', $storeId)
            ->whereIn('store_user_id', $userIds)->column('user_name,real_name,department_id', 'store_user_id');
        $deptIds = array_values(array_unique(array_filter(array_column($users, 'department_id'))));
        $depts = empty($deptIds) ? [] : Db::name('store_department')->where('store_id', $storeId)
            ->whereIn('id', $deptIds)->column('department_name', 'id');

        $byPost = [];
        foreach ($rows as &$r) {
            $u = $users[(int)$r['user_id']] ?? [];
            $r['user_name'] = (string)(($u['real_name'] ?? '') ?: ($u['user_name'] ?? ('#' . $r['user_id'])));
            $r['department_name'] = $depts[(int)($u['department_id'] ?? 0)] ?? '';
            $post = $r['post_code'] ?: 'other';
            $byPost[$post] = ($byPost[$post] ?? 0) + (float)$r['amount'];
        }
        unset($r);

        return ['success' => true, 'data' => ['month' => $month, 'scope' => $scope['label'], 'list' => $rows, 'by_post' => $byPost]];
    }

    // ------------------------------------------------------------------
    // 销售漏斗
    // ------------------------------------------------------------------
    public function funnel(int $storeId): array
    {
        $stages = [1 => '初步接触', 2 => '需求确认', 3 => '报价', 4 => '谈判', 5 => '成交'];
        $scope = $this->scope();
        $fq = Db::name('crm_customer')->where('store_id', $storeId)->where('is_delete', 0)->where('status', 1);
        if (!$scope['all']) {
            $fq->where(function ($q) use ($scope) {
                $q->whereIn('owner_user_id', $scope['user_ids'])
                  ->whereOr('cs_user_id', 'in', $scope['user_ids']);
            });
        }
        $raw = $fq->field('funnel_stage, COUNT(*) AS cnt')
            ->group('funnel_stage')->select()->toArray();
        $cnts = [];
        foreach ($raw as $r) { $cnts[(int)$r['funnel_stage']] = (int)$r['cnt']; }

        $rows = [];
        $prev = 0;
        foreach ($stages as $i => $name) {
            $cnt = (int)($cnts[$i] ?? 0);
            $rows[] = [
                'stage' => $i,
                'name' => $name,
                'count' => $cnt,
                'conv_from_prev' => $prev > 0 ? round($cnt / $prev * 100, 1) : null,
            ];
            $prev = $cnt;
        }
        return ['success' => true, 'data' => $rows];
    }

    // ------------------------------------------------------------------
    // 报价成交率
    // ------------------------------------------------------------------
    public function quotation(int $storeId, string $startMonth = '', string $endMonth = ''): array
    {
        $start = $startMonth ? strtotime($startMonth . '-01') : strtotime(date('Y-m-01', strtotime('-5 months')));
        $end = $endMonth ? strtotime($endMonth . '-01 +1 month') : strtotime(date('Y-m-01 +1 month'));

        $total = Db::name('crm_quotation')->where('store_id', $storeId)->where('is_delete', 0)
            ->where('create_time', '>=', $start)->where('create_time', '<', $end)->count();
        $won = Db::name('crm_order')->where('store_id', $storeId)->where('is_delete', 0)
            ->where('quotation_id', '>', 0)->where('status', '<>', 6)
            ->where('create_time', '>=', $start)->where('create_time', '<', $end)->count();
        $wonAmount = (float)Db::name('crm_order')->where('store_id', $storeId)->where('is_delete', 0)
            ->where('quotation_id', '>', 0)->where('status', '<>', 6)
            ->where('create_time', '>=', $start)->where('create_time', '<', $end)->sum('final_amount');
        $avgRow = Db::name('crm_order')->alias('o')
            ->join(Db::name('crm_quotation')->getTable() . ' q', 'q.id = o.quotation_id AND q.store_id = o.store_id')
            ->where('o.store_id', $storeId)->where('o.is_delete', 0)->where('o.status', '<>', 6)
            ->where('o.create_time', '>=', $start)->where('o.create_time', '<', $end)
            ->field('AVG(o.create_time - q.create_time) AS avg_sec')->find();
        $avgDays = $avgRow['avg_sec'] ?? null;

        return ['success' => true, 'data' => [
            'quotation_count' => $total,
            'won_count' => $won,
            'win_rate' => $total > 0 ? round($won / $total * 100, 1) : null,
            'won_amount' => $wonAmount,
            'avg_cycle_days' => $avgDays ? round($avgDays / 86400, 1) : null,
        ]];
    }

    // ------------------------------------------------------------------
    // 续费到期（未来 N 天）
    // ------------------------------------------------------------------
    public function renewal(int $storeId, int $days = 90): array
    {
        $now = time();
        $end = $now + $days * 86400;
        $scope = $this->scope();
        $rq = Db::name('crm_project')->where('store_id', $storeId)->where('is_delete', 0)
            ->where('status', 2)
            ->where('service_end_time', '>', $now)->where('service_end_time', '<=', $end);
        if (!$scope['all']) {
            $rq->where(function ($q) use ($scope) {
                $q->whereIn('cs_user_id', $scope['user_ids'])
                  ->whereOr('owner_user_id', 'in', $scope['user_ids']);
            });
        }
        $list = $rq->field('id,project_no,title,customer_id,owner_user_id,cs_user_id,service_end_time')
            ->order('service_end_time', 'asc')->limit(200)->select()->toArray();

        $customerIds = array_values(array_unique(array_column($list, 'customer_id')));
        $customers = empty($customerIds) ? [] : Db::name('crm_customer')->where('store_id', $storeId)
            ->whereIn('id', $customerIds)->column('customer_name', 'id');
        $userIds = array_values(array_unique(array_merge(
            array_column($list, 'owner_user_id'), array_column($list, 'cs_user_id')
        )));
        $users = empty($userIds) ? [] : Db::name('store_user')->where('store_id', $storeId)
            ->whereIn('store_user_id', $userIds)->column('user_name,real_name', 'store_user_id');

        foreach ($list as &$row) {
            $row['customer_name'] = $customers[(int)$row['customer_id']] ?? '';
            $row['cs_user_name'] = $this->nameOf($users, (int)$row['cs_user_id']);
            $row['owner_user_name'] = $this->nameOf($users, (int)$row['owner_user_id']);
            $row['days_left'] = (int)ceil(((int)$row['service_end_time'] - $now) / 86400);
        }
        unset($row);
        return ['success' => true, 'data' => ['days' => $days, 'scope' => $scope['label'], 'list' => $list, 'count' => count($list)]];
    }

    // ------------------------------------------------------------------
    // 交付与审核
    // ------------------------------------------------------------------
    public function delivery(int $storeId): array
    {
        $monthStart = strtotime(date('Y-m-01'));
        $completed = Db::name('crm_project')->where('store_id', $storeId)->where('is_delete', 0)
            ->where('status', 3)->where('completed_at', '>=', $monthStart)->count();
        $onTime = Db::name('crm_project')->where('store_id', $storeId)->where('is_delete', 0)
            ->where('status', 3)->where('completed_at', '>=', $monthStart)
            ->where('service_end_time', '>', 0)
            ->whereColumn('completed_at', '<=', 'service_end_time')->count();

        $reviews = Db::name('crm_project_review')->where('store_id', $storeId)
            ->where('result', '<>', 0)->where('review_time', '>=', $monthStart)
            ->field('result, review_time, submit_time, assign_time, reason')->select()->toArray();

        $pass = 0; $reject = 0; $sumHours = 0; $sumResp = 0; $reasons = [];
        foreach ($reviews as $r) {
            $r['result'] == 1 ? $pass++ : $reject++;
            if ($r['review_time'] > $r['submit_time']) $sumHours += ($r['review_time'] - $r['submit_time']) / 3600;
            if ($r['assign_time'] > 0 && $r['review_time'] > $r['assign_time']) {
                $sumResp += ($r['review_time'] - $r['assign_time']) / 3600;
            }
            if ($r['result'] == 2 && $r['reason'] !== '') {
                $reasons[$r['reason']] = ($reasons[$r['reason']] ?? 0) + 1;
            }
        }
        arsort($reasons);
        $totalReviews = $pass + $reject;
        $pending = Db::name('crm_project_review')->where('store_id', $storeId)->where('result', 0)->count();

        return ['success' => true, 'data' => [
            'completed' => $completed,
            'on_time_rate' => $completed > 0 ? round($onTime / $completed * 100, 1) : null,
            'review_count' => $totalReviews,
            'pass_count' => $pass,
            'reject_count' => $reject,
            'reject_rate' => $totalReviews > 0 ? round($reject / $totalReviews * 100, 1) : null,
            'avg_review_hours' => $totalReviews > 0 ? round($sumHours / $totalReviews, 1) : null,
            'avg_response_hours' => $totalReviews > 0 ? round($sumResp / $totalReviews, 1) : null,
            'pending_reviews' => $pending,
            'reasons' => array_slice($reasons, 0, 5, true),
        ]];
    }

    // ------------------------------------------------------------------
    // 应收账龄
    // ------------------------------------------------------------------
    public function receivable(int $storeId): array
    {
        $orders = Db::name('crm_order')->where('store_id', $storeId)->where('is_delete', 0)
            ->where('status', '<>', 6)->where('unpaid_amount', '>', 0)
            ->field('id,customer_id,order_no,unpaid_amount,order_date,create_time')
            ->select()->toArray();

        $now = time();
        $buckets = [
            ['key' => 'd30',  'label' => '30 天内',  'amount' => 0, 'count' => 0],
            ['key' => 'd60',  'label' => '31–60 天', 'amount' => 0, 'count' => 0],
            ['key' => 'd90',  'label' => '61–90 天', 'amount' => 0, 'count' => 0],
            ['key' => 'd90p', 'label' => '90 天以上', 'amount' => 0, 'count' => 0],
        ];
        foreach ($orders as $o) {
            $t = (int)($o['order_date'] ?: $o['create_time'] ?: $now);
            $days = (int)floor(($now - $t) / 86400);
            $idx = $days <= 30 ? 0 : ($days <= 60 ? 1 : ($days <= 90 ? 2 : 3));
            $buckets[$idx]['amount'] += (float)$o['unpaid_amount'];
            $buckets[$idx]['count']++;
        }

        $top = $orders;
        usort($top, function ($a, $b) { return (float)$b['unpaid_amount'] <=> (float)$a['unpaid_amount']; });
        $top = array_slice($top, 0, 10);
        $customerIds = array_values(array_unique(array_column($top, 'customer_id')));
        $customers = empty($customerIds) ? [] : Db::name('crm_customer')->where('store_id', $storeId)
            ->whereIn('id', $customerIds)->column('customer_name', 'id');
        foreach ($top as &$o) {
            $o['customer_name'] = $customers[(int)$o['customer_id']] ?? '';
        }
        unset($o);

        return ['success' => true, 'data' => ['buckets' => $buckets, 'top' => $top,
            'total' => array_sum(array_column($buckets, 'amount'))]];
    }

    private function nameOf(array $users, int $userId): string
    {
        if ($userId <= 0 || !isset($users[$userId])) return '';
        $u = $users[$userId];
        return (string)(($u['real_name'] ?? '') ?: ($u['user_name'] ?? ''));
    }
}
