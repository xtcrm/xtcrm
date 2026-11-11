<?php
namespace mpp\crm\backend;

use app\platform\backend\BaseController;
use mpp\crm\service\CustomFieldService;

class CustomField extends BaseController
{
    protected $methodRules = [
        'lists'     => 'GET',
        'active'    => 'GET',
        'add'       => 'POST',
        'edit'      => 'POST',
        'delete'    => 'POST',
        'setStatus' => 'POST',
    ];

    public function lists()
    {
        if (!$this->checkAction('/crm/customField/lists', '查询自定义字段')) return;
        $result = (new CustomFieldService())->getList($this->request->param());
        return $this->renderSuccess($result['data']);
    }

    public function active()
    {
        $entity = $this->request->param('entity', 'customer');
        $result = (new CustomFieldService())->getActiveFields($entity);
        return $this->renderSuccess($result['data']);
    }

    public function add()
    {
        if (!$this->checkAction('/crm/customField/add', '新增自定义字段')) return;
        $result = (new CustomFieldService())->add($this->postData());
        if ($result['success']) return $this->renderSuccess($result['data'], '添加成功');
        return $this->renderError($result['error']);
    }

    public function edit()
    {
        if (!$this->checkAction('/crm/customField/edit', '编辑自定义字段')) return;
        $result = (new CustomFieldService())->edit($this->postData());
        if ($result['success']) return $this->renderSuccess($result['data'], '保存成功');
        return $this->renderError($result['error']);
    }

    public function delete()
    {
        if (!$this->checkAction('/crm/customField/delete', '删除自定义字段')) return;
        $id = $this->request->param('id', 0);
        $result = (new CustomFieldService())->delete($id);
        if ($result['success']) return $this->renderSuccess([], '删除成功');
        return $this->renderError($result['error']);
    }

    public function setStatus()
    {
        if (!$this->checkAction('/crm/customField/setStatus', '启用停用字段')) return;
        $id = $this->request->param('id', 0);
        $status = $this->request->param('status', 1);
        $result = (new CustomFieldService())->setStatus($id, $status);
        if ($result['success']) return $this->renderSuccess([], '操作成功');
        return $this->renderError($result['error']);
    }
}
