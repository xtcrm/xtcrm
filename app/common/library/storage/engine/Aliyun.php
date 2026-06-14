<?php

declare (strict_types = 1);

namespace app\common\library\storage\engine;

use OSS\OssClient;
use OSS\Core\OssException;

/**
 * 阿里云存储引擎 (OSS)
 * Class Aliyun
 * @package app\common\library\storage\engine
 */
class Aliyun extends Basics
{
    /**
     * 执行上传
     * @return bool
     */
    public function upload(): bool
    {
        try {
            $ossClient = new OssClient(
                $this->config['access_key_id'],
                $this->config['access_key_secret'],
                $this->config['domain'],
                true
            );
            $result = $ossClient->uploadFile(
                $this->config['bucket'],
                $this->getSaveFileInfo()['file_path'],
                $this->getRealPath()
            );
        } catch (OssException $e) {
            $this->error = $e->getMessage();
            return false;
        }
        return true;
    }

    /**
     * 删除文件
     * @param string $filePath
     * @return bool
     */
    public function delete(string $filePath): bool
    {
        try {
            $ossClient = new OssClient(
                $this->config['access_key_id'],
                $this->config['access_key_secret'],
                $this->config['domain'],
                true
            );
            $ossClient->deleteObject($this->config['bucket'], $filePath);
        } catch (OssException $e) {
            $this->error = $e->getMessage();
            return false;
        }
        return true;
    }
}
