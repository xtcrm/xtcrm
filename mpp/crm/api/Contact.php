<?php
namespace mpp\crm\api;

use mpp\crm\service\ContactService;

class Contact extends Base
{
    protected $methodRules = ['lists'=>'GET','detail'=>'GET','add'=>'POST','edit'=>'POST','delete'=>'POST'];

    public function lists()
    {
        $customerId = (int)$this->request->param('customer_id', 0);
        $result = (new ContactService())->getList($customerId);
        return $this->renderSuccess($result['data'] ?: []);
    }

    public function detail()
    {
        $id = (int)$this->request->param('id', 0);
        $result = (new ContactService())->detail($id);
        return $result['success'] ? $this->renderSuccess($result['data'] ?: []) : $this->renderError($result['error']);
    }

    public function add()
    {
        $data = $this->postData();
        $result = (new ContactService())->add($data, $this->getUserId());
        return $result['success'] ? $this->renderSuccess($result['data'] ?: [], '添加成功') : $this->renderError($result['error']);
    }

    public function edit()
    {
        $data = $this->postData();
        $id = (int)($data['id'] ?? 0);
        $result = (new ContactService())->edit($id, $data, $this->getUserId());
        return $result['success'] ? $this->renderSuccess($result['data'] ?: [], '保存成功') : $this->renderError($result['error']);
    }

    public function delete()
    {
        $id = (int)$this->request->post('id', 0);
        $result = (new ContactService())->delete($id);
        return $result['success'] ? $this->renderSuccess('删除成功') : $this->renderError($result['error']);
    }
}
