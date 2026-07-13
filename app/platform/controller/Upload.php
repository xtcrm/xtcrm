<?php
declare(strict_types=1);

namespace app\platform\controller;

use app\platform\backend\BaseController;
use app\store\model\UploadFile as UploadFileModel;
use app\store\model\Setting as SettingModel;
use app\common\enum\Setting as SettingEnum;
use app\common\enum\file\FileType as FileTypeEnum;
use app\common\library\storage\Driver as StorageDriver;

class Upload extends BaseController
{
    protected $allowAllAction = ['image'];

    /** 上传图片 */
    public function image()
    {
        $config = SettingModel::getItem(SettingEnum::STORAGE);
        $storage = new StorageDriver($config);
        $storage->setUploadFile('iFile')
            ->setRootDirName((string)$this->storeId)
            ->setValidationScene('image');
        if ($storage->upload()) {
            $fileInfo = $storage->getSaveFileInfo();
            $model = new UploadFileModel;
            $model->add($fileInfo, FileTypeEnum::IMAGE, (int)$this->request->param('group_id', 0));
            return $this->renderSuccess([
                'file_id'     => $model->file_id,
                'file_path'   => $fileInfo['file_path'],
                'preview_url' => $fileInfo['preview_url'] ?? $fileInfo['file_path'],
            ], '上传成功');
        }
        return $this->renderError($storage->getError() ?: '上传失败');
    }
}
