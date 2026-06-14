<?php

namespace cores;

/**
 * Skill 独立自动加载 —— 零 Proxy 路由
 *
 * 仅处理 skills/* 的 controller 路由，不做 MPP 搜索，不做跨 skill fallback。
 *
 * 映射表：
 *   app\platform\controller\  →  skills/{name}/backend/   (PC 后台)
 *   app\api\controller\       →  skills/{name}/api/        (小程序 API)
 *
 * 与 MppAutoload 职责分离：MppAutoload 管 mpp/* 大模块，SkillAutoload 管 skills/* 轻量插件。
 */
class SkillAutoload
{
    /**
     * 命名空间前缀 → 子目录映射
     */
    private static $mappings = [
        ['app\\platform\\controller\\', 'backend'],
        ['app\\api\\controller\\', 'api'],
    ];

    public static function boot(): void
    {
        spl_autoload_register([self::class, 'load'], true, false);
    }

    public function handle(): void
    {
        self::boot();
    }

    /**
     * Skill controller 自动发现
     *
     * 查找逻辑：
     *   1. 从 URL 提取 skill 名和 class 名
     *   2. 拼真实类名: skills\{name}\{layer}\{Class}
     *   3. class_exists → class_alias
     *   4. 找不到返回 false（不 fallback）
     */
    public static function load(string $class): bool
    {
        foreach (self::$mappings as $mapping) {
            [$prefix, $layer] = $mapping;
            if (strpos($class, $prefix) !== 0) {
                continue;
            }

            $relative = substr($class, strlen($prefix));

            // 有子目录: app\platform\controller\invoice\Invoice → module=invoice, rest=Invoice
            $pos = strpos($relative, '\\');
            if ($pos !== false) {
                $module = substr($relative, 0, $pos);
                $rest   = substr($relative, $pos + 1);

                // 只搜 skills
                if (self::tryAlias("skills\\{$module}\\{$layer}\\{$rest}", $class)) {
                    return true;
                }
                continue;
            }

            // 扁平结构: app\platform\controller\Index → 扫描所有 skills
            foreach (self::getSkillNames() as $name) {
                if (self::tryAlias("skills\\{$name}\\{$layer}\\{$relative}", $class)) {
                    return true;
                }
            }
        }

        return false;
    }

    private static function tryAlias(string $realClass, string $alias): bool
    {
        if (class_exists($realClass, true) || trait_exists($realClass, true) || interface_exists($realClass, true)) {
            class_alias($realClass, $alias);
            return true;
        }
        return false;
    }

    /** @var array|null 缓存的 skill 名称列表 */
    private static $skillNames = null;

    /**
     * 获取所有 skill 名称（扫描 skills/* 目录）
     */
    private static function getSkillNames(): array
    {
        if (self::$skillNames !== null) {
            return self::$skillNames;
        }
        self::$skillNames = [];
        $baseDir = dirname(__DIR__) . '/skills';
        if (!is_dir($baseDir)) return self::$skillNames;
        foreach (glob($baseDir . '/*', GLOB_ONLYDIR) as $dir) {
            $name = basename($dir);
            if ($name === 'common') continue;
            self::$skillNames[] = $name;
        }
        return self::$skillNames;
    }
}
