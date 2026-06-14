<?php
namespace mpp\crm\backend;

use app\platform\backend\BaseController;
use mpp\crm\service\ContractService;

class Contract extends BaseController
{
    protected $methodRules = [
        'lists' => 'GET', 'detail' => 'GET',
        'add' => 'POST', 'edit' => 'POST', 'delete' => 'POST', 'changeStatus' => 'POST',
    ];

    protected function getUserId(): int
    {
        return $this->store['store_user_id'] ?? ($this->store['uid'] ?? 0);
    }

    public function lists()
    {
        if(!$this->checkAction('/crm/contract/lists','合同列表')) return;
        $result = (new ContractService())->getList($this->request->param());
        return $this->renderSuccess($result['data']);
    }

    public function detail()
    {
        if(!$this->checkAction('/crm/contract/detail','合同详情')) return;
        $id = $this->request->param('id', 0);
        $result = (new ContractService())->detail($id);
        if (!$result['success']) return $this->renderError($result['error']);
        return $this->renderSuccess($result['data']);
    }

    public function add()
    {
        if(!$this->checkAction('/crm/contract/add','新增合同')) return;
        $data = $this->postData();
        $data['owner_user_id'] = $this->getUserId();
        $result = (new ContractService())->add($data, $this->getUserId());
        if ($result['success']) return $this->renderSuccess($result['data'], '添加成功');
        return $this->renderError($result['error']);
    }

    public function edit()
    {
        if(!$this->checkAction('/crm/contract/edit','编辑合同')) return;
        $id = $this->request->param('id', 0);
        $result = (new ContractService())->edit($id, $this->postData(), $this->getUserId());
        if ($result['success']) return $this->renderSuccess([], '更新成功');
        return $this->renderError($result['error']);
    }

    public function delete()
    {
        if(!$this->checkAction('/crm/contract/delete','删除合同')) return;
        $id = $this->request->param('id', 0);
        $result = (new ContractService())->delete($id, $this->getUserId());
        if ($result['success']) return $this->renderSuccess([], '删除成功');
        return $this->renderError($result['error']);
    }

    public function changeStatus()
    {
        if(!$this->checkAction('/crm/contract/changeStatus','变更状态')) return;
        $id = $this->request->param('id', 0);
        $status = $this->request->param('status', 1);
        $result = (new ContractService())->changeStatus($id, intval($status), $this->getUserId());
        if ($result['success']) return $this->renderSuccess([], '状态更新成功');
        return $this->renderError($result['error']);
    }
}
