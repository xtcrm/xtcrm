<?php
namespace mpp\crm\backend;

use app\platform\backend\BaseController;
use mpp\crm\service\ContactService;

class Contact extends BaseController
{
    protected $methodRules = [
        'lists' => 'GET', 'detail' => 'GET',
        'add' => 'POST', 'edit' => 'POST', 'delete' => 'POST',
    ];

    protected function getUserId(): int
    {
        return $this->store['store_user_id'] ?? ($this->store['uid'] ?? 0);
    }

    public function lists()
    {
        if(!$this->checkAction('/crm/contact/lists','联系人列表')) return;
        $customerId = $this->request->param('customer_id', 0);
        $result = (new ContactService())->getList($customerId);
        return $this->renderSuccess($result['data']);
    }

    public function detail()
    {
        if(!$this->checkAction('/crm/contact/detail','联系人详情')) return;
        $id = $this->request->param('id', 0);
        $result = (new ContactService())->detail($id);
        if (!$result['success']) return $this->renderError($result['error']);
        return $this->renderSuccess($result['data']);
    }

    public function add()
    {
        if(!$this->checkAction('/crm/contact/add','新增联系人')) return;
        $result = (new ContactService())->add($this->postData(), $this->getUserId());
        if ($result['success']) return $this->renderSuccess($result['data'], '添加成功');
        return $this->renderError($result['error']);
    }

    public function edit()
    {
        if(!$this->checkAction('/crm/contact/edit','编辑联系人')) return;
        $id = $this->request->param('id', 0);
        $result = (new ContactService())->edit($id, $this->postData(), $this->getUserId());
        if ($result['success']) return $this->renderSuccess([], '更新成功');
        return $this->renderError($result['error']);
    }

    public function delete()
    {
        if(!$this->checkAction('/crm/contact/delete','删除联系人')) return;
        $id = $this->request->param('id', 0);
        $result = (new ContactService())->delete($id, $this->getUserId());
        if ($result['success']) return $this->renderSuccess([], '删除成功');
        return $this->renderError($result['error']);
    }
}
