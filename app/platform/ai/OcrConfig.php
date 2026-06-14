<?php

declare (strict_types=1);

namespace app\platform\ai;

use think\facade\Db;

/**
 * OCR 配置读取
 *
 * 从 yoshop_config 表读取阿里云 OCR 相关配置（config_type='platform_setting'）。
 * 各模块共享同一套 OCR 配置。
 */
class OcrConfig
{
    const DEFAULTS = [
        'endpoint'         => 'ocr-api.cn-hangzhou.aliyuncs.com',
        'access_key_id'     => '',
        'access_key_secret' => '',
    ];

    /** @var array<string, array> 按 store_id 缓存 */
    private static $cache = [];

    /**
     * 获取完整配置
     */
    public static function get(?int $storeId = null): array
    {
        if ($storeId === null) {
            try {
                $storeId = app()->request->storeId() ?? 0;
            } catch (\Throwable $e) {
                $storeId = 0;
            }
        }

        $cacheKey = 'store_' . $storeId;
        if (isset(self::$cache[$cacheKey])) return self::$cache[$cacheKey];

        $rows = Db::name('config')
            ->where('config_type', 'platform_setting')
            ->where(function ($q) use ($storeId) {
                $q->where('store_id', $storeId);
                if ($storeId !== 0) {
                    $q->whereOr('store_id', 0);
                }
            })
            ->select()->toArray();

        $cfg = self::DEFAULTS;

        foreach ($rows as $r) {
            $isFallback = ($storeId !== 0 && $r['store_id'] == 0);
            switch ($r['config_name']) {
                case 'ocr_endpoint':
                    if (!$isFallback || $cfg['endpoint'] === self::DEFAULTS['endpoint']) {
                        $cfg['endpoint'] = $r['config_value'] ?: $cfg['endpoint'];
                    }
                    break;
                case 'ocr_access_key_id':
                    if (!$isFallback || $cfg['access_key_id'] === '') {
                        $cfg['access_key_id'] = $r['config_value'];
                    }
                    break;
                case 'ocr_access_key_secret':
                    if (!$isFallback || $cfg['access_key_secret'] === '') {
                        $cfg['access_key_secret'] = $r['config_value'];
                    }
                    break;
            }
        }

        self::$cache[$cacheKey] = $cfg;
        return $cfg;
    }

    /** 是否已配置 */
    public static function isConfigured(): bool
    {
        $cfg = self::get();
        return !empty($cfg['access_key_id']) && !empty($cfg['access_key_secret']);
    }

    /** 清除缓存 */
    public static function clearCache(?int $storeId = null): void
    {
        if ($storeId !== null) {
            unset(self::$cache['store_' . $storeId]);
        } else {
            self::$cache = [];
        }
    }
}
