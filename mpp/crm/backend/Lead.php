<?php
namespace mpp\crm\backend;

use app\platform\backend\BaseController;
use mpp\crm\service\LeadService;

class Lead extends BaseController
{
    protected $methodRules = [
        'lists' => 'GET', 'detail' => 'GET',
        'add' => 'POST', 'edit' => 'POST', 'delete' => 'POST', 'convert' => 'POST',
    ];

    protected function getUserId(): int
    {
        return $this->store['store_user_id'] ?? ($this->store['uid'] ?? 0);
    }

    public function lists()
    {
        if(!$this->checkAction('/crm/lead/lists','线索列表')) return;
        $result = (new LeadService())->getList($this->request->param());
        return $this->renderSuccess($result['data']);
    }

    public function detail()
    {
        if(!$this->checkAction('/crm/lead/detail','线索详情')) return;
        $id = $this->request->param('id', 0);
        $result = (new LeadService())->detail($id);
        if (!$result['success']) return $this->renderError($result['error']);
        return $this->renderSuccess($result['data']);
    }

    public function add()
    {
        if(!$this->checkAction('/crm/lead/add','新增线索')) return;
        $data = $this->postData();
        $data['owner_user_id'] = $this->getUserId();
        $result = (new LeadService())->add($data, $this->getUserId());
        if ($result['success']) return $this->renderSuccess($result['data'], '添加成功');
        return $this->renderError($result['error']);
    }

    public function edit()
    {
        if(!$this->checkAction('/crm/lead/edit','编辑线索')) return;
        $id = $this->request->param('id', 0);
        $result = (new LeadService())->edit($id, $this->postData(), $this->getUserId());
        if ($result['success']) return $this->renderSuccess([], '更新成功');
        return $this->renderError($result['error']);
    }

    public function delete()
    {
        if(!$this->checkAction('/crm/lead/delete','删除线索')) return;
        $id = $this->request->param('id', 0);
        $result = (new LeadService())->delete($id, $this->getUserId());
        if ($result['success']) return $this->renderSuccess([], '删除成功');
        return $this->renderError($result['error']);
    }

    public function convert()
    {
        if(!$this->checkAction('/crm/lead/convert','转为客户')) return;
        $id = $this->request->param('id', 0);
        $result = (new LeadService())->convert($id, $this->getUserId());
        if ($result['success']) return $this->renderSuccess($result['data'], '已转为客户');
        return $this->renderError($result['error']);
    }
}
