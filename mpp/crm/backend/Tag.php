<?php
namespace mpp\crm\backend;

use app\platform\backend\BaseController;
use mpp\crm\service\TagService;

class Tag extends BaseController
{
    protected $methodRules = [
        'groupLists'     => 'GET',
        'groupAdd'       => 'POST',
        'groupEdit'      => 'POST',
        'groupDelete'    => 'POST',
        'lists'          => 'GET',
        'add'            => 'POST',
        'edit'           => 'POST',
        'delete'         => 'POST',
        'allByTarget'    => 'GET',
        'getCustomerTags'  => 'GET',
        'setCustomerTags'  => 'POST',
        'getContactTags'   => 'GET',
        'setContactTags'   => 'POST',
    ];

    // ==================== 分组 ====================

    public function groupLists()
    {
        if (!$this->checkAction('/crm/tag/groupLists', '查询标签分组')) return;
        $result = (new TagService())->groupList($this->request->param());
        return $this->renderSuccess($result['data']);
    }

    public function groupAdd()
    {
        if (!$this->checkAction('/crm/tag/groupAdd', '新增标签分组')) return;
        $data = $this->postData();
        $result = (new TagService())->groupAdd($data);
        if ($result['success']) return $this->renderSuccess($result['data'], '添加成功');
        return $this->renderError($result['error']);
    }

    public function groupEdit()
    {
        if (!$this->checkAction('/crm/tag/groupEdit', '编辑标签分组')) return;
        $id = $this->request->param('id', 0);
        $result = (new TagService())->groupEdit($id, $this->postData());
        if ($result['success']) return $this->renderSuccess([], '更新成功');
        return $this->renderError($result['error']);
    }

    public function groupDelete()
    {
        if (!$this->checkAction('/crm/tag/groupDelete', '删除标签分组')) return;
        $id = $this->request->param('id', 0);
        $result = (new TagService())->groupDelete($id);
        if ($result['success']) return $this->renderSuccess([], '删除成功');
        return $this->renderError($result['error']);
    }

    // ==================== 标签 ====================

    public function lists()
    {
        if (!$this->checkAction('/crm/tag/lists', '查询标签')) return;
        $result = (new TagService())->getList($this->request->param());
        return $this->renderSuccess($result['data']);
    }

    public function add()
    {
        if (!$this->checkAction('/crm/tag/add', '新增标签')) return;
        $result = (new TagService())->add($this->postData());
        if ($result['success']) return $this->renderSuccess($result['data'], '添加成功');
        return $this->renderError($result['error']);
    }

    public function edit()
    {
        if (!$this->checkAction('/crm/tag/edit', '编辑标签')) return;
        $id = $this->request->param('id', 0);
        $result = (new TagService())->edit($id, $this->postData());
        if ($result['success']) return $this->renderSuccess([], '更新成功');
        return $this->renderError($result['error']);
    }

    public function delete()
    {
        if (!$this->checkAction('/crm/tag/delete', '删除标签')) return;
        $id = $this->request->param('id', 0);
        $result = (new TagService())->delete($id);
        if ($result['success']) return $this->renderSuccess([], '删除成功');
        return $this->renderError($result['error']);
    }

    // ==================== 分组树（下拉选择器） ====================

    public function allByTarget()
    {
        $targetType = $this->request->param('target_type', 'customer');
        $result = (new TagService())->allByTarget($targetType);
        return $this->renderSuccess($result['data']);
    }

    // ==================== 客户标签 ====================

    public function getCustomerTags()
    {
        $customerId = $this->request->param('customer_id', 0);
        $result = (new TagService())->getCustomerTags((int)$customerId);
        return $this->renderSuccess($result['data']);
    }

    public function setCustomerTags()
    {
        if (!$this->checkAction('/crm/tag/setCustomerTags', '客户标签分配')) return;
        $customerId = $this->request->param('customer_id', 0);
        $names = $this->request->param('names', []);
        $result = (new TagService())->setCustomerTags((int)$customerId, (array)$names);
        if ($result['success']) return $this->renderSuccess([], '标签已更新');
        return $this->renderError($result['error']);
    }

    // ==================== 联系人标签 ====================

    public function getContactTags()
    {
        $contactId = $this->request->param('contact_id', 0);
        $result = (new TagService())->getContactTags((int)$contactId);
        return $this->renderSuccess($result['data']);
    }

    public function setContactTags()
    {
        if (!$this->checkAction('/crm/tag/setContactTags', '联系人标签分配')) return;
        $contactId = $this->request->param('contact_id', 0);
        $names = $this->request->param('names', []);
        $result = (new TagService())->setContactTags((int)$contactId, (array)$names);
        if ($result['success']) return $this->renderSuccess([], '标签已更新');
        return $this->renderError($result['error']);
    }
}
