<?php

declare (strict_types=1);

namespace app\platform\upload;

/**
 * 文件工具类
 *
 * URL 拼接、文件类型/大小校验等通用操作。
 */
class FileHelper
{
    /**
     * 拼接文件的完整可访问 URL
     *
     * @param string $relativePath 相对路径，如 "2024/06/11/abc.jpg"
     * @return string               完整 URL，如 "https://xx.com/uploads/2024/06/11/abc.jpg"
     */
    public static function fullUrl(string $relativePath): string
    {
        if (empty($relativePath)) return '';

        // 已经是完整 URL 则直接返回
        if (preg_match('/^https?:\/\//', $relativePath)) {
            return $relativePath;
        }

        return rtrim(uploads_url(), '/') . '/' . ltrim($relativePath, '/');
    }

    /**
     * 校验文件扩展名是否在允许列表中
     *
     * @param string $ext     文件扩展名（不含点号）
     * @param array  $allowed 允许的扩展名列表
     * @return bool
     */
    public static function validateType(string $ext, array $allowed): bool
    {
        return in_array(strtolower($ext), $allowed);
    }

    /**
     * 校验文件大小是否在限制内
     *
     * @param int $bytes     文件字节数
     * @param int $maxBytes  最大允许字节数
     * @return bool
     */
    public static function validateSize(int $bytes, int $maxBytes): bool
    {
        return $bytes <= $maxBytes;
    }

    /**
     * 将字节数格式化为人类可读的大小
     *
     * @param int $bytes
     * @return string  如 "2.5 MB"
     */
    public static function formatSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        $size = (float)$bytes;
        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }
        return round($size, 2) . ' ' . $units[$i];
    }
}
