<?php
declare(strict_types=1);

namespace mpp\crm\api;

use mpp\crm\service\CustomFieldService;

/**
 * 移动端自定义字段
 * URL: /api/crm.customField/lists
 */
class CustomField extends Base
{
    protected $methodRules = [
        'lists'     => 'GET',
        'active'    => 'GET',
        'add'       => 'POST',
        'edit'      => 'POST',
        'delete'    => 'POST',
        'setStatus' => 'POST',
    ];

    /** GET /api/crm.customField/lists */
    public function lists()
    {
        $result = (new CustomFieldService())->getList($this->request->param());
        return $this->renderSuccess($result['data']);
    }

    /** GET /api/crm.customField/active?entity=customer */
    public function active()
    {
        $entity = $this->request->param('entity', 'customer');
        $result = (new CustomFieldService())->getActiveFields($entity);
        return $this->renderSuccess($result['data']);
    }

    /** POST /api/crm.customField/add */
    public function add()
    {
        $data = $this->postData();
        $result = (new CustomFieldService())->add($data);
        if ($result['success']) {
            return $this->renderSuccess($result['data'], '添加成功');
        }
        return $this->renderError($result['error']);
    }

    /** POST /api/crm.customField/edit */
    public function edit()
    {
        $data = $this->postData();
        $result = (new CustomFieldService())->edit($data);
        if ($result['success']) {
            return $this->renderSuccess([], '更新成功');
        }
        return $this->renderError($result['error']);
    }

    /** POST /api/crm.customField/delete */
    public function delete()
    {
        $id = $this->request->param('id', 0);
        $result = (new CustomFieldService())->delete((int)$id);
        if ($result['success']) {
            return $this->renderSuccess([], '删除成功');
        }
        return $this->renderError($result['error']);
    }

    /** POST /api/crm.customField/setStatus */
    public function setStatus()
    {
        $id = $this->request->param('id', 0);
        $status = (int)$this->request->param('status', 1);
        $result = (new CustomFieldService())->setStatus((int)$id, $status);
        if ($result['success']) {
            return $this->renderSuccess([], '状态更新成功');
        }
        return $this->renderError($result['error']);
    }
}
