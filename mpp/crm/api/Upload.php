<?php
declare(strict_types=1);

namespace mpp\crm\api;

use app\common\library\storage\Driver as StorageDriver;
use app\common\enum\Setting as SettingEnum;
use app\common\enum\file\FileType as FileTypeEnum;
use app\store\model\Setting as SettingModel;
use app\store\model\UploadFile as UploadFileModel;

/**
 * 移动端文件上传
 * URL: /api/crm.upload/image
 */
class Upload extends Base
{
    private $config;

    public function initialize()
    {
        parent::initialize();
        $this->config = SettingModel::getItem(SettingEnum::STORAGE);
    }

    protected $methodRules = [
        'image' => 'POST',
    ];

    /** POST /api/crm.upload/image */
    public function image()
    {
        $storage = new StorageDriver($this->config);
        $storage->setUploadFile('iFile')
            ->setRootDirName((string)$this->storeId)
            ->setValidationScene('image');

        if (!$storage->upload()) {
            return $this->renderError('上传失败：' . $storage->getError());
        }

        $fileInfo = $storage->getSaveFileInfo();

        $model = new UploadFileModel;
        $model->add($fileInfo, FileTypeEnum::IMAGE, 0);

        return $this->renderSuccess([
            'file_path' => $fileInfo['file_path'] ?? '',
            'file_url'  => $fileInfo['file_url'] ?? '',
            'file_name' => $fileInfo['file_name'] ?? '',
        ], '上传成功');
    }
}
