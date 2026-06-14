<?php
namespace mpp\crm\backend;

use app\platform\backend\BaseController;
use mpp\crm\service\FollowUpService;

class FollowUp extends BaseController
{
    protected $methodRules = [
        'lists' => 'GET', 'add' => 'POST', 'edit' => 'POST', 'delete' => 'POST',
    ];

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
}
