<?php
declare(strict_types=1);

namespace mpp\crm\api;

use mpp\crm\model\Insight as InsightModel;
use think\facade\Db;

/**
 * 移动端工作台
 * URL: /api/crm.dashboard/index
 */
class Dashboard extends Base
{
    protected $methodRules = ['index' => 'GET'];

    public function index()
    {
        $now = time();
        $monthStart = strtotime(date('Y-m-01'));
        $sid = $this->storeId;
        $userId = $this->userId;

        // 漏斗
        $funnel = [];
        $stageLabels = ['', '初步接触', '需求确认', '报价', '谈判', '成交'];
        for ($i = 1; $i <= 5; $i++) {
            $funnel[] = [
                'stage' => $i,
                'label' => $stageLabels[$i],
                'count' => Db::name('crm_customer')
                    ->where('store_id', $sid)->where('funnel_stage', $i)->where('is_delete', 0)->count(),
            ];
        }

        // 待跟进
        $pendingFollowup = Db::name('crm_customer')
            ->where('store_id', $sid)->where('is_delete', 0)->where('status', 1)
            ->where('owner_user_id', '>', 0)
            ->where(function ($q) use ($now) {
                $q->where('last_followup_time', '<', $now - 7 * 86400)
                  ->whereOr('last_followup_time', null);
            })->count();

        // 本月新增
        $newCustomers = Db::name('crm_customer')
            ->where('store_id', $sid)->where('is_delete', 0)
            ->where('create_time', '>=', $monthStart)->count();

        // 本月报价
        $monthQuotation = Db::name('crm_quotation')
            ->where('store_id', $sid)->where('is_delete', 0)
            ->where('create_time', '>=', $monthStart)->sum('final_amount');

        // 本月订单
        $monthOrder = Db::name('crm_order')
            ->where('store_id', $sid)->where('is_delete', 0)
            ->where('create_time', '>=', $monthStart)->where('status', '<>', 6)->sum('final_amount');

        // 公海
        $poolCount = Db::name('crm_customer')
            ->where('store_id', $sid)->where('is_delete', 0)->where('owner_user_id', 0)->count();

        // 最近跟进（管理员看全部，其他人只看自己的）
        $recentFollows = Db::name('crm_followup')
            ->alias('f')
            ->join('yoshop_crm_customer c', 'f.customer_id = c.id AND c.store_id = f.store_id')
            ->where('f.store_id', $sid)->where('f.is_delete', 0)
            ->where(function ($q) use ($userId) {
                // 非管理员只看自己的跟进
                if (empty($this->store['is_super']) && ($this->store['role_id'] ?? 0) !== 1) {
                    $q->where('f.owner_user_id', $userId);
                }
            })
            ->field('f.*, c.customer_name')
            ->order(['f.create_time' => 'desc'])->limit(10)->select();

        // 洞察
        $insightQuery = InsightModel::where('yoshop_crm_insight.store_id', $sid)
            ->where('status', 'active')->order('priority', 'desc')->order('create_time', 'desc')->limit(5);
        if (empty($this->store['is_super']) && ($this->store['role_id'] ?? 0) !== 1) {
            $insightQuery->where('user_id', $userId);
        }
        $insights = $insightQuery->select()->toArray();

        // 未读通知
        $unreadCount = Db::name('crm_notification')
            ->where('user_id', $userId)->where('is_read', 0)->count();

        return $this->renderSuccess(compact(
            'funnel', 'pendingFollowup', 'newCustomers',
            'monthQuotation', 'monthOrder', 'poolCount',
            'recentFollows', 'insights', 'unreadCount'
        ));
    }
}
