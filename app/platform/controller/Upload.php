<?php
declare(strict_types=1);

namespace app\platform\controller;

use app\platform\backend\BaseController;
use app\platform\upload\UploadService;
use app\platform\model\UploadFile as UploadFileModel;
use app\common\model\store\Setting as SettingModel;
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

    /**
     * 上传文件（文档等，不限图片）
     *
     * 与 image() 的区别：不限制文件类型，且走平台自包含的 UploadService
     *（读存储配置 + 落 upload_file + 拼 URL 一手包），不依赖商城那套 app\store\model\UploadFile。
     * 大小上限见 UploadService::MAX_FILE_SIZE（10MB）。
     *
     * 注：本 action 不在 $allowAllAction 里，需要登录。
     */
    public function file()
    {
        $file = $this->request->file('iFile');
        if (!$file) {
            return $this->renderError('请选择要上传的文件');
        }

        try {
            $info = UploadService::upload($file, (int)$this->storeId, (int)$this->request->param('group_id', 0));
        } catch (\Throwable $e) {
            return $this->renderError($e->getMessage() ?: '上传失败');
        }

        return $this->renderSuccess([
            'file_id'   => $info['file_id'],
            'file_name' => $info['file_name'],
            'file_path' => $info['file_path'],
            'file_url'  => $info['file_url'],
            'file_ext'  => $info['file_ext'],
            'file_size' => $info['file_size'],
        ], '上传成功');
    }
}
