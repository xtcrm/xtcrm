<?php
namespace mpp\crm\backend;

use app\platform\backend\BaseController;
use mpp\crm\service\ContactService;

class Contact extends BaseController
{
    protected $methodRules = [
        'lists' => 'GET', 'listsByCustomer' => 'GET', 'detail' => 'GET', 'lookup' => 'GET',
        'add' => 'POST', 'edit' => 'POST', 'unbind' => 'POST', 'delete' => 'POST',
    ];

    // ==================== 手机号查重 ====================

    public function lookup()
    {
        $mobile = trim($this->request->param('mobile', ''));
        if (empty($mobile)) return $this->renderError('手机号不能为空');
        $result = ContactService::lookupByMobile($mobile, $this->storeId);
        return $this->renderSuccess($result ?: []);
    }

    // ==================== 联系人管理菜单 ====================

    public function lists()
    {
        if(!$this->checkAction('/crm/contact/list','联系人列表')) return;
        $where = $this->request->param();
        $result = ContactService::listAll($where, $where, $this->storeId);
        return $this->renderSuccess($result);
    }

    public function detail()
    {
        if(!$this->checkAction('/crm/contact/detail','联系人详情')) return;
        $id = (int)$this->request->param('id', 0);
        $result = (new ContactService())->detail($id, $this->storeId);
        if (!$result['success']) return $this->renderError($result['error']);
        return $this->renderSuccess($result['data']);
    }

    // ==================== 客户下的联系人操作 ====================

    public function listsByCustomer()
    {
        $customerId = (int)$this->request->param('customer_id', 0);
        $result = (new ContactService())->getByCustomer($customerId, $this->storeId);
        return $this->renderSuccess($result['data']);
    }

    public function add()
    {
        if(!$this->checkAction('/crm/contact/add','新增联系人')) return;
        $result = (new ContactService())->addOrBind($this->postData(), $this->storeId, $this->getUserId());
        if ($result['success']) return $this->renderSuccess($result['data'], $result['data']['msg'] ?? '添加成功');
        return $this->renderError($result['error']);
    }

    public function edit()
    {
        if(!$this->checkAction('/crm/contact/edit','编辑联系人')) return;
        $id = (int)$this->request->param('id', 0);
        $result = (new ContactService())->edit($id, $this->postData(), $this->storeId, $this->getUserId());
        if ($result['success']) return $this->renderSuccess([], '更新成功');
        return $this->renderError($result['error']);
    }

    public function unbind()
    {
        if(!$this->checkAction('/crm/contact/unbind','解除绑定')) return;
        $contactId = (int)$this->request->param('contact_id', 0);
        $customerId = (int)$this->request->param('customer_id', 0);
        $result = (new ContactService())->unbind($contactId, $customerId, $this->storeId, $this->getUserId());
        if ($result['success']) return $this->renderSuccess([], '已解除绑定');
        return $this->renderError($result['error']);
    }

    public function delete()
    {
        if(!$this->checkAction('/crm/contact/delete','删除联系人')) return;
        $id = (int)$this->request->param('id', 0);
        $result = (new ContactService())->delete($id, $this->storeId, $this->getUserId());
        if ($result['success']) return $this->renderSuccess([], '删除成功');
        return $this->renderError($result['error']);
    }
}
