<?php
namespace mpp\crm\backend;

use app\platform\backend\BaseController;
use mpp\crm\service\FollowUpService;

class FollowUp extends BaseController
{
    protected $methodRules = [
        'lists' => 'GET', 'todayVisits' => 'GET', 'weekVisits' => 'GET', 'calendarEvents' => 'GET',
        'greeting' => 'POST', 'greetings' => 'GET',
        'add' => 'POST', 'edit' => 'POST', 'delete' => 'POST',
        'eventAdd' => 'POST', 'eventEdit' => 'POST', 'eventDelete' => 'POST',
    ];

    /** 今日拜访列表 */
    public function todayVisits()
    {
        $userId = $this->resolveUserId(intval($this->request->param('user_id', 0)));
        $result = FollowUpService::todayVisits($this->storeId, $userId);
        return $this->renderSuccess($result);
    }

    /** 周拜访列表（按日期范围） */
    public function weekVisits()
    {
        $start = $this->request->param('start', '');
        $end = $this->request->param('end', '');
        $userId = $this->resolveUserId(intval($this->request->param('user_id', 0)));
        $result = FollowUpService::weekVisits($this->storeId, $start, $end, $userId);
        return $this->renderSuccess($result);
    }

    /** 统一获取已保存的祝福语（所有事件类型） */
    public function greetings()
    {
        $start = $this->request->param('start', date('Y-m-01'));
        $end = $this->request->param('end', date('Y-m-t'));
        $list = \think\facade\Db::name('crm_calendar_greeting')
            ->where('store_id', $this->storeId)
            ->where('event_date', '>=', $start)
            ->where('event_date', '<=', $end)
            ->field('event_type, event_date, target_id, greeting')
            ->select()->toArray();
        return $this->renderSuccess(['list' => $list]);
    }

    /** AI 生成祝福语 */
    public function greeting()
    {
        $type = $this->request->param('type', ''); // birthday / established / visit
        $name = $this->request->param('name', '');
        $context = $this->request->param('context', '');
        $eventDate = $this->request->param('event_date', date('Y-m-d'));
        $targetId = $this->request->param('target_id', '');
        $followupId = intval($this->request->param('followup_id', 0));
        try {
            // 先查是否已生成过
            $exist = \think\facade\Db::name('crm_calendar_greeting')
                ->where('store_id', $this->storeId)
                ->where('event_type', $type)
                ->where('event_date', $eventDate)
                ->where('target_id', $targetId)
                ->value('greeting');
            if ($exist) {
                return $this->renderSuccess(['text' => $exist]);
            }
            // AI 生成
            $prompt = $this->buildGreetingPrompt($type, $name, $context);
            $result = \app\common\service\AiService::quickAsk($prompt);
            $text = $result['data'] ?? ($result['success'] ? '' : '');
            $text = $text ?: '🎉 祝一切顺利！';
            // 写入事件记录表
            \think\facade\Db::name('crm_calendar_greeting')->insert([
                'store_id' => $this->storeId,
                'event_type' => $type,
                'event_date' => $eventDate,
                'target_id' => (string)$targetId,
                'target_name' => $name,
                'context' => $context,
                'greeting' => $text,
                'create_time' => time(),
                'update_time' => time(),
            ]);
            // 拜访事件同步写入跟进记录
            if ($followupId > 0) {
                \think\facade\Db::name('crm_followup')->where('id', $followupId)->update([
                    'next_follow_content' => $text,
                    'update_time' => time(),
                ]);
            }
            return $this->renderSuccess(['text' => $text]);
        } catch (\Throwable $e) {
            return $this->renderSuccess(['text' => '🎉 祝一切顺利！']);
        }
    }

    private function buildGreetingPrompt(string $type, string $name, string $context): string
    {
        $name = $name ?: '客户';
        switch ($type) {
            case 'birthday':
                return "请为{$name}写一句生日祝福语，温馨真诚，50字以内，不要称呼\"亲爱的\"。";
            case 'established':
                return "请为{$name}写一句公司周年庆贺语，商务大气，50字以内。背景：{$context}";
            case 'visit':
                return "请为拜访{$name}写一句开场问候语，专业亲切，50字以内。背景：{$context}";
            default:
                return "请写一句商务祝福语，50字以内。";
        }
    }

    /** 日历事件（拜访+生日+成立日） */
    public function calendarEvents()
    {
        $start = $this->request->param('start', '');
        $end = $this->request->param('end', '');
        $userId = $this->resolveUserId(intval($this->request->param('user_id', 0)));
        $customerId = intval($this->request->param('customer_id', 0));
        $result = FollowUpService::calendarEvents($this->storeId, $start, $end, $userId, $customerId);
        return $this->renderSuccess($result);
    }

    /**
     * 解析 userId：显式传参优先；非超管自动限制为当前用户
     * PC 后台 token 缓存不含 is_super，需从 DB 查询
     */
    private function resolveUserId(int $explicitUserId): int
    {
        if ($explicitUserId > 0) return $explicitUserId;
        $user = \app\platform\auth\TokenService::getUser();
        if (empty($user['is_super'])) {
            return $this->getUserId();
        }
        return 0;
    }

    protected function getUserId(): int
    {
        return $this->store['store_user_id'] ?? ($this->store['uid'] ?? 0);
    }

    public function lists()
    {
        if(!$this->checkAction('/crm/followup/lists','跟进列表')) return;
        $customerId = $this->request->param('customer_id', 0);
        $result = (new FollowUpService())->getList($customerId);
        return $this->renderSuccess($result['data']);
    }

    public function add()
    {
        if(!$this->checkAction('/crm/followup/add','新增跟进')) return;
        $data = $this->postData();
        $data['owner_user_id'] = $this->getUserId();
        $result = (new FollowUpService())->add($data, $this->getUserId());
        if ($result['success']) return $this->renderSuccess($result['data'], '添加成功');
        return $this->renderError($result['error']);
    }

    public function edit()
    {
        if(!$this->checkAction('/crm/followup/edit','编辑跟进')) return;
        $id = $this->request->param('id', 0);
        $result = (new FollowUpService())->edit($id, $this->postData(), $this->getUserId());
        if ($result['success']) return $this->renderSuccess([], '更新成功');
        return $this->renderError($result['error']);
    }

    public function delete()
    {
        if(!$this->checkAction('/crm/followup/delete','删除跟进')) return;
        $id = $this->request->param('id', 0);
        $result = (new FollowUpService())->delete($id, $this->getUserId());
        if ($result['success']) return $this->renderSuccess([], '删除成功');
        return $this->renderError($result['error']);
    }

    /** 自定义日历事件 — 新增 */
    public function eventAdd()
    {
        $data = $this->postData();
        $data['owner_user_id'] = $this->getUserId();
        \think\facade\Db::name('crm_calendar_event')->insert([
            'store_id' => $this->storeId,
            'title' => $data['title'] ?? '',
            'event_date' => $data['event_date'] ?? date('Y-m-d'),
            'end_date' => $data['end_date'] ?: null,
            'repeat_type' => $data['repeat_type'] ?? 'none',
            'repeat_rule' => $data['repeat_rule'] ? json_encode($data['repeat_rule']) : null,
            'color' => $data['color'] ?? 'blue',
            'is_lunar' => intval($data['is_lunar'] ?? 0),
            'content' => $data['content'] ?? '',
            'customer_id' => intval($data['customer_id'] ?? 0),
            'owner_user_id' => $data['owner_user_id'] ?? 0,
            'create_time' => time(),
            'update_time' => time(),
        ]);
        return $this->renderSuccess([], '添加成功');
    }

    /** 自定义日历事件 — 编辑 */
    public function eventEdit()
    {
        $data = $this->postData();
        $id = intval($data['id'] ?? 0);
        if ($id <= 0) return $this->renderError('参数错误');
        \think\facade\Db::name('crm_calendar_event')
            ->where('id', $id)->where('store_id', $this->storeId)
            ->update([
                'title' => $data['title'] ?? '',
                'event_date' => $data['event_date'] ?? date('Y-m-d'),
                'end_date' => $data['end_date'] ?: null,
                'repeat_type' => $data['repeat_type'] ?? 'none',
                'repeat_rule' => $data['repeat_rule'] ? json_encode($data['repeat_rule']) : null,
                'color' => $data['color'] ?? 'blue',
                'content' => $data['content'] ?? '',
                'customer_id' => intval($data['customer_id'] ?? 0),
                'update_time' => time(),
            ]);
        return $this->renderSuccess([], '更新成功');
    }

    /** 自定义日历事件 — 删除 */
    public function eventDelete()
    {
        $id = intval($this->request->param('id', 0));
        if ($id <= 0) return $this->renderError('参数错误');
        \think\facade\Db::name('crm_calendar_event')
            ->where('id', $id)->where('store_id', $this->storeId)
            ->update(['is_delete' => 1, 'update_time' => time()]);
        return $this->renderSuccess([], '删除成功');
    }
}
