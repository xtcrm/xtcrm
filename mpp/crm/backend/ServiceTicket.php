<?php

namespace mpp\crm\backend;

use app\platform\backend\BaseController;
use mpp\crm\service\ServiceTicketService;

class ServiceTicket extends BaseController
{
    protected $methodRules = [
        'lists' => 'GET', 'detail' => 'GET',
        'add' => 'POST', 'edit' => 'POST', 'assign' => 'POST', 'transfer' => 'POST',
        'flowTemplates' => 'GET', 'startFlow' => 'POST', 'cancelFlow' => 'POST',
        'changeStatus' => 'POST', 'rate' => 'POST', 'comment' => 'POST', 'delete' => 'POST',
    ];

    protected function getUserId(): int
    {
        return $this->store['store_user_id'] ?? ($this->store['uid'] ?? 0);
    }

    public function lists()
    {
        if (!$this->checkAction('/crm/serviceTicket/lists', '客服单列表')) return;
        $result = (new ServiceTicketService())->getList($this->request->param());
        return $this->renderSuccess($result);
    }

    public function detail()
    {
        if (!$this->checkAction('/crm/serviceTicket/lists', '客服单详情')) return;
        $id = (int)$this->request->param('id', 0);
        $result = (new ServiceTicketService())->detail($id);
        if (!$result['success']) return $this->renderError($result['error']);
        return $this->renderSuccess($result['data']);
    }

    public function add()
    {
        if (!$this->checkAction('/crm/serviceTicket/add', '新增客服单')) return;
        $result = (new ServiceTicketService())->add($this->postData(), $this->getUserId());
        if (!$result['success']) return $this->renderError($result['error']);
        return $this->renderSuccess($result['data'], '客服单已创建');
    }

    public function edit()
    {
        if (!$this->checkAction('/crm/serviceTicket/edit', '编辑客服单')) return;
        $data = $this->postData();
        $id = (int)($data['id'] ?? 0);
        $result = (new ServiceTicketService())->edit($id, $data, $this->getUserId());
        if (!$result['success']) return $this->renderError($result['error']);
        return $this->renderSuccess($result['data'] ?: [], '已更新');
    }

    public function flowTemplates()
    {
        if (!$this->checkAction('/crm/serviceTicket/edit', '客服单流程')) return;
        $result = (new ServiceTicketService())->flowTemplates();
        if (!$result['success']) return $this->renderError($result['error']);
        return $this->renderSuccess($result['data']);
    }

    public function startFlow()
    {
        if (!$this->checkAction('/crm/serviceTicket/edit', '客服单发起流程')) return;
        $data = $this->postData();
        $result = (new ServiceTicketService())->startFlow(
            (int)($data['id'] ?? 0),
            (string)($data['template_code'] ?? ''),
            $this->getUserId()
        );
        if (!$result['success']) return $this->renderError($result['error']);
        return $this->renderSuccess($result['data'] ?: [], '流程已发起');
    }

    public function cancelFlow()
    {
        if (!$this->checkAction('/crm/serviceTicket/edit', '客服单撤回流程')) return;
        $data = $this->postData();
        $result = (new ServiceTicketService())->cancelFlow(
            (int)($data['id'] ?? 0),
            (string)($data['reason'] ?? ''),
            $this->getUserId()
        );
        if (!$result['success']) return $this->renderError($result['error']);
        return $this->renderSuccess($result['data'] ?: [], '流程已撤回');
    }

    public function transfer()
    {
        if (!$this->checkAction('/crm/serviceTicket/assign', '客服单转办')) return;
        $data = $this->postData();
        $result = (new ServiceTicketService())->transfer(
            (int)($data['id'] ?? 0),
            (int)($data['to_user_id'] ?? 0),
            (string)($data['reason'] ?? ''),
            $this->getUserId()
        );
        if (!$result['success']) return $this->renderError($result['error']);
        return $this->renderSuccess($result['data'] ?: [], '已转办');
    }

    public function assign()
    {
        if (!$this->checkAction('/crm/serviceTicket/assign', '指派客服单')) return;
        $data = $this->postData();
        $result = (new ServiceTicketService())->assign(
            (int)($data['id'] ?? 0),
            (int)($data['assignee_user_id'] ?? 0),
            $this->getUserId()
        );
        if (!$result['success']) return $this->renderError($result['error']);
        return $this->renderSuccess($result['data'] ?: [], '已指派');
    }

    public function changeStatus()
    {
        if (!$this->checkAction('/crm/serviceTicket/changeStatus', '客服单状态')) return;
        $data = $this->postData();
        $result = (new ServiceTicketService())->changeStatus(
            (int)($data['id'] ?? 0),
            (int)($data['status'] ?? 0),
            $data,
            $this->getUserId()
        );
        if (!$result['success']) return $this->renderError($result['error']);
        $changed = !empty($result['data']['status_changed']);
        return $this->renderSuccess($result['data'] ?: [], $changed ? '状态已更新' : '处理结果已保存');
    }

    public function comment()
    {
        if (!$this->checkAction('/crm/serviceTicket/edit', '客服单跟进')) return;
        $data = $this->postData();
        $result = (new ServiceTicketService())->addComment(
            (int)($data['id'] ?? 0),
            (string)($data['content'] ?? ''),
            $this->getUserId()
        );
        if (!$result['success']) return $this->renderError($result['error']);
        return $this->renderSuccess($result['data'] ?: [], '已记录');
    }

    public function rate()
    {
        if (!$this->checkAction('/crm/serviceTicket/changeStatus', '客服单评价')) return;
        $data = $this->postData();
        $result = (new ServiceTicketService())->rate(
            (int)($data['id'] ?? 0),
            (int)($data['csat'] ?? 0),
            $this->getUserId()
        );
        if (!$result['success']) return $this->renderError($result['error']);
        return $this->renderSuccess($result['data'] ?: [], '评价已保存');
    }

    public function delete()
    {
        if (!$this->checkAction('/crm/serviceTicket/delete', '删除客服单')) return;
        $id = (int)$this->request->param('id', 0);
        $result = (new ServiceTicketService())->delete($id, $this->getUserId());
        if (!$result['success']) return $this->renderError($result['error']);
        return $this->renderSuccess($result['data'] ?: [], '已删除');
    }
}
