<?php
namespace mpp\crm\backend;

use app\platform\backend\BaseController;
use mpp\crm\model\Insight as InsightModel;
use mpp\crm\service\InsightService;
use app\platform\auth\TokenService;

class Insight extends BaseController
{
    protected $methodRules = [
        'list' => 'GET',
        'dismiss' => 'POST',
        'runNow' => 'POST',
    ];

    /** 获取当前用户的活跃洞察（智能面板数据源） */
    public function list()
    {
        $userId = $this->store['store_user_id'] ?? ($this->store['uid'] ?? 0);
        $user = TokenService::getUser();

        // 复用现有权限逻辑：管理员看全部，经理看部门，业务员看自己
        $query = InsightModel::where('status', 'active')
            ->where('create_date', date('Y-m-d'))
            ->order('priority', 'desc')
            ->order('create_time', 'desc')
            ->limit(20);

        // 管理员不过滤
        if (empty($user['is_super'])) {
            $query->where('user_id', $userId);
        }

        $list = $query->select()->toArray();
        return $this->renderSuccess(compact('list'));
    }

    /** 忽略/关闭一条洞察 */
    public function dismiss()
    {
        $id = $this->request->param('id', 0);
        $reason = $this->request->param('reason', '');
        $insight = InsightModel::where('id', $id)->find();
        if (!$insight) return $this->renderError('洞察不存在');

        $insight->status = 'dismissed';
        $insight->dismissed_reason = $reason;
        $insight->update_time = time();
        $insight->save();

        return $this->renderSuccess([], '已忽略');
    }

    /** 手动触发扫描（调试用） */
    public function runNow()
    {
        $user = TokenService::getUser();
        if (empty($user['is_super'])) return $this->renderError('仅管理员可手动执行');

        $storeId = $this->storeId;
        $stats = (new InsightService())->dailyScan($storeId);
        return $this->renderSuccess($stats, '扫描完成');
    }
}
