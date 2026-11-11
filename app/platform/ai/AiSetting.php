<?php

declare (strict_types=1);

namespace app\platform\ai;

use think\facade\Db;

/**
 * 商城全局 AI 配置
 *
 * 由「设置 → AI 配置」（/setting/ai）写入，config_type = 'llm_setting'，按租户隔离。
 *
 * 两种接入方式：
 *   platform — 平台配置：走平台自建网关（sub2api），商家只填 Key + 选模型，
 *              接口地址属于平台内部实现，不落库配置、不回传前端
 *   custom   — 自定义配置：填 OpenAI 兼容地址（DeepSeek 等）+ Key + 模型
 *
 * AiConfig 会优先使用本配置；未配置时回退到旧 crm_setting，存量租户不受影响。
 */
class AiSetting
{
    /** 配置命名空间 */
    const TYPE = 'llm_setting';

    const PROVIDER_PLATFORM = 'platform';
    const PROVIDER_CUSTOM = 'custom';

    /** 平台网关地址（基于 sub2api） */
    const PLATFORM_BASE = 'https://api.xtocn.com';

    const DEFAULTS = [
        'provider'    => self::PROVIDER_PLATFORM,
        'api_url'     => '',
        'api_key'     => '',
        'model'       => '',
        'temperature' => '0.7',
        'max_tokens'  => '1500',
    ];

    /**
     * 配置表字段名 → 内部键名
     *
     * 沿用 ai_* 命名，避免和历史配置混在一起时看不出来源。
     */
    const NAME_MAP = [
        'provider'      => 'provider',
        'ai_api_url'    => 'api_url',
        'ai_api_key'    => 'api_key',
        'ai_model'      => 'model',
        'ai_temperature' => 'temperature',
        'ai_max_tokens' => 'max_tokens',
    ];

    /** @var array<string, array> 按 store_id 缓存（请求级） */
    private static $cache = [];

    /**
     * 获取完整配置
     *
     * @param int|null $storeId 显式租户；null 时从请求上下文解析
     */
    public static function get(?int $storeId = null): array
    {
        if ($storeId === null) {
            try {
                $storeId = (int)(app()->request->storeId() ?? 0);
            } catch (\Throwable $e) {
                $storeId = 0;
            }
        }

        $cacheKey = 'store_' . $storeId;
        if (isset(self::$cache[$cacheKey])) return self::$cache[$cacheKey];

        $rows = Db::name('config')
            ->where('config_type', self::TYPE)
            ->where(function ($q) use ($storeId) {
                $q->where('store_id', $storeId);
                if ($storeId !== 0) {
                    $q->whereOr('store_id', 0);
                }
            })
            ->select()->toArray();

        // 租户配置覆盖全局兜底；空值不覆盖
        $global = [];
        $own = [];
        foreach ($rows as $r) {
            $name = (string)$r['config_name'];
            if (!isset(self::NAME_MAP[$name])) continue;
            $key = self::NAME_MAP[$name];
            if ((int)$r['store_id'] === $storeId) {
                $own[$key] = (string)$r['config_value'];
            } else {
                $global[$key] = (string)$r['config_value'];
            }
        }
        $merged = array_merge($global, $own);

        $cfg = self::DEFAULTS;
        foreach (self::DEFAULTS as $key => $default) {
            if (isset($merged[$key]) && $merged[$key] !== '') {
                $cfg[$key] = $merged[$key];
            }
        }
        if (!in_array($cfg['provider'], [self::PROVIDER_PLATFORM, self::PROVIDER_CUSTOM], true)) {
            $cfg['provider'] = self::PROVIDER_PLATFORM;
        }

        self::$cache[$cacheKey] = $cfg;
        return $cfg;
    }

    public static function isConfigured(?int $storeId = null): bool
    {
        return !empty(self::get($storeId)['api_key']);
    }

    /**
     * 平台网关地址（不带末尾斜杠）
     * 本地/测试环境可用环境变量 XTOCN_AI_BASE 覆盖
     */
    public static function platformBase(): string
    {
        $base = getenv('XTOCN_AI_BASE');
        if (!is_string($base) || trim($base) === '') {
            $base = self::PLATFORM_BASE;
        }
        return rtrim(trim($base), '/');
    }

    /**
     * 对话补全地址
     */
    public static function endpoint(?array $cfg = null): string
    {
        $cfg = $cfg ?? self::get();
        if (($cfg['provider'] ?? self::PROVIDER_PLATFORM) !== self::PROVIDER_CUSTOM) {
            return self::platformBase() . '/v1/chat/completions';
        }
        return self::normalizeChatUrl((string)($cfg['api_url'] ?? ''));
    }

    /**
     * 模型列表地址
     */
    public static function modelsUrl(?array $cfg = null): string
    {
        $cfg = $cfg ?? self::get();
        if (($cfg['provider'] ?? self::PROVIDER_PLATFORM) !== self::PROVIDER_CUSTOM) {
            return self::platformBase() . '/v1/models';
        }
        $url = self::normalizeChatUrl((string)($cfg['api_url'] ?? ''));
        return $url === '' ? '' : preg_replace('#/chat/completions$#', '/models', $url);
    }

    /**
     * 规范化自定义地址：用户可能只填域名，也可能填完整地址
     */
    public static function normalizeChatUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') return '';
        if (strpos($url, '/chat/completions') !== false) return $url;

        $path = (string)parse_url($url, PHP_URL_PATH);
        $path = trim($path, '/');
        // 只填了域名（如 https://api.deepseek.com）时补 /v1，兼容 OpenAI 风格
        if ($path === '') {
            return rtrim($url, '/') . '/v1/chat/completions';
        }
        return rtrim($url, '/') . '/chat/completions';
    }

    /**
     * 供前端回显
     *
     * 平台配置不下发接口地址 —— 那是平台内部实现，商家无需关心。
     */
    public static function forDisplay(?int $storeId = null): array
    {
        $cfg = self::get($storeId);
        $isCustom = $cfg['provider'] === self::PROVIDER_CUSTOM;

        return [
            'provider'       => $cfg['provider'],
            'has_key'        => !empty($cfg['api_key']),
            'api_key_mask'   => self::mask($cfg['api_key']),
            'ai_api_url'     => $isCustom ? $cfg['api_url'] : '',
            'ai_model'       => $cfg['model'],
            'ai_temperature' => (float)$cfg['temperature'],
            'ai_max_tokens'  => (int)$cfg['max_tokens'],
        ];
    }

    public static function mask(string $key): string
    {
        $len = strlen($key);
        if ($len === 0) return '';
        if ($len <= 8) return str_repeat('*', $len);
        return substr($key, 0, 3) . '****' . substr($key, -4);
    }

    public static function clearCache(?int $storeId = null): void
    {
        if ($storeId !== null) {
            unset(self::$cache['store_' . $storeId]);
        } else {
            self::$cache = [];
        }
    }
}
