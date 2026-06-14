<?php

namespace cores;

/**
 * 模块加载器
 *
 * 扫描 modules/ 目录，解析每个模块的 module.json，
 * 注册 autoloader 将 app namespace 映射到 modules/{name}/backend/ 目录。
 *
 * 与 SkillLoader 的区别：
 *   - 处理大型业务模块（CRM、商城、财税），不是轻量插件
 *   - model 按 common/store 分两个子目录（避免同名文件冲突）
 *   - 支持 timer 命名空间
 *   - 迁移使用 yoshop_skill_migration 表追踪（与 Skill 共用）
 *
 * Autoloader 链：ModuleLoader → SkillLoader → Composer
 */
class ModuleLoader
{
    /** @var array 已加载的模块清单 */
    private static $modules = [];

    /** @var bool 是否已初始化 */
    private static $booted = false;

    /**
     * 启动模块系统（AppInit 时调用，在 SkillLoader 之后）
     */
    public static function boot(): void
    {
        if (self::$booted) return;
        self::$booted = true;

        $baseDir = config('module.base_dir') ?: root_path('modules');
        if (!is_dir($baseDir)) return;

        // 扫描模块目录
        self::scan($baseDir);

        // 自动执行数据库迁移
        self::runMigrations();

        // 注册自动加载器
        self::registerAutoloader();
    }

    /**
     * 扫描 modules/ 目录，解析所有 module.json
     */
    private static function scan(string $baseDir): void
    {
        $dirs = glob($baseDir . '/*', GLOB_ONLYDIR);
        if (empty($dirs)) return;

        $disabled = config('module.disabled') ?: [];

        foreach ($dirs as $dir) {
            $name = basename($dir);
            if (in_array($name, $disabled)) continue;

            $manifestFile = $dir . '/module.json';
            if (!file_exists($manifestFile)) continue;

            $content = file_get_contents($manifestFile);
            if ($content === false) continue;

            $manifest = json_decode($content, true);
            if (!is_array($manifest)) continue;

            if (empty($manifest['name'])) {
                $manifest['name'] = $name;
            }

            $manifest['base_dir'] = $dir;
            self::$modules[$name] = $manifest;
        }
    }

    /**
     * 注册自动加载器
     *
     * 关键：model 拆成 common/ 和 store/ 两个子目录。
     * common model（extends BaseModel）放在 model/common/，
     * store model（extends common model）放在 model/store/。
     * 两者 namespace 不同，同名文件不会冲突。
     */
    private static function registerAutoloader(): void
    {
        $moduleDirs = [];
        foreach (self::$modules as $name => $module) {
            $backendDir = $module['base_dir'] . '/backend';
            if (is_dir($backendDir)) {
                $moduleDirs[$name] = $backendDir;
            }
        }

        if (empty($moduleDirs)) return;

        spl_autoload_register(function ($class) use ($moduleDirs) {
            // namespace 前缀 → 子目录映射
            // 注意：只用带作用域的前缀（crm\、admin\ 等），
            // 不用通用的 app\store\*、app\common\* 前缀，
            // 否则会把 admin 模块的文件错误匹配给 store/common 命名空间。
            $prefixes = [
                'app\\store\\controller\\crm\\' => 'controller',
                'app\\store\\model\\crm\\'      => 'model/store',
                'app\\common\\model\\crm\\'     => 'model/common',
                'app\\common\\service\\crm\\'   => 'service',
                'app\\admin\\controller\\'      => 'controller',
                'app\\admin\\model\\'           => 'model',
                'app\\admin\\service\\'         => 'service',
                'app\\store\\controller\\fiscal\\' => 'controller',
                'app\\store\\model\\fiscal\\'      => 'model/store',
                'app\\common\\model\\fiscal\\'      => 'model/common',
                'app\\api\\controller\\fiscal\\'    => 'api',
                'app\\timer\\controller\\'          => 'timer',
                'app\\timer\\'                      => 'timer',
            ];

            foreach ($prefixes as $prefix => $type) {
                if (strpos($class, $prefix) !== 0) continue;

                // 计算相对于命名空间的类名部分
                $relativeClass = substr($class, strlen($prefix));
                $fileName = str_replace('\\', '/', $relativeClass) . '.php';

                // 在所有模块中查找
                foreach ($moduleDirs as $baseDir) {
                    $filePath = $baseDir . '/' . $type . '/' . $fileName;
                    if (file_exists($filePath)) {
                        require $filePath;
                        // 验证文件声明了正确的类（防止同文件名但不同 namespace 被误匹配）
                        if (class_exists($class, false) || interface_exists($class, false) || trait_exists($class, false)) {
                            return true;
                        }
                        // 类不匹配，继续查下一个模块目录
                    }
                }

                // 所有模块目录都没找到，返回 false 让 SkillLoader 继续查找
                return false;
            }

            return false; // 不匹配任何前缀
        }, true, true); // prepend=true 放到 Composer 和 SkillLoader 前面
    }

    /**
     * 自动执行模块数据库迁移
     *
     * 扫描每个模块的 migrations/ 目录，按文件名排序执行未跑过的 .sql 文件。
     * 复用 yoshop_skill_migration 表追踪执行记录（skill_name 列存模块名）。
     */
    private static function runMigrations(): void
    {
        if (empty(self::$modules)) return;

        foreach (self::$modules as $name => $module) {
            $migrationsDir = ($module['base_dir'] ?? '') . '/migrations';
            if (!is_dir($migrationsDir)) continue;

            $files = glob($migrationsDir . '/*.sql');
            if (empty($files)) continue;

            sort($files, SORT_STRING);

            foreach ($files as $file) {
                $filename = basename($file);
                $version = self::extractVersion($filename);

                // 检查是否已执行
                $executed = self::isMigrationExecuted($name, $version);
                if ($executed) continue;

                // 执行迁移
                try {
                    $sql = file_get_contents($file);
                    if (empty(trim($sql))) continue;

                    $statements = self::splitSql($sql);
                    $db = \think\facade\Db::connect();
                    foreach ($statements as $stmt) {
                        $stmt = trim($stmt);
                        if (empty($stmt)) continue;
                        $db->execute($stmt);
                    }

                    // 记录已执行
                    self::markMigrationExecuted($name, $version, $filename);

                    // 记录日志
                    self::logMigration($name, $filename, true);
                } catch (\Throwable $e) {
                    self::logMigration($name, $filename, false, $e->getMessage());
                }
            }
        }
    }

    /**
     * 从文件名提取版本号
     * 格式：001_2.3.1_desc.sql → 2.3.1
     */
    private static function extractVersion(string $filename): string
    {
        if (preg_match('/_(\d+\.\d+\.\d+)_/', $filename, $m)) {
            return $m[1];
        }
        return pathinfo($filename, PATHINFO_FILENAME);
    }

    /**
     * 检查迁移是否已执行
     */
    private static function isMigrationExecuted(string $moduleName, string $version): bool
    {
        try {
            return \think\facade\Db::name('skill_migration')
                ->where('skill_name', $moduleName)
                ->where('version', $version)
                ->count() > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 记录迁移已执行
     */
    private static function markMigrationExecuted(string $moduleName, string $version, string $filename): void
    {
        \think\facade\Db::name('skill_migration')->insert([
            'skill_name' => $moduleName,
            'version' => $version,
            'filename' => $filename,
            'executed_at' => time(),
            'store_id' => 0,
        ]);
    }

    /**
     * 写迁移日志
     */
    private static function logMigration(string $name, string $filename, bool $success, string $error = ''): void
    {
        $logFile = runtime_path() . 'log' . DIRECTORY_SEPARATOR . 'module_migrations.log';
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
        $status = $success ? '✓' : "✗ {$error}";
        @file_put_contents($logFile, date('Y-m-d H:i:s') . " [{$name}] {$filename} {$status}\n", FILE_APPEND);
    }

    /**
     * 拆分 SQL 语句（按分号分隔，忽略注释）
     */
    private static function splitSql(string $sql): array
    {
        $sql = preg_replace('/--.*$/m', '', $sql);
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);

        $statements = [];
        $current = '';
        $lines = explode("\n", $sql);

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') continue;
            $current .= $line . "\n";
            if (substr($trimmed, -1) === ';') {
                $statements[] = $current;
                $current = '';
            }
        }

        if (trim($current) !== '') {
            $statements[] = $current;
        }

        return $statements;
    }

    /**
     * 获取已加载的模块列表
     */
    public static function getModules(): array
    {
        return self::$modules;
    }

    /**
     * 获取某个模块的信息
     */
    public static function getModule(string $name): ?array
    {
        return self::$modules[$name] ?? null;
    }

    /**
     * 热刷新
     */
    public static function refresh(): void
    {
        self::$modules = [];
        self::$booted = false;
        self::boot();
    }
}
