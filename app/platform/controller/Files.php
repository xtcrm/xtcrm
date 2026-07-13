<?php
declare(strict_types=1);

namespace app\platform\controller;

use app\platform\backend\BaseController;
use app\store\model\UploadFile as UploadFileModel;

class Files extends BaseController
{
    protected $allowAllAction = ['list'];

    public function list()
    {
        $model = new UploadFileModel;
        $list = $model->getList($this->request->param());
        return $this->renderSuccess(compact('list'));
    }

    /** 上传文件 */
    public function upload()
    {
        $model = new UploadFileModel;
        if ($model->upload($this->request)) {
            return $this->renderSuccess(['file_id' => $model->file_id, 'preview_url' => $model->preview_url, 'file_path' => $model->file_path], '上传成功');
        }
        return $this->renderError($model->getError() ?: '上传失败');
    }

    public function edit(int $fileId)
    {
        $model = UploadFileModel::detail($fileId);
        if ($model->edit($this->request->param())) {
            return $this->renderSuccess('更新成功');
        }
        return $this->renderError($model->getError() ?: '更新失败');
    }

    public function delete(array $fileIds)
    {
        $model = new UploadFileModel;
        if (!$model->setDelete($fileIds)) {
            return $this->renderError($model->getError() ?: '操作失败');
        }
        return $this->renderSuccess('操作成功');
    }

    public function moveGroup(int $groupId, array $fileIds)
    {
        $model = new UploadFileModel;
        if (!$model->moveGroup($groupId, $fileIds)) {
            return $this->renderError($model->getError() ?: '操作失败');
        }
        return $this->renderSuccess('操作成功');
    }
}
