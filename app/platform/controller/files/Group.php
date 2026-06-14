<?php

declare (strict_types = 1);

namespace app\platform\controller\files;

use app\platform\backend\BaseController;
use app\platform\model\UploadGroup as GroupModel;

/**
 * 文件分组
 * Class Group
 * @package app\platform\controller\files
 */
class Group extends BaseController
{
    /**
     * 文件分组列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function list()
    {
        $model = new GroupModel;
        $list = $model->getList();
        return $this->renderSuccess(compact('list'));
    }

    /**
     * 添加文件分组
     * @return array|string
     */
    public function add()
    {
        // 新增记录
        $model = new GroupModel;
        if ($model->add($this->postForm())) {
            return $this->renderSuccess('添加成功');
        }
        return $this->renderError($model->getError() ?: '添加失败');
    }

    /**
     * 编辑文件分组
     * @param int $groupId
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function edit(int $groupId)
    {
        // 分组详情
        $model = GroupModel::detail($groupId);
        // 更新记录
        if ($model->edit($this->postForm())) {
            return $this->renderSuccess('更新成功');
        }
        return $this->renderError($model->getError() ?: '更新失败');
    }

    /**
     * 删除文件分组
     * @param int $groupId
     * @return array
     * @throws \Exception
     */
    public function delete(int $groupId)
    {
        $model = GroupModel::detail($groupId);
        if (!$model->remove()) {
            return $this->renderError($model->getError() ?: '删除失败');
        }
        return $this->renderSuccess('删除成功');
    }

}
