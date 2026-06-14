<?php

namespace cores\library\backup;

use ZipArchive;
use app\common\library\Lock;
use cores\exception\BaseException;

class Files
{
    /**
     * 备份配置
     * @var integer
     */
    private $config = [
        'path' => './backup/', //数据库备份路径
    ];

    /**
     * 备份的文件名
     * @var string
     */
    private $fileName;

    /**
     * 数据库备份构造方法
     * @param array $config 备份配置信息
     * @throws BaseException
     */
    public function __construct(array $config = [])
    {
        // 初始化配置信息
        $this->config = array_merge($this->config, $config);
        // 设置脚本运行超时时间
        $this->setTimeout();
        // 初始化文件名
        $this->setFile();
        // 检查文件夹是否可写
        if (!$this->checkPath($this->config['path'])) {
            throwError('很抱歉，后端文件备份目录不可写');
        }
    }

    /**
     * 设置备份文件名
     * @param string|null $fileName 文件名称
     * @return $this
     */
    public function setFile(?string $fileName = null): self
    {
        $this->fileName = $fileName ?: date('YmdHis');
        return $this;
    }

    /**
     * 检查目录是否可写
     * @param string $path 目录
     * @return boolean
     */
    protected function checkPath(string $path): bool
    {
        if (is_dir($path) && is_writable($path)) {
            return true;
        }
        return mkdir($path, 0755, true);
    }

    /**
     * 设置脚本运行超时时间
     * 0表示不限制，支持连贯操作
     */
    public function setTimeout($time = 0): self
    {
        if (!is_null($time)) {
            set_time_limit($time) || ini_set('max_execution_time', $time);
        }
        return $this;
    }

    /**
     * 备份指定文件
     * @param array $fileList
     * @return bool
     * @throws BaseException
     */
    public function backup(array $fileList): bool
    {
        // 设置并发锁
        $this->lockUp();
        // zip文件的路径
        $zipFilePath = "{$this->config['path']}{$this->fileName}.zip";
        // 创建一个zip文件
        $Zip = new ZipArchive;
        if ($Zip->open($zipFilePath, ZipArchive::CREATE) !== true) {
            throwError('创建zip文件失败');
        }
        // 将要打包的文件列表写入到zip中
        foreach ($fileList as $filePath) {
            $realFilePath = root_path() . $filePath;
            if (file_exists($realFilePath)) {
                $Zip->addFile($realFilePath, $filePath);
            }
        }
        // 关闭处理的zip文件
        $Zip->close();
        // 关闭并发锁
        $this->unLock();
        return true;
    }

    /**
     * 导入文件包
     * @param string $zipFilePath 文件包的路径(zip格式)
     * @return bool
     * @throws BaseException
     */
    public function import(string $zipFilePath): bool
    {
        // 设置并发锁
        $this->lockUp();
        // 打开zip文件
        $Zip = new ZipArchive;
        if ($Zip->open($zipFilePath) !== true) {
            throwError('打开zip文件失败');
        }
        // 要解压到的目录
        $outputPath = root_path();
        // 检查文件夹是否可写
        if (!$this->checkPath($outputPath)) {
            throwError('很抱歉，解压到的目录不可写');
        }
        // 解压zip文件
        $status = $Zip->extractTo($outputPath);
        // 关闭处理的zip文件
        $Zip->close();
        // 关闭并发锁
        $this->unLock();
        if ($status == false) {
            throwError('解压zip文件失败');
        }
        return $status;
    }

    /**
     * 防止并发文件锁
     */
    private function lockUp()
    {
        Lock::lockUp('BackupFiles');
    }

    /**
     * 解除并发锁
     */
    private function unLock()
    {
        Lock::unLock('BackupFiles');
    }
}