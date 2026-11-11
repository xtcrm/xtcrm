<?php
namespace mpp\crm\backend;

use app\platform\backend\BaseController;
use mpp\crm\service\DemandService;

class Demand extends BaseController
{
    protected $methodRules = [
        'customerDemands' => 'GET',
        'listAll'         => 'GET',
        'dismiss'         => 'POST',
        'setStatus'       => 'POST',
    ];

    public function listAll()
    {
        $userId = $this->getUserId();
        $isSuper = \think\facade\Db::name('store_user')->where('store_user_id', $userId)->value('is_super');
        $list = \think\facade\Db::name('crm_demand')
            ->alias('d')
            ->join('yoshop_crm_customer c', 'd.customer_id = c.id')
            ->leftJoin('yoshop_crm_demand_rule r', 'd.rule_key = r.rule_key AND r.store_id = ' . $this->storeId)
            ->leftJoin('yoshop_store_user u', 'u.store_user_id = d.owner_user_id')
            ->where('d.store_id', $this->storeId)
            ->where('d.status', 1)
            ->field('d.id, d.customer_id, d.owner_user_id, d.rule_key, d.reason, d.create_time, c.customer_name, r.rule_name, r.priority, u.real_name as owner_user_name')
            ->order('r.priority', 'asc')->order('d.create_time', 'desc')
            ->limit(50);
        if (!$isSuper) {
            $list->where('d.owner_user_id', $userId);
        }
        $demands = $list->select()->toArray();

        // 加载已保存的祝福语
        $today = date('Y-m-d');
        $greetings = \think\facade\Db::name('crm_calendar_greeting')
            ->where('store_id', $this->storeId)
            ->where('event_type', 'demand')
            ->where('event_date', $today)
            ->select()->toArray();
        $greetingMap = [];
        foreach ($greetings as $g) {
            $greetingMap[$g['target_id']] = $g['greeting'];
        }
        foreach ($demands as &$d) {
            $key = 'dm' . $d['id'];
            if (isset($greetingMap[$key])) {
                $d['_greeting'] = $greetingMap[$key];
            }
        }
        unset($d);

        return $this->renderSuccess(['list' => $demands]);
    }

    public function customerDemands()
    {
        $customerId = $this->request->param('customer_id', 0);
        $result = (new DemandService())->getByCustomer((int)$customerId);
        return $this->renderSuccess($result['data']);
    }

    public function dismiss()
    {
        if (!$this->checkAction('/crm/demand/dismiss', '忽略需求')) return;
        $id = $this->request->param('id', 0);
        $result = (new DemandService())->dismiss((int)$id);
        if ($result['success']) return $this->renderSuccess([], '已忽略');
        return $this->renderError($result['error']);
    }
 
    public function setStatus()
    {
        if (!$this->checkAction('/crm/demand/setStatus', '更新需求状态')) return;
        $id = $this->request->param('id', 0);
        $status = $this->request->param('status', 1);
        $result = (new DemandService())->setStatus((int)$id, (int)$status);
        if ($result['success']) return $this->renderSuccess([], '已更新');
        return $this->renderError($result['error']);
    }
}
