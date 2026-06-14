<?php
declare (strict_types=1);

namespace cores\library;

use app\common\exception\BaseException;

class Download
{
    // 保存后文件夹路径
    private $folderPath;

    // 保存后的文件名
    private $fileName;

    // 下载地址
    private $url;

    /**
     * 设置下载的版本号 (用于建立本地目录)
     * @param string $path
     * @return $this
     */
    public function setFolderPath(string $path): Download
    {
        $this->folderPath = $path;
        !is_dir($this->folderPath) && mkdir($this->folderPath, 0755, true);
        return $this;
    }

    /**
     * 设置保存后的文件名
     * @param string $fileName
     * @return $this
     */
    public function setFileName(string $fileName): Download
    {
        $this->fileName = $fileName;
        return $this;
    }

    /**
     * 设置下载地址
     * @param string $url
     * @return $this
     */
    public function setUrl(string $url): Download
    {
        $this->url = $url;
        return $this;
    }

    /**
     * 执行下载和保存
     * @return bool
     * @throws \cores\exception\BaseException
     */
    public function download(): bool
    {
        // 下载网络资源
        $result = $this->httpGet();
        empty($result) && throwError('文件下载失败');
        // 保存到本地
        return $this->fwrite($result);
    }

    /**
     * 获取保存的文件路径
     * @return string
     */
    public function getFilePath(): string
    {
        return $this->folderPath . $this->fileName;
    }

    /**
     * 写入文件
     * @param string $content
     * @return bool
     */
    private function fwrite(string $content): bool
    {
        $fp = fopen($this->getFilePath(), 'w');
        $status = fwrite($fp, $content);
        fclose($fp);
        return $status !== false;
    }

    /**
     * 请求网络文件
     * @return bool|string
     * @throws \cores\exception\BaseException
     */
    private function httpGet()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        // 检查是否有错误发生
        if (curl_errno($ch)) {
            throwError('Curl error: ' . curl_error($ch));
        }
        curl_close($ch);
        return $result;
    }
}