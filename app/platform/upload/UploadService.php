<?php

declare (strict_types=1);

namespace app\platform\upload;

use think\facade\Db;
use app\common\library\storage\Driver as StorageDriver;

/**
 * 平台统一上传服务
 *
 * 自包含文件记录管理（直接写 yoshop_upload_file），
 * 存储引擎委托 app\common\library\storage\Driver（OSS/本地/七牛等）。
 */
class UploadService
{
    const FILE_TABLE = 'upload_file';

    /** 文件类型常量 */
    const TYPE_IMAGE = 10;
    const TYPE_ANNEX = 20;
    const TYPE_VIDEO = 30;

    /** 默认存储 */
    const STORAGE_LOCAL = 'local';

    /** 最大文件大小（字节），默认 10MB */
    const MAX_FILE_SIZE = 10 * 1024 * 1024;

    /**
     * 上传单个文件
     *
     * @param \think\File $file
     * @param int         $storeId  租户 ID
     * @param int         $groupId  文件分组 ID
     * @return array
     * @throws \think\Exception
     */
    public static function upload(\think\File $file, int $storeId = 0, int $groupId = 0): array
    {
        // 1. 读取存储配置
        $config = self::getStorageConfig($storeId);

        // 2. 大小校验
        //    这里不调 Driver::setValidationScene()：那个方法把 $rules 数组原样转调给存储引擎，
        //    而引擎侧的签名是 setValidationScene(string $scene)（收的是「场景名」，不是规则），
        //    传数组必然 TypeError —— 所以本方法自建以来一调用就炸。改为自己校验，保留 10MB 上限的意图。
        $size = (int)($file->getSize() ?: 0);
        if ($size > self::MAX_FILE_SIZE) {
            throw new \think\Exception('文件大小超出限制，最大 ' . round(self::MAX_FILE_SIZE / 1048576, 1) . 'MB');
        }

        // 3. 上传到存储引擎
        $storage = new StorageDriver($config);
        // 按租户分目录，与 mpp/crm/service/DocumentService 的落盘位置保持一致。
        // 注意 setUploadFile() 返回的是存储引擎，setRootDirName 必须链在它后面，不能打在 $storage 上。
        $storage->setUploadFile('iFile')->setRootDirName((string)$storeId);

        if (!$storage->upload()) {
            throw new \think\Exception($storage->getError() ?: '文件上传失败');
        }

        $saved = $storage->getSaveFileInfo();
        // 注意：不要用 $file->getInfo() —— think\File / think\file\UploadedFile 上都没有这个方法，
        // 引擎的 getSaveFileInfo() 已经把 file_size 一并带回来了。原先那行会让每次真实上传都 fatal。
        $fileSize = (int)($saved['file_size'] ?? $size);

        // 4. 判断文件类型
        $fileExt  = strtolower(pathinfo($saved['file_name'], PATHINFO_EXTENSION));
        $fileType = self::guessFileType($fileExt);

        // 5. 写入文件库
        $fileId = Db::name(self::FILE_TABLE)->insertGetId([
            'group_id'   => $groupId > 0 ? $groupId : 0,
            'channel'    => 10,
            'storage'    => $config['default'] ?? self::STORAGE_LOCAL,
            'domain'     => '',
            'file_name'  => $saved['file_name'],
            'file_path'  => $saved['file_path'],
            'file_size'  => $fileSize,
            'file_ext'   => $fileExt,
            'file_type'  => $fileType,
            'store_id'   => $storeId,
            'is_recycle' => 0,
            'is_delete'  => 0,
        ]);

        return [
            'file_id'   => (int)$fileId,
            'file_name' => $saved['file_name'],
            'file_path' => $saved['file_path'],
            'file_url'  => FileHelper::fullUrl($saved['file_path']),
            'file_ext'  => $fileExt,
            'file_size' => $fileSize,
        ];
    }

    /**
     * 从本地文件路径入库（系统生成的文件，不是 HTTP 上传）
     *
     * 场景：TTS 合成的配音、ffmpeg 合成的成片 —— 这些是服务端自己产出的文件，
     * 没有 	hink\File 上传对象，走不了 upload()。
     * 底层复用同一条存储链路（Driver::setUploadFileByReal → 各引擎 upload），
     * 入库字段与 upload() 完全一致，因此 url()/urlMap()/delete() 都通用。
     *
     * 源文件不会被移走：本地引擎最终走 Filesystem::putFileAs，
     * 内部是 fopen(..., 'r') 复制流，不是 rename。
     *
     * @param string $path    本地文件绝对路径
     * @param int    $storeId 租户 ID
     * @param int    $groupId 文件分组 ID
     * @param int    $maxSize 大小上限（字节）；0 = 用 MAX_FILE_SIZE
     * @return array 结构同 upload()
     * @throws 	hink\Exception
     */
    public static function uploadByPath(string $path, int $storeId = 0, int $groupId = 0, int $maxSize = 0): array
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new 	hink\Exception('待入库文件不存在或不可读');
        }

        $limit = $maxSize > 0 ? $maxSize : self::MAX_FILE_SIZE;
        $size  = (int)filesize($path);
        if ($size > $limit) {
            throw new 	hink\Exception('文件大小超出限制，最大 ' . round($limit / 1048576, 1) . 'MB');
        }

        // 1. 读取存储配置
        $config = self::getStorageConfig($storeId);

        // 2. 交给存储引擎（注意：setUploadFileByReal 返回的是存储引擎，
        //    setRootDirName 必须链在它后面，与 upload() 的写法一致）
        $storage = new StorageDriver($config);
        $storage->setUploadFileByReal($path)->setRootDirName((string)$storeId);

        if (!$storage->upload()) {
            throw new 	hink\Exception($storage->getError() ?: '文件入库失败');
        }

        $saved = $storage->getSaveFileInfo();
        $fileSize = (int)($saved['file_size'] ?? $size);

        // 3. 判断文件类型
        $fileExt  = strtolower(pathinfo($saved['file_name'], PATHINFO_EXTENSION));
        $fileType = self::guessFileType($fileExt);

        // 4. 写入文件库
        //    这里比 upload() 多写 create_time / update_time：upload() 那份漏了，
        //    导致 upload_file 里一堆 create_time 为空的记录（DocumentService 那份是写了的）。
        //    新代码补上，不动 upload() 的既有行为。
        $now = time();
        $fileId = Db::name(self::FILE_TABLE)->insertGetId([
            'group_id'    => $groupId > 0 ? $groupId : 0,
            'channel'     => 10,
            'storage'     => $config['default'] ?? self::STORAGE_LOCAL,
            'domain'      => '',
            'file_name'   => $saved['file_name'],
            'file_path'   => $saved['file_path'],
            'file_size'   => $fileSize,
            'file_ext'    => $fileExt,
            'file_type'   => $fileType,
            'store_id'    => $storeId,
            'is_recycle'  => 0,
            'is_delete'   => 0,
            'create_time' => $now,
            'update_time' => $now,
        ]);

        return [
            'file_id'   => (int)$fileId,
            'file_name' => $saved['file_name'],
            'file_path' => $saved['file_path'],
            'file_url'  => FileHelper::fullUrl($saved['file_path']),
            'file_ext'  => $fileExt,
            'file_size' => $fileSize,
        ];
    }

    /**
     * 根据 file_id 获取文件信息
     */
    public static function info(int $fileId): ?array
    {
        $row = Db::name(self::FILE_TABLE)->where('file_id', $fileId)->find();
        if (!$row) return null;

        $url = ($row['storage'] === self::STORAGE_LOCAL)
            ? rtrim(uploads_url(), '/') . '/' . $row['file_path']
            : ($row['domain'] ?: '') . '/' . $row['file_path'];

        return [
            'file_id'   => (int)$row['file_id'],
            'file_name' => $row['file_name'],
            'file_path' => $row['file_path'],
            'file_url'  => $url,
            'file_ext'  => $row['file_ext'],
            'file_size' => (int)$row['file_size'],
            'file_type' => (int)$row['file_type'],
            'storage'   => $row['storage'],
        ];
    }

    /**
     * 根据 file_id 获取完整 URL
     */
    public static function url(int $fileId): string
    {
        $info = self::info($fileId);
        return $info['file_url'] ?? '';
    }

    /**
     * 批量获取文件 URL
     */
    public static function urlMap(array $fileIds): array
    {
        $map = [];
        foreach ($fileIds as $fid) {
            $map[$fid] = self::url((int)$fid);
        }
        return $map;
    }

    /**
     * 删除文件（软删除 + 物理删除存储文件）
     */
    public static function delete(int $fileId): bool
    {
        $row = Db::name(self::FILE_TABLE)->where('file_id', $fileId)->find();
        if (!$row) return false;

        // 物理删除存储文件
        try {
            $config = self::getStorageConfig((int)$row['store_id']);
            $storage = new StorageDriver($config, $row['storage']);
            $storage->delete($row['file_path']);
        } catch (\Throwable $e) {
            // 存储删除失败不阻断
        }

        // 标记已删除
        Db::name(self::FILE_TABLE)->where('file_id', $fileId)->update(['is_delete' => 1]);
        return true;
    }

    // ── private ──

    /**
     * 读取存储配置
     */
    private static function getStorageConfig(int $storeId): array
    {
        $setting = Db::name('store_setting')
            ->where('store_id', $storeId)
            ->where('key', 'storage')
            ->find();

        if ($setting && !empty($setting['values'])) {
            $values = json_decode($setting['values'], true);
            if (is_array($values)) return $values;
        }

        // 默认本地存储
        return [
            'default' => self::STORAGE_LOCAL,
            'engine'  => [self::STORAGE_LOCAL => null],
        ];
    }

    private static function guessFileType(string $ext): int
    {
        $images = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg', 'ico'];
        $videos = ['mp4', 'avi', 'mov', 'wmv', 'flv', 'mkv', 'webm'];

        if (in_array($ext, $images)) return self::TYPE_IMAGE;
        if (in_array($ext, $videos)) return self::TYPE_VIDEO;
        return self::TYPE_ANNEX;
    }
}
