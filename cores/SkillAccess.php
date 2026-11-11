<?php
namespace cores;

/**
 * Skill 租户可见性
 *
 * 约定：每个 skill 可以在自己的目录下放 manifest.json：
 *
 *   // skills/{name}/manifest.json
 *   {
 *     "id": "legalpartner",
 *     "name": "法务宝合伙人",
 *     "store_id": [10001],                       // 0=所有租户 / int=单租户 / array=白名单
 *     "menu_prefixes": ["/apps/xxx", "/xxx."],   // 该 skill 的菜单 path 前缀
 *     "disabled_message": "该功能未开通"          // 未开通时的提示
 *   }
 *
 * 没有 manifest.json 的 skill 默认对全部租户开放，不受影响。
 */
class SkillAccess
{
    /** @var array<string, array> */
    private static $configs = [];

    /** @var array|null */
    private static $skills = null;

    /**
     * 读取 skill 配置
     *
     * 配置放在 skills/{name}/manifest.json，写法对齐 website 模板：
     *   "store_id": 0        // 所有租户
     *   "store_id": 10001    // 单租户
     *   "store_id": [10001]  // 多租户白名单
     */
    public static function config(string $skill): array
    {
        $skill = trim($skill);
        if ($skill === '') return self::defaults();
        if (isset(self::$configs[$skill])) return self::$configs[$skill];

        $file = self::skillDir() . $skill . '/manifest.json';
        if (!is_file($file)) {
            return self::$configs[$skill] = self::defaults();
        }

        $json = json_decode((string)file_get_contents($file), true);
        if (!is_array($json)) $json = [];

        $storeId = $json['store_id'] ?? 0;
        if (is_array($storeId)) {
            $allowAll = false;
            $enabledStores = array_map('intval', $storeId);
        } else {
            $storeId = (int)$storeId;
            $allowAll = ($storeId === 0);
            $enabledStores = $allowAll ? [] : [$storeId];
        }

        return self::$configs[$skill] = [
            'allow_all'        => $allowAll,
            'enabled_stores'   => $enabledStores,
            'menu_prefixes'    => array_values(array_filter(array_map('strval', (array)($json['menu_prefixes'] ?? [])))),
            'disabled_message' => (string)($json['disabled_message'] ?? '该功能未开通'),
        ];
    }

    /**
     * 指定 skill 在指定租户是否可用
     */
    public static function enabled(string $skill, ?int $storeId): bool
    {
        $config = self::config($skill);
        if ($config['allow_all']) return true;

        $storeId = (int)$storeId;
        return $storeId > 0 && in_array($storeId, $config['enabled_stores'], true);
    }

    /**
     * 从类名解析 skill 名：skills\legalpartner\backend\Partner → legalpartner
     */
    public static function skillFromClass(string $class): ?string
    {
        if (preg_match('/^skills\\\\([A-Za-z0-9_]+)\\\\/', $class, $m)) {
            return $m[1];
        }
        return null;
    }

    /**
     * 类所属的 skill 是否对租户开放；非 skill 类直接放行
     */
    public static function enabledForClass(string $class, ?int $storeId): bool
    {
        $skill = self::skillFromClass($class);
        if ($skill === null) return true;
        return self::enabled($skill, $storeId);
    }

    /**
     * 未开通提示
     */
    public static function disabledMessageForClass(string $class): string
    {
        $skill = self::skillFromClass($class);
        return $skill ? (string)self::config($skill)['disabled_message'] : '该功能未开通';
    }

    /**
     * 控制器入口守卫
     *
     * storeId <= 0 时跳过：/v1、CLI 等场景由控制器自行解析租户后再判断。
     */
    public static function guard(string $class, ?int $storeId): void
    {
        $storeId = (int)$storeId;
        if ($storeId <= 0) return;

        if (!self::enabledForClass($class, $storeId)) {
            throwError(self::disabledMessageForClass($class));
        }
    }

    /**
     * 当前租户下，所有被关闭 skill 的菜单前缀
     */
    public static function disabledMenuPrefixes(?int $storeId): array
    {
        $storeId = (int)$storeId;
        if ($storeId <= 0) return [];

        $prefixes = [];
        foreach (self::skillNames() as $skill) {
            if (self::enabled($skill, $storeId)) continue;
            foreach (self::config($skill)['menu_prefixes'] as $prefix) {
                if ($prefix !== '') $prefixes[] = $prefix;
            }
        }

        return array_values(array_unique($prefixes));
    }

    /**
     * 菜单 path 是否对租户可见
     */
    public static function menuEnabled(string $path, ?int $storeId): bool
    {
        $path = (string)$path;
        foreach (self::disabledMenuPrefixes($storeId) as $prefix) {
            if (strpos($path, $prefix) === 0) return false;
        }
        return true;
    }

    /**
     * 配置版本（用于权限缓存失效）
     */
    public static function version(): string
    {
        $max = 0;
        foreach (self::skillNames() as $skill) {
            $file = self::skillDir() . $skill . '/manifest.json';
            if (is_file($file)) $max = max($max, (int)filemtime($file));
        }
        return (string)$max;
    }

    /**
     * 清除请求级缓存
     */
    public static function clearCache(): void
    {
        self::$configs = [];
        self::$skills = null;
    }

    // ── private ──

    private static function defaults(): array
    {
        return [
            'allow_all'        => true,
            'enabled_stores'  => [],
            'menu_prefixes'   => [],
            'disabled_message'=> '该功能未开通',
        ];
    }

    private static function skillDir(): string
    {
        return rtrim(root_path('skills'), '/\\') . '/';
    }

    private static function skillNames(): array
    {
        if (self::$skills !== null) return self::$skills;

        $dirs = glob(self::skillDir() . '*', GLOB_ONLYDIR) ?: [];
        $names = [];
        foreach ($dirs as $dir) {
            $names[] = basename($dir);
        }
        return self::$skills = $names;
    }
}
