<?php
namespace mpp\crm\api;

use mpp\crm\service\ContactService;

class Contact extends Base
{
    protected $methodRules = [
        'lists'=>'GET','listsByCustomer'=>'GET','detail'=>'GET','lookup'=>'GET',
        'add'=>'POST','edit'=>'POST','unbind'=>'POST','delete'=>'POST',
    ];

    /** 手机号查重 */
    public function lookup()
    {
        $mobile = trim($this->request->param('mobile', ''));
        if (empty($mobile)) return $this->renderError('手机号不能为空');
        $result = ContactService::lookupByMobile($mobile, $this->storeId);
        return $this->renderSuccess($result ?: []);
    }

    /** 客户下的联系人 */
    public function listsByCustomer()
    {
        $customerId = (int)$this->request->param('customer_id', 0);
        $result = (new ContactService())->getByCustomer($customerId, $this->storeId);
        return $this->renderSuccess($result['data'] ?: []);
    }

    public function lists()
    {
        $customerId = (int)$this->request->param('customer_id', 0);
        $result = (new ContactService())->getList($customerId);
        return $this->renderSuccess($result['data'] ?: []);
    }

    public function detail()
    {
        $id = (int)$this->request->param('id', 0);
        $result = (new ContactService())->detail($id, $this->storeId);
        return $result['success'] ? $this->renderSuccess($result['data'] ?: []) : $this->renderError($result['error']);
    }

    /** 新增/绑定（手机号去重） */
    public function add()
    {
        $result = (new ContactService())->addOrBind($this->postData(), $this->storeId, $this->getUserId());
        return $result['success'] ? $this->renderSuccess($result['data'] ?: [], $result['data']['msg'] ?? '添加成功') : $this->renderError($result['error']);
    }

    public function edit()
    {
        $data = $this->postData();
        $id = (int)($data['id'] ?? 0);
        $result = (new ContactService())->edit($id, $data, $this->storeId, $this->getUserId());
        return $result['success'] ? $this->renderSuccess([], '保存成功') : $this->renderError($result['error']);
    }

    public function unbind()
    {
        $contactId = (int)$this->request->param('contact_id', 0);
        $customerId = (int)$this->request->param('customer_id', 0);
        $result = (new ContactService())->unbind($contactId, $customerId, $this->storeId, $this->getUserId());
        return $result['success'] ? $this->renderSuccess([], '已解除绑定') : $this->renderError($result['error']);
    }

    public function delete()
    {
        $id = (int)$this->request->post('id', 0);
        $result = (new ContactService())->delete($id, $this->storeId, $this->getUserId());
        return $result['success'] ? $this->renderSuccess([], '删除成功') : $this->renderError($result['error']);
    }
}
