<?php

namespace cores\library\backup;

use cores\exception\BaseException;
use think\facade\Db;
use think\facade\Config;

/**
 * 数据库备份
 * Class Backup
 * @package cores\library
 */
class DataBase
{
    /**
     * 文件指针
     * @var resource
     */
    private $fp;

    /**
     * 备份文件信息 part - 卷号，name - 文件名
     * @var array
     */
    private $file;

    /**
     * 当前打开文件大小
     * @var integer
     */
    private $size = 0;

    /**
     * 数据库配置
     * @var array
     */
    private $dbConfig = [];

    /**
     * 备份配置
     * @var integer
     */
    private $config = [
        'path' => './backup/', //数据库备份路径
        'part' => 20971520, //数据库备份卷大小
        'compress' => 0, //数据库备份文件是否启用压缩 0不压缩 1 压缩
        'level' => 9, //数据库备份文件压缩级别 1普通 4 一般  9最高
    ];

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
        // 初始化数据库连接参数
        $this->setDbConn();
        // 检查文件夹是否可写
        if (!$this->checkPath($this->config['path'])) {
            throwError('很抱歉，数据库备份目录不可写');
        }
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
     * 设置数据库连接必备参数
     * @param array $dbConfig 数据库连接配置信息
     * @return $this
     */
    public function setDbConn(array $dbConfig = []): self
    {
        $database = Config::get('database');
        $this->dbConfig = !empty($dbConfig) ? $dbConfig : $database['connections'][$database['default']];
        return $this;
    }

    /**
     * 数据类连接
     * @return \think\db\ConnectionInterface
     */
    public static function connect(): \think\db\ConnectionInterface
    {
        return Db::connect();
    }

    /**
     * 数据库表列表
     * @return array
     */
    public function tableList(): array
    {
        $list = self::connect()->query('SHOW TABLE STATUS');
        return array_map('array_change_key_case', $list);
    }

    /**
     * 查看指定表名的字段信息
     * @param string $table
     * @return array
     */
    public function tableInfo(string $table): array
    {
        $list = self::connect()->query("SHOW FULL COLUMNS FROM {$table}");
        return array_map('array_change_key_case', $list);
    }

    /***
     * 备份文件列表
     * file_path : 文件完整路径
     * file_name : 文件名称
     * create_time ： 文件的创建时间
     * file_size： 文件大小
     */
    public function fileList(): array
    {
        if (!is_dir($this->config['path'])) {
            mkdir($this->config['path'], 0755, true);
        }
        $path = realpath($this->config['path']);
        // 迭代器遍历目录 :https://www.php.net/manual/zh/class.filesystemiterator.php
        $glob = new \FilesystemIterator($path);
        $list = [];
        // $glob->valid() 检测迭代器是否到底了
        while ($glob->valid()) {
            $fileNamePath = $path . '/' . $glob->getFilename();
            $item['file_name'] = $glob->getFilename();
            $item['file_path'] = $fileNamePath;
            $item['create_time'] = $glob->getCTime();
            $item['file_size'] = filesize($fileNamePath);
            $list[] = $item;
            $glob->next();  // 游标往后移动
        }
        return $list;
    }

    /***
     * 删除备份文件
     * @param string filename 文件名字
     * @throws BaseException
     */
    public function fileDel(string $filename): bool
    {
        $path = realpath($this->config['path']);
        $fileNamePath = $path . '/' . $filename;
        if (!file_exists($fileNamePath)) {
            throwError("{$filename} 404");
        }
        chmod($fileNamePath, 0777);
        unlink($fileNamePath);
        return true;
    }

    /**
     * 下载备份
     * @param string filename 文件名字
     */
    public function fileDownload(string $filename): bool
    {
        $path = realpath($this->config['path']);
        $fileNamePath = $path . '/' . $filename;
        if (!file_exists($fileNamePath)) {
            return false;
        }
        // 告诉浏览器这是一个文件流格式的文件
        header("Content-type: application/octet-stream;charset=utf-8");
        // 用来告诉浏览器，文件是可以当做附件被下载，下载后的文件名称为$file_name该变量的值。
        header("Content-Disposition: attachment; filename=" . $filename);
        // 请求范围的度量单位
        header("Accept-Ranges: bytes");
        // Content-Length是指定包含于请求或响应中数据的字节长度
        header("Accept-Length: " . filesize($fileNamePath));
        readfile($fileNamePath);
    }

    /**
     * 设置备份文件名称
     * @param string|null $file 文件名字
     * @return $this
     */
    public function setFile(?string $file = null): self
    {
        $fileName = $file ?: date('YmdHis');
        $this->file = ['name' => $fileName, 'part' => 1];
        return $this;
    }

    /**
     * 备份表结构
     * 函数功能：把表的结构转换成为SQL
     * 函数参数：$table: 要进行提取的表名
     * 返 回 值：返回提取后的结果，SQL集合
     * @param string $table 表名
     * @return bool
     */
    public function backupTable(string $table): bool
    {
        $db = self::connect();
        $result = $db->query("SHOW CREATE TABLE `{$table}`");
        $sql = "\n";
        $sql .= "-- -----------------------------\n";
        $sql .= "-- Table structure for `{$table}`\n";
        $sql .= "-- -----------------------------\n";
        $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
        $sql .= trim($result[0]['Create Table']) . ";\n\n";
        return $this->write($sql);
    }

    /**
     * 备份表结构 + 数据
     * @param string $table 表名
     * @param int $start 起始行数
     * @return bool
     */
    public function backup(string $table, int $start = 0): bool
    {
        $db = self::connect();
        // 判断表是否存在
        if (!$this->isExistTable($table)) {
            return false;
        }
        // 备份表结构
        if ($start === 0) {
            $this->backupTable($table);
        }
        // 数据总数
        $result = $db->query("SELECT COUNT(*) AS count FROM `{$table}`");
        $count = $result[0]['count'];
        //备份表数据
        if ($count > 0) {
            // 写入数据注释
            if ($start === 0) {
                $sql = "-- -----------------------------\n";
                $sql .= "-- Records of `{$table}`\n";
                $sql .= "-- -----------------------------\n";
                $this->write($sql);
            }
            // 备份数据记录
            $result = $db->query("SELECT * FROM `{$table}` LIMIT {$start}, 1000");
            foreach ($result as $row) {
                $values = $this->row2values($row);
                $sql = "INSERT INTO `{$table}` VALUES ({$values});\n";
                if ($this->write($sql) === false) {
                    return false;
                }
            }
            // 还有更多数据
            if ($count > $start + 1000) {
                //return [$start + 1000, $count];
                return $this->backup($table, $start + 1000);
            }
        }
        return true;
    }

    /**
     * 指定的表是否存在
     * @param string $table 表名
     * @return bool
     */
    private function isExistTable(string $table): bool
    {
        $db = self::connect();
        return (bool)$db->query("SHOW TABLES LIKE '{$table}'");
    }

    /**
     * 数组数据转换为字符串 (用于拼接VALUES)
     * @param array $row
     * @return string
     */
    private function row2values(array $row): string
    {
        $values = '';
        foreach ($row as $value) {
            $values .= "'" . addslashes($value) . "', ";
        }
        return str_replace(["\r", "\n"], ['\\r', '\\n'], rtrim($values, ', '));
    }

    /**
     * 导入备份
     * @param string $filePath
     * @return bool
     */
    public function import(string $filePath): bool
    {
        $db = self::connect();
        $sqlStr = '';
        if ($this->config['compress']) {
            $gz = gzopen($filePath, 'r');
            $bufferSize = 4096; // read 4kb at a time
            while (!gzeof($gz)) {
                $sqlStr .= gzread($gz, $bufferSize);
            }
            gzclose($gz);
        } else {
            $sqlStr = file_get_contents($filePath, 'r');
        }
        $sqlStr = str_replace("\r\n", "\n", $sqlStr);
        $sqlArr = explode(";\n", $sqlStr);
        foreach ($sqlArr as $sql) {
            !empty(trim($sql)) && $db->query($sql);
        }
        return true;
    }

    /**
     * 优化表
     * @param string|string[] $tables 表名 [支持数组]
     * @return bool
     */
    public function optimize($tables = null): bool
    {
        $db = self::connect();
        if (is_array($tables)) {
            $tables = implode('`,`', $tables);
        }
        $list = $db->query("OPTIMIZE TABLE `{$tables}`");
        return true;
    }

    /**
     * 优化表
     * @param string|string[] $tables 表名 [支持数组]
     * @return bool
     */
    public function repair($tables = null): bool
    {
        $db = self::connect();
        if (is_array($tables)) {
            $tables = implode('`,`', $tables);
        }
        $list = $db->query("REPAIR TABLE `{$tables}`");
        return true;
    }

    /**
     * 写入SQL语句
     * @param string $sql 要写入的SQL语句
     * @return bool     true写入成功，false写入失败
     */
    private function write(string $sql): bool
    {
        $size = strlen($sql);
        //由于压缩原因，无法计算出压缩后的长度，这里假设压缩率为50%，
        //一般情况压缩率都会高于50%；
        $size = $this->config['compress'] ? $size / 2 : $size;
        $this->open($size);
        return $this->config['compress'] ? gzwrite($this->fp, $sql) : fwrite($this->fp, $sql);
    }

    /**
     * 打开一个卷，用于写入数据
     * @param int $size 写入数据的大小
     */
    private function open(int $size)
    {
        if ($this->fp) {
            $this->size += $size;
            if ($this->size > $this->config['part']) {
                $this->config['compress'] ? gzclose($this->fp) : fclose($this->fp);
                $this->fp = null;
                $this->file['part']++;
                $this->backupInit();
            }
            return;
        }
        $filename = "{$this->config['path']}{$this->file['name']}-{$this->file['part']}.sql";
        if ($this->config['compress']) {
            $filename .= '.gz';
            $this->fp = gzopen($filename, "a{$this->config['level']}");
        } else {
            $this->fp = fopen($filename, 'a');
        }
        $this->size = filesize($filename) + $size;
    }

    /**
     * 写入初始数据
     * @return void
     */
    private function backupInit(): void
    {
        $sql = "-- -----------------------------\n";
        $sql .= "-- MySQL Data Transfer \n";
        $sql .= "-- \n";
        $sql .= "-- Host     : " . $this->dbConfig['hostname'] . "\n";
        $sql .= "-- Port     : " . $this->dbConfig['hostport'] . "\n";
        $sql .= "-- Database : " . $this->dbConfig['database'] . "\n";
        $sql .= "-- \n";
        $sql .= "-- Part : #{$this->file['part']}\n";
        $sql .= "-- Date : " . date("Y-m-d H:i:s") . "\n";
        $sql .= "-- -----------------------------\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";
        $this->write($sql);
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
     * 析构方法，用于关闭文件资源
     */
    public function __destruct()
    {
        if ($this->fp) {
            $this->config['compress'] ? gzclose($this->fp) : fclose($this->fp);
        }
    }
}
