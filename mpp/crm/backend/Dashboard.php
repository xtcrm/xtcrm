<?php
namespace mpp\crm\backend;

use app\platform\backend\BaseController;
use mpp\crm\model\Insight as InsightModel;
use app\platform\auth\TokenService;
use think\facade\Db;

class Dashboard extends BaseController
{
    protected $methodRules = ['index' => 'GET'];

    /** 非超管时应用当前用户过滤 */
    private function userScope($query)
    {
        $user = TokenService::getUser();
        if (empty($user['is_super'])) {
            $query->where('owner_user_id', $this->getUserId());
        }
        return $query;
    }

    public function index()
    {
        $now = time();
        $monthStart = strtotime(date('Y-m-01'));
        $sid = $this->storeId;
        $userId = $this->getUserId();

        // 漏斗各阶段客户数
        $funnel = [];
        for ($i = 1; $i <= 5; $i++) {
            $q = Db::name('crm_customer')
                ->where('store_id', $sid)->where('funnel_stage', $i)->where('is_delete', 0);
            $this->userScope($q);
            $funnel[] = [
                'stage' => $i,
                'label' => ['', '初步接触', '需求确认', '报价', '谈判', '成交'][$i],
                'count' => $q->count(),
            ];
        }

        // 待跟进客户数
        $pq = Db::name('crm_customer')
            ->where('store_id', $sid)->where('is_delete', 0)->where('status', 1)
            ->where('owner_user_id', '>', 0)
            ->where(function ($q) use ($now) {
                $q->where('last_followup_time', '<', $now - 7 * 86400)
                  ->whereOr('last_followup_time', null);
            });
        $this->userScope($pq, 'crm_customer');
        $pendingFollowup = $pq->count();

        // 本月新增客户
        $nc = Db::name('crm_customer')
            ->where('store_id', $sid)->where('is_delete', 0)
            ->where('create_time', '>=', $monthStart);
        $this->userScope($nc, 'crm_customer');
        $newCustomers = $nc->count();

        // 本月报价
        $mq = Db::name('crm_quotation')
            ->where('store_id', $sid)->where('is_delete', 0)
            ->where('create_time', '>=', $monthStart);
        $this->userScope($mq);
        $monthQuotation = $mq->sum('final_amount');

        // 本月订单
        $mo = Db::name('crm_order')
            ->where('store_id', $sid)->where('is_delete', 0)
            ->where('create_time', '>=', $monthStart)->where('status', '<>', 6);
        $this->userScope($mo);
        $monthOrder = $mo->sum('final_amount');

        // 待收款
        $up = Db::name('crm_order')
            ->where('store_id', $sid)->where('is_delete', 0)
            ->whereIn('status', [1,2,3,4])->where('payment_status', '<>', 3);
        $this->userScope($up);
        $unpaid = $up->sum('unpaid_amount');

        // 公海
        $poolCount = Db::name('crm_customer')
            ->where('store_id', $sid)->where('is_delete', 0)
            ->where('owner_user_id', 0)->count();

        // 最近跟进（非超管只看自己客户的）
        $rf = Db::name('crm_followup')->alias('f')
            ->join('crm_customer c', 'f.customer_id = c.id AND c.store_id = f.store_id')
            ->where('f.store_id', $sid)->where('f.is_delete', 0)
            ->field('f.*, c.customer_name')
            ->order(['f.create_time' => 'desc'])->limit(10);
        $user = TokenService::getUser();
        if (empty($user['is_super'])) {
            $rf->where('f.owner_user_id', $this->getUserId());
        }
        $recentFollows = $rf->select();

        // 洞察卡片（按当前用户权限过滤）
        $user = TokenService::getUser();
        $insightQuery = InsightModel::where('status', 'active')
            ->order('priority', 'desc')->order('create_time', 'desc')
            ->limit(15);
        if (empty($user['is_super'])) {
            $insightQuery->where('user_id', $userId);
        }
        $insights = $insightQuery->select()->toArray();

        // 补充 AI 建议内容和采纳状态
        $suggestionIds = array_filter(array_column($insights, 'suggestion_id'));
        $suggestions = [];
        if (!empty($suggestionIds)) {
            $rows = Db::name('crm_ai_suggestion')
                ->whereIn('id', $suggestionIds)->select()->toArray();
            foreach ($rows as $r) {
                $suggestions[$r['id']] = $r;
            }
        }
        foreach ($insights as &$insight) {
            $sid2 = $insight['suggestion_id'] ?? 0;
            if ($sid2 && isset($suggestions[$sid2])) {
                $s = $suggestions[$sid2];
                $content = json_decode($s['content'], true);
                $insight['suggestion_text'] = $content['text'] ?? '';
                $insight['suggestion_accepted'] = $s['accepted'];
                $insight['suggestion_record_id'] = $s['id'];
            } else {
                $insight['suggestion_text'] = '';
                $insight['suggestion_accepted'] = null;
                $insight['suggestion_record_id'] = 0;
            }
        }
        unset($insight);

        // 通知未读数
        $unreadCount = Db::name('crm_notification')
            ->where('user_id', $userId)->where('is_read', 0)->count();

        // 投影数据（KPI + 排名）
        $kpi = Db::name('crm_projection')
            ->where('type', 'kpi_dashboard')->where('key', 'overall')->where('store_id', $sid)
            ->order('calculated_at', 'desc')->value('data');
        $ranking = Db::name('crm_projection')
            ->where('type', 'team_ranking')->where('key', 'overall')->where('store_id', $sid)
            ->order('calculated_at', 'desc')->value('data');

        // 扫描日志：今日洞察扫描结果摘要
        $scanLog = $this->buildScanLog($sid);

        return $this->renderSuccess(compact(
            'funnel', 'pendingFollowup', 'newCustomers', 'monthQuotation',
            'monthOrder', 'unpaid', 'poolCount', 'recentFollows', 'insights', 'unreadCount',
            'kpi', 'ranking', 'scanLog'
        ));
    }

    private function buildScanLog(int $sid): array
    {
        $today = date('Y-m-d');
        // 今日洞察按规则分组统计
        $rows = Db::name('crm_insight')
            ->where('store_id', $sid)->where('create_date', $today)
            ->field('source_rule, COUNT(*) as cnt')
            ->group('source_rule')->select()->toArray();

        // 上次投影刷新时间
        $lastScan = Db::name('crm_projection')
            ->where('store_id', $sid)->order('calculated_at', 'desc')->value('calculated_at');

        $ruleNames = [
            'scanFollowupOverdue'  => ['name' => '报价逾期检测',   'desc' => '扫描报价已发送但超时未回复的客户'],
            'scanRepurchaseWindow'  => ['name' => '复购窗口检测',   'desc' => '分析历史采购周期，发现已进入复购期的客户'],
            'scanPoolWarning'       => ['name' => '公海预警',       'desc' => '检测即将因长期未跟进掉入公海的客户'],
            'scanChurnRisk'         => ['name' => '流失风险评估',   'desc' => '综合采购间隔+跟进频率，评估客户流失风险'],
            'scanDormant'           => ['name' => '沉睡客户唤醒',   'desc' => '发现曾有成交但长期未联系的沉睡客户'],
            'enrichWithAI'          => ['name' => 'AI 建议生成',    'desc' => '为高价值洞察自动生成跟进建议文案'],
        ];

        $rules = [];
        foreach ($rows as $r) {
            $info = $ruleNames[$r['source_rule']] ?? ['name' => $r['source_rule'], 'desc' => ''];
            $rules[] = [
                'name' => $info['name'],
                'desc' => $info['desc'],
                'count' => intval($r['cnt']),
            ];
        }

        return [
            'last_scan_time' => $lastScan ? date('Y-m-d H:i:s', $lastScan) : '',
            'total_insights' => array_sum(array_column($rows, 'cnt')),
            'rules' => $rules,
        ];
    }
}
