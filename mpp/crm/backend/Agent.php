<?php
namespace mpp\crm\backend;

use app\platform\backend\BaseController;
use mpp\crm\service\AgentService;

class Agent extends BaseController
{
    protected $methodRules = [
        'chat' => 'POST',
        'lists' => 'GET', 'copyFromPreset' => 'POST', 'add' => 'POST', 'edit' => 'POST', 'delete' => 'POST',
    ];

    protected function getUserId(): int
    {
        return $this->store['store_user_id'] ?? ($this->store['uid'] ?? 0);
    }

    /** AI 对话 — CRM 模块 */
    public function chat()
    {
        $message = $this->request->param('message', '');
        if (empty($message)) return $this->renderError('请输入内容');

        $result = (new AgentService())->chat($message, $this->getUserId());
        if ($result['success']) return $this->renderSuccess($result['data']);
        return $this->renderError($result['error']);
    }

    /** 预设列表 — 内容引擎 skill */
    public function lists()
    {
        if (!$this->checkAction('/crm.profile/detail', '查看档案')) return;
        $result = (new AgentService())->listAll($this->storeId);
        return $this->renderSuccess($result['data']);
    }

    /** 从预设复制 — 内容引擎 skill */
    public function copyFromPreset()
    {
        $presetKey = $this->request->param('preset_key', '');
        if (empty($presetKey)) return $this->renderError('请选择预设');
        $result = (new AgentService())->copyFromPreset($presetKey, $this->storeId);
        if (!$result['success']) return $this->renderError($result['error']);
        return $this->renderSuccess($result['data'], '复制成功');
    }

    /** 添加预设 — 内容引擎 skill */
    public function add()
    {
        $data = $this->postData();
        $result = (new AgentService())->add($data, $this->storeId);
        if (!$result['success']) return $this->renderError($result['error']);
        return $this->renderSuccess($result['data'], '添加成功');
    }

    /** 编辑预设 — 内容引擎 skill */
    public function edit()
    {
        $data = $this->postData();
        $result = (new AgentService())->edit($data, $this->storeId);
        if (!$result['success']) return $this->renderError($result['error']);
        return $this->renderSuccess([], '更新成功');
    }

    /** 删除预设 — 内容引擎 skill */
    public function delete()
    {
        $id = $this->request->param('id', 0);
        $result = (new AgentService())->delete((int)$id, $this->storeId);
        if (!$result['success']) return $this->renderError($result['error']);
        return $this->renderSuccess([], '删除成功');
    }
}
