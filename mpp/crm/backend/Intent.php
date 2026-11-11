<?php
namespace mpp\crm\backend;

use app\platform\backend\BaseController;
use mpp\crm\service\IntentService;

/**
 * 客户需求
 *
 * URL：s=/platform/crm.intent/{action}
 */
class Intent extends BaseController
{
    protected $methodRules = [
        'lists' => 'GET',
        'detail' => 'GET',
        'add' => 'POST',
        'edit' => 'POST',
        'assign' => 'POST',
        'hold' => 'POST',
        'resume' => 'POST',
        'close' => 'POST',
        'confirm' => 'POST',
    ];

    protected function getUserId(): int
    {
        return (int)($this->store['store_user_id'] ?? ($this->store['uid'] ?? 0));
    }

    public function lists()
    {
        if (!$this->checkAction('/crm/intent/lists', '需求列表')) return;
        $result = (new IntentService())->getList($this->request->param(), (int)$this->storeId, $this->getUserId());
        return $this->renderSuccess($result);
    }

    public function detail()
    {
        if (!$this->checkAction('/crm/intent/detail', '需求详情')) return;
        $id = (int)$this->request->param('id', 0);
        $result = (new IntentService())->detail($id, (int)$this->storeId, $this->getUserId());
        if (!$result['success']) return $this->renderError($result['error']);
        return $this->renderSuccess($result['data']);
    }

    public function add()
    {
        if (!$this->checkAction('/crm/intent/add', '新增需求')) return;
        $result = (new IntentService())->add($this->postData(), (int)$this->storeId, $this->getUserId());
        if (!$result['success']) return $this->renderError($result['error']);
        return $this->renderSuccess($result['data'], '创建成功');
    }

    public function edit()
    {
        if (!$this->checkAction('/crm/intent/edit', '编辑需求')) return;
        $id = (int)$this->request->param('id', 0);
        $data = $this->postData();
        unset($data['id']);
        $result = (new IntentService())->edit($id, $data, (int)$this->storeId, $this->getUserId());
        if (!$result['success']) return $this->renderError($result['error']);
        return $this->renderSuccess([], '保存成功');
    }

    public function assign()
    {
        if (!$this->checkAction('/crm/intent/assign', '指派承接人')) return;
        $id = (int)$this->request->param('id', 0);
        $assignee = (int)$this->request->param('assignee_user_id', 0);
        $result = (new IntentService())->assign($id, $assignee, (int)$this->storeId, $this->getUserId());
        if (!$result['success']) return $this->renderError($result['error']);
        return $this->renderSuccess([], '已指派');
    }

    public function hold()
    {
        if (!$this->checkAction('/crm/intent/close', '关闭/搁置需求')) return;
        $id = (int)$this->request->param('id', 0);
        $reason = (string)$this->request->param('reason', '');
        $heldUntil = $this->request->param('held_until', null);
        if ($heldUntil && !is_numeric($heldUntil)) $heldUntil = strtotime((string)$heldUntil) ?: null;
        $result = (new IntentService())->hold($id, $reason, $heldUntil ? (int)$heldUntil : null, (int)$this->storeId, $this->getUserId());
        if (!$result['success']) return $this->renderError($result['error']);
        return $this->renderSuccess([], '已搁置');
    }

    public function resume()
    {
        if (!$this->checkAction('/crm/intent/close', '关闭/搁置需求')) return;
        $id = (int)$this->request->param('id', 0);
        $result = (new IntentService())->resume($id, (int)$this->storeId, $this->getUserId());
        if (!$result['success']) return $this->renderError($result['error']);
        return $this->renderSuccess([], '已重新启动');
    }

    public function close()
    {
        if (!$this->checkAction('/crm/intent/close', '关闭/搁置需求')) return;
        $id = (int)$this->request->param('id', 0);
        $reason = (string)$this->request->param('reason', '');
        $result = (new IntentService())->close($id, $reason, (int)$this->storeId, $this->getUserId());
        if (!$result['success']) return $this->renderError($result['error']);
        return $this->renderSuccess([], '已关闭');
    }

    public function confirm()
    {
        if (!$this->checkAction('/crm/intent/confirm', '确认成交')) return;
        $id = (int)$this->request->param('id', 0);
        $result = (new IntentService())->confirm($id, (int)$this->storeId, $this->getUserId());
        if (!$result['success']) return $this->renderError($result['error']);
        return $this->renderSuccess([], '已成交');
    }
}
