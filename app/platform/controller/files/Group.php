<?php
declare(strict_types=1);

namespace app\platform\controller\files;

use app\platform\backend\BaseController;
use app\store\model\UploadGroup as GroupModel;

class Group extends BaseController
{
    protected $allowAllAction = [];

    public function list()
    {
        $model = new GroupModel;
        $list = $model->getList();
        return $this->renderSuccess(compact('list'));
    }

    public function add()
    {
        $model = new GroupModel;
        if ($model->add($this->request->param())) {
            return $this->renderSuccess('添加成功');
        }
        return $this->renderError($model->getError() ?: '添加失败');
    }

    public function edit(int $groupId)
    {
        $model = GroupModel::detail($groupId);
        if ($model->edit($this->request->param())) {
            return $this->renderSuccess('更新成功');
        }
        return $this->renderError($model->getError() ?: '更新失败');
    }

    public function delete(int $groupId)
    {
        $model = GroupModel::detail($groupId);
        if (!$model->remove()) {
            return $this->renderError($model->getError() ?: '删除失败');
        }
        return $this->renderSuccess('删除成功');
    }
}
