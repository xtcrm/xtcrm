<?php
declare(strict_types=1);

namespace mpp\crm\api;

use mpp\crm\service\FollowUpService;

/**
 * 移动端跟进记录
 * URL: /api/crm.followUp/lists
 */
class FollowUp extends Base
{
    protected $methodRules = [
        'lists'          => 'GET',
        'add'            => 'POST',
        'todayVisits'    => 'GET',
        'weekVisits'     => 'GET',
        'calendarEvents' => 'GET',
        'greeting'       => 'POST',
    ];

    /** GET /api/crm.followUp/lists */
    public function lists()
    {
        $customerId = (int)$this->request->param('customer_id', 0);
        $result = (new FollowUpService())->getList($customerId);
        return $this->renderSuccess($result['data'] ?: []);
    }

    /** POST /api/crm.followUp/add */
    public function add()
    {
        $data = $this->postData();
        if (empty($data['customer_id'])) {
            return $this->renderError('参数错误：缺少客户ID');
        }
        $result = (new FollowUpService())->add($data, $this->getUserId());
        if ($result['success']) {
            return $this->renderSuccess($result['data'] ?: [], '添加成功');
        }
        return $this->renderError($result['error']);
    }

    /** 今日拜访 */
    public function todayVisits()
    {
        $userId = $this->resolveUserId(intval($this->request->param('user_id', 0)));
        return $this->renderSuccess(FollowUpService::todayVisits($this->storeId, $userId));
    }

    /** 周拜访 */
    public function weekVisits()
    {
        $start  = $this->request->param('start', '');
        $end    = $this->request->param('end', '');
        $userId = $this->resolveUserId(intval($this->request->param('user_id', 0)));
        return $this->renderSuccess(FollowUpService::weekVisits($this->storeId, $start, $end, $userId));
    }

    /** 日历事件（拜访+生日+周年） */
    public function calendarEvents()
    {
        $start  = $this->request->param('start', '');
        $end    = $this->request->param('end', '');
        $userId = $this->resolveUserId(intval($this->request->param('user_id', 0)));
        return $this->renderSuccess(FollowUpService::calendarEvents($this->storeId, $start, $end, $userId));
    }

    /**
     * 解析 userId：显式传参优先；非超管自动限制为当前用户
     * 超管传 0 = 看全部，非超管强制限制为本人
     */
    private function resolveUserId(int $explicitUserId): int
    {
        if ($explicitUserId > 0) return $explicitUserId;
        if (empty($this->store['is_super']) && ($this->store['role_id'] ?? 0) !== 1) {
            return $this->userId;
        }
        return 0;
    }

    /** AI 祝福语 */
    public function greeting()
    {
        $type       = $this->request->param('type', '');
        $name       = $this->request->param('name', '');
        $context    = $this->request->param('context', '');
        $eventDate  = $this->request->param('event_date', date('Y-m-d'));
        $targetId   = $this->request->param('target_id', '');
        $followupId = intval($this->request->param('followup_id', 0));
        try {
            $exist = \think\facade\Db::name('crm_calendar_greeting')
                ->where('store_id', $this->storeId)->where('event_type', $type)
                ->where('event_date', $eventDate)->where('target_id', $targetId)
                ->value('greeting');
            if ($exist) return $this->renderSuccess(['text' => $exist]);
            $prompt = "请写一句祝福语，50字以内";
            if ($type === 'birthday') $prompt = "请为{$name}写一句生日祝福语，温馨真诚，50字以内。";
            elseif ($type === 'established') $prompt = "请为{$name}写一句公司周年庆贺语，商务大气，50字以内。背景：{$context}";
            elseif ($type === 'visit') $prompt = "请为拜访{$name}写一句开场问候语，专业亲切，50字以内。背景：{$context}";
            $result = \app\common\service\AiService::quickAsk($prompt);
            $text = $result['data'] ?? '🎉 祝一切顺利！';
            \think\facade\Db::name('crm_calendar_greeting')->insert([
                'store_id' => $this->storeId, 'event_type' => $type, 'event_date' => $eventDate,
                'target_id' => (string)$targetId, 'target_name' => $name, 'context' => $context,
                'greeting' => $text, 'create_time' => time(), 'update_time' => time(),
            ]);
            if ($followupId > 0) {
                \think\facade\Db::name('crm_followup')->where('id', $followupId)
                    ->update(['next_follow_content' => $text, 'update_time' => time()]);
            }
            return $this->renderSuccess(['text' => $text]);
        } catch (\Throwable $e) {
            return $this->renderSuccess(['text' => '🎉 祝一切顺利！']);
        }
    }
}
