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

        // 2. 上传到存储引擎
        $storage = new StorageDriver($config);
        $storage->setUploadFile();
        $storage->setValidationScene(['fileSize' => self::MAX_FILE_SIZE]);

        if (!$storage->upload()) {
            throw new \think\Exception($storage->getError() ?: '文件上传失败');
        }

        $saved = $storage->getSaveFileInfo();
        $info  = $file->getInfo();

        // 3. 判断文件类型
        $fileExt  = strtolower(pathinfo($saved['file_name'], PATHINFO_EXTENSION));
        $fileType = self::guessFileType($fileExt);

        // 4. 写入文件库
        $fileId = Db::name(self::FILE_TABLE)->insertGetId([
            'group_id'   => $groupId > 0 ? $groupId : 0,
            'channel'    => 10,
            'storage'    => $config['default'] ?? self::STORAGE_LOCAL,
            'domain'     => '',
            'file_name'  => $saved['file_name'],
            'file_path'  => $saved['file_path'],
            'file_size'  => $info['size'] ?? 0,
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
            'file_size' => (int)$info['size'],
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
