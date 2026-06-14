<?php

namespace mpp\crm\backend\manage;

use app\platform\backend\BaseController;
use app\platform\model\Department as DepartmentModel;
use app\platform\model\UserDepartment;

/**
 * CRM 部门管理控制器
 *
 * 路由：/crm.manage.department/tree
 * 暂时委托 app/store/ 模型，后续可迁移到 platform/model/。
 */
class Department extends BaseController
{
    protected $methodRules = [
        'tree'       => 'GET',
        'add'        => 'POST',
        'edit'       => 'POST',
        'delete'     => 'POST',
        'getUsers'   => 'GET',
        'assignUser' => 'POST',
        'removeUser' => 'POST',
    ];

    public function tree()
    {
        $model = new DepartmentModel;
        $params = $this->request->param();
        $businessParams = array_diff_key($params, array_flip(['s', 'app', 'controller', 'method']));
        $tree = $model->getTreeList($businessParams);
        return $this->renderSuccess(compact('tree'));
    }

    public function add()
    {
        $model = new DepartmentModel;
        if ($model->add($this->postData())) {
            return $this->renderSuccess('添加成功');
        }
        return $this->renderError($model->getError() ?: '添加失败');
    }

    public function edit($id)
    {
        $model = new DepartmentModel;
        $detail = $model->detail($id);
        if (!$detail) return $this->renderError('部门不存在');

        if ($detail->edit($this->postData())) {
            return $this->renderSuccess('更新成功');
        }
        return $this->renderError($detail->getError() ?: '更新失败');
    }

    public function delete()
    {
        $data = $this->postData();
        $id = $data['id'] ?? 0;
        $model = new DepartmentModel;
        $detail = $model->detail($id);
        if (!$detail) return $this->renderError('部门不存在');
        if ($detail->setDelete($id)) return $this->renderSuccess('删除成功');
        return $this->renderError($detail->getError() ?: '删除失败');
    }

    public function getUsers()
    {
        $params = $this->request->param();
        $id = $params['id'] ?? 0;
        $model = new DepartmentModel;
        $detail = $model->detail($id);
        if (!$detail) return $this->renderError('部门不存在');
        $users = $model->getUsers($id);
        return $this->renderSuccess(compact('users'));
    }

    public function assignUser()
    {
        $data = $this->postData();
        $model = new DepartmentModel;
        $detail = $model->detail($data['department_id'] ?? 0);
        if (!$detail) return $this->renderError('部门不存在');

        if ($model->assignUser($data['user_id'] ?? 0, $data['department_id'] ?? 0, $data['is_manager'] ?? 0)) {
            return $this->renderSuccess('分配成功');
        }
        return $this->renderError($model->getError() ?: '分配失败');
    }

    public function removeUser()
    {
        $data = $this->postData();
        $userDeptModel = new UserDepartment();
        if ($userDeptModel->removeUserFromDepartment($data['user_id'] ?? 0)) {
            return $this->renderSuccess('移除成功');
        }
        return $this->renderError('移除失败');
    }
}
