<?php

declare (strict_types=1);

namespace app\platform\ai;

use think\facade\Db;

/**
 * AI 配置读取
 *
 * 从 yoshop_config 表读取 AI 相关配置（config_type='crm_setting'）。
 * 各模块共享同一套 AI 配置，不区分模块。
 */
class AiConfig
{
    /** 默认值 */
    const DEFAULTS = [
        'api_url'     => 'https://api.deepseek.com/chat/completions',
        'api_key'     => '',
        'model'       => 'deepseek-chat',
        'temperature' => 0.7,
        'max_tokens'  => 1024,
    ];

    /** @var array<string, array> 按 store_id 缓存 */
    private static $cache = [];

    /**
     * 获取完整配置
     *
     * @param int|null $storeId 租户ID，null 时自动从当前请求获取
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
            ->where('config_type', 'crm_setting')
            ->where(function ($q) use ($storeId) {
                $q->where('store_id', $storeId);
                if ($storeId !== 0) {
                    $q->whereOr('store_id', 0); // fallback 到全局配置
                }
            })
            ->select()->toArray();

        $cfg = self::DEFAULTS;

        foreach ($rows as $r) {
            // store_id 匹配的优先，已经设置过的不再被 store_id=0 的覆盖
            $isFallback = ($storeId !== 0 && $r['store_id'] == 0);
            switch ($r['config_name']) {
                case 'ai_api_url':
                    $url = $r['config_value'] ?: $cfg['api_url'];
                    if (strpos($url, '/chat/completions') === false) {
                        $url = rtrim($url, '/') . '/chat/completions';
                    }
                    if (!$isFallback || $cfg['api_url'] === self::DEFAULTS['api_url']) {
                        $cfg['api_url'] = $url;
                    }
                    break;
                case 'ai_api_key':
                    if (!$isFallback || $cfg['api_key'] === '') {
                        $cfg['api_key'] = $r['config_value'];
                    }
                    break;
                case 'ai_model':
                    if (!$isFallback || $cfg['model'] === self::DEFAULTS['model']) {
                        $cfg['model'] = $r['config_value'] ?: $cfg['model'];
                    }
                    break;
                case 'ai_temperature':
                    if (!$isFallback || $cfg['temperature'] === self::DEFAULTS['temperature']) {
                        $cfg['temperature'] = floatval($r['config_value'] ?? 0.7);
                    }
                    break;
                case 'ai_max_tokens':
                    if (!$isFallback || $cfg['max_tokens'] === self::DEFAULTS['max_tokens']) {
                        $cfg['max_tokens'] = intval($r['config_value'] ?? 1024);
                    }
                    break;
            }
        }

        self::$cache[$cacheKey] = $cfg;
        return $cfg;
    }

    /** 快捷读取 */
    public static function apiUrl(): string     { return self::get()['api_url']; }
    public static function apiKey(): string      { return self::get()['api_key']; }
    public static function model(): string       { return self::get()['model']; }
    public static function temperature(): float  { return self::get()['temperature']; }
    public static function maxTokens(): int      { return self::get()['max_tokens']; }

    /** 是否已配置 */
    public static function isConfigured(): bool
    {
        return !empty(self::get()['api_key']);
    }

    /** 清除缓存（配置更新后调用） */
    public static function clearCache(?int $storeId = null): void
    {
        if ($storeId !== null) {
            unset(self::$cache['store_' . $storeId]);
        } else {
            self::$cache = [];
        }
    }
}
