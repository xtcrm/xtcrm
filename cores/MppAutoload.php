<?php

namespace cores;

/**
 * MPP/Skill 自动发现 —— 零 Proxy 路由
 *
 * 当 ThinkPHP 尝试加载 app\store\controller\crm\Customer 时，
 * 如果文件不在 app/ 下（已迁到 mpp/crm/），Composer PSR-4 找不到，
 * 此 fallback 用 class_alias() 创建别名指向真实类。
 *
 * 查找顺序：
 *   1. mpp\{module}\{layer}\{Class}
 *   2. skills\{module}\{layer}\{Class}
 */
class MppAutoload
{
    /**
     * 命名空间前缀 → mpp/skill 下的子目录映射
     * 仅处理控制器层路由（ThinkPHP URL 兼容需要 app\* 命名空间）
     * model/service 直接引用 mpp\* / skills\* 真实路径，不走这里
     */
    private static $mappings = [
        ['app\\platform\\controller\\', 'backend'],
        ['app\\platform\\controller\\', 'api'],
        ['app\\api\\controller\\', 'api'],
        ['app\\timer\\controller\\', 'timer'],
    ];

    public static function boot(): void
    {
        spl_autoload_register([self::class, 'load'], false, false);
    }

    public function handle(): void
    {
        self::boot();
    }

    /**
     * 控制器路由自动发现
     *
     * 隔离策略：
     *   - MPP 模块：严格隔离，只搜 URL 中指定的模块，不跨模块 fallback
     *   - Skills：允许跨模块映射（如 /crm.editor → skills/contentengine/）
     *   - Shop 子目录（goods/order 等）：只搜 mpp\shop\，不搜其他模块
     */
    public static function load(string $class): bool
    {
        foreach (self::$mappings as $mapping) {
            [$prefix, $layer] = $mapping;
            if (strpos($class, $prefix) !== 0) {
                continue;
            }

            $relative = substr($class, strlen($prefix));

            // 情况1: 有子目录 — app\store\controller\crm\Customer → module=crm, rest=Customer
            $pos = strpos($relative, '\\');
            if ($pos !== false) {
                $module = substr($relative, 0, $pos);
                $rest   = substr($relative, $pos + 1);

                // Step 1: 精确匹配 MPP 模块
                if (self::tryAlias("mpp\\{$module}\\{$layer}\\{$rest}", $class)) {
                    return true;
                }

                // Step 1.5: 同模块子目录回退
                // URL /admin/admin.user → module=admin, rest=User
                // mpp\admin\backend\User 不存在，但 mpp\admin\backend\admin\User 存在
                if (self::tryAlias("mpp\\{$module}\\{$layer}\\{$module}\\{$rest}", $class)) {
                    return true;
                }

                // Step 2: 检查是否为某个 MPP 模块的子目录
                // 扫描 mpp/*/backend/ 下的子目录，建立映射：goods → shop, customer → crm...
                if (self::tryMppSubDir($module, $rest, $layer, $class)) {
                    return true;
                }

                // Step 3: 精确匹配 Skills 模块
                if (self::tryAlias("skills\\{$module}\\{$layer}\\{$rest}", $class)) {
                    return true;
                }

                // Step 4: Skills 跨模块 fallback（仅 skills，MPP 不参与）
                // URL /crm.editor → module=crm，但代码在 skills/contentengine/
                foreach (self::getModules('skills') as $mod) {
                    if ($mod === $module) continue;
                    $realClass = "skills\\{$mod}\\{$layer}\\{$rest}";
                    if (self::tryAlias($realClass, $class)) {
                        return true;
                    }
                }

                // Step 5: 用完整 relative 路径重试 skills
                foreach (self::getModules('skills') as $mod) {
                    $realClass = "skills\\{$mod}\\{$layer}\\{$relative}";
                    if (self::tryAlias($realClass, $class)) {
                        return true;
                    }
                }
                // 继续尝试下一个 prefix 映射（例如 backend→api 回退）
                continue;
            }

            // 情况2: 扁平结构 — app\store\controller\Order
            // 优先 mpp\shop\，因为它有最多的扁平 controller
            if (self::tryAlias("mpp\\shop\\{$layer}\\{$relative}", $class)) return true;

            // 再搜其他 MPP 模块
            foreach (self::getModules('mpp') as $module) {
                if ($module === 'shop') continue; // 上面已试过
                $realClass = "mpp\\{$module}\\{$layer}\\{$relative}";
                if (self::tryAlias($realClass, $class)) {
                    return true;
                }
            }

            // 最后搜 Skills
            foreach (self::getModules('skills') as $module) {
                $realClass = "skills\\{$module}\\{$layer}\\{$relative}";
                if (self::tryAlias($realClass, $class)) {
                    return true;
                }
            }
        }

        return false;
    }

    // ========== 子目录映射缓存 ==========
    /** @var array<string, string>|null 子目录名 → MPP模块名，如 'goods' → 'shop' */
    private static $subDirMap = null;

    /**
     * 构建子目录 → 模块映射表（首次调用扫描一次，后续 O(1) 哈希查找）
     * 例如: goods → shop, customer → crm
     */
    private static function getMppSubDirMap(string $layer): array
    {
        if (self::$subDirMap !== null) {
            return self::$subDirMap;
        }
        self::$subDirMap = [];
        $baseDir = dirname(__DIR__);
        foreach (self::getModules('mpp') as $mppModule) {
            $layerDir = "{$baseDir}/mpp/{$mppModule}/{$layer}";
            if (!is_dir($layerDir)) continue;
            foreach (glob($layerDir . '/*', GLOB_ONLYDIR) as $subDir) {
                self::$subDirMap[basename($subDir)] = $mppModule;
            }
        }
        return self::$subDirMap;
    }

    /**
     * 尝试将 $module 解析为某个 MPP 模块的子目录
     * 例如: module=goods → 查映射表得到 shop → mpp\shop\backend\goods\Comment
     */
    private static function tryMppSubDir(string $module, string $rest, string $layer, string $alias): bool
    {
        $map = self::getMppSubDirMap($layer);
        if (isset($map[$module])) {
            return self::tryAlias("mpp\\{$map[$module]}\\{$layer}\\{$module}\\{$rest}", $alias);
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

    /** @var array<string, array> 缓存的模块列表，按类型分组 */
    private static $modulesCache = [];

    /**
     * 获取指定类型的所有模块名
     * @param string $type 'mpp' 或 'skills'
     */
    private static function getModules(string $type): array
    {
        if (isset(self::$modulesCache[$type])) {
            return self::$modulesCache[$type];
        }
        self::$modulesCache[$type] = [];
        $baseDir = dirname(__DIR__);
        $typeDir = $baseDir . '/' . $type;
        if (!is_dir($typeDir)) return self::$modulesCache[$type];
        foreach (glob($typeDir . '/*', GLOB_ONLYDIR) as $dir) {
            $name = basename($dir);
            if ($name === 'common') continue;
            self::$modulesCache[$type][] = $name;
        }
        return self::$modulesCache[$type];
    }
}
