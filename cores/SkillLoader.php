<?php

namespace cores;

/**
 * 技能插件加载器
 *
 * 扫描 skills/ 目录，解析每个技能包的 SKILL.md，
 * 注册 PSR-4 命名空间使 ThinkPHP 的 URL 路由能自动发现技能包的 controller。
 *
 * 工作原理：
 *   技能包 controller 放在 skills/{name}/backend/controller/ 下，
 *   但声明 namespace app\store\controller\crm（匹配 ThinkPHP 约定）。
 *   SkillLoader 注册 spl_autoload_register，当 Composer 找不到类时，
 *   遍历所有技能包的 backend 子目录查找对应的 .php 文件。
 */
class SkillLoader
{
    /** @var array 已加载的技能包清单 */
    private static $skills = [];

    /** @var bool 是否已初始化 */
    private static $booted = false;

    /**
     * 启动技能系统（应用入口调用一次）
     */
    public static function boot(): void
    {
        if (self::$booted) return;
        self::$booted = true;

        $baseDir = config('skill.base_dir') ?: root_path('skills');
        if (!is_dir($baseDir)) return;

        // 扫描技能包目录
        self::scan($baseDir);

        // 自动执行数据库迁移（每次启动检查未执行版本）
        self::runMigrations();

        // 注册自动加载器
        self::registerAutoloader();
    }

    /**
     * 扫描 skills/ 目录，解析所有 SKILL.md
     */
    private static function scan(string $baseDir): void
    {
        $dirs = glob($baseDir . '/*', GLOB_ONLYDIR);
        if (empty($dirs)) return;

        $disabled = config('skill.disabled') ?: [];

        foreach ($dirs as $dir) {
            $name = basename($dir);
            if (in_array($name, $disabled)) continue;

            $manifestFile = $dir . '/SKILL.md';
            if (!file_exists($manifestFile)) continue;

            $skill = self::parseManifest($manifestFile, $dir);
            if ($skill) {
                self::$skills[$name] = $skill;
            }
        }
    }

    /**
     * 解析 SKILL.md 的 YAML frontmatter
     */
    private static function parseManifest(string $file, string $baseDir): ?array
    {
        $content = file_get_contents($file);
        if ($content === false) return null;

        // 解析 --- 分隔的 YAML frontmatter
        if (!preg_match('/^---\s*\n(.*?)\n---\s*\n(.*)$/s', $content, $m)) {
            return null;
        }

        $yaml = $m[1];
        $body = trim($m[2]);

        // 简单 YAML 解析（只处理平铺键值对 + 简单数组）
        $meta = self::parseSimpleYaml($yaml);
        if (empty($meta['name'])) {
            $meta['name'] = basename($baseDir);
        }

        $meta['base_dir'] = $baseDir;
        $meta['instructions'] = $body;

        return $meta;
    }

    /**
     * 极简 YAML 解析器（不依赖第三方库）
     * 支持：key: value, key: [item1, item2], 嵌套对象
     */
    private static function parseSimpleYaml(string $yaml): array
    {
        $lines = explode("\n", $yaml);
        $result = [];
        $currentKey = null;
        $currentIndent = 0;
        $nested = [];

        foreach ($lines as $line) {
            // 跳过空行和注释
            if (trim($line) === '' || preg_match('/^\s*#/', $line)) continue;

            // 检测缩进
            if (preg_match('/^(\s*)([a-zA-Z_][a-zA-Z0-9_]*)\s*:\s*(.*)$/', $line, $m)) {
                $indent = strlen($m[1]);
                $key = $m[2];
                $value = trim($m[3]);

                // 简单 JSON 数组：[a, b, c]
                if (preg_match('/^\[(.*)\]$/', $value, $arrMatch)) {
                    $arr = json_decode($value, true);
                    $result[$key] = is_array($arr) ? $arr : array_map('trim', explode(',', $arrMatch[1]));
                }
                // JSON 对象：{"key": "val"}
                elseif (preg_match('/^\{.*\}$/', $value)) {
                    $decoded = json_decode($value, true);
                    $result[$key] = is_array($decoded) ? $decoded : $value;
                }
                // 空值：下一行可能是嵌套
                elseif ($value === '' || $value === '{}') {
                    $result[$key] = [];
                    $currentKey = $key;
                    $currentIndent = $indent + 2;
                }
                // 普通字符串
                else {
                    // 去掉引号
                    if ((strpos($value, '"') === 0 && strrpos($value, '"') === strlen($value) - 1) ||
                        (strpos($value, "'") === 0 && strrpos($value, "'") === strlen($value) - 1)) {
                        $value = substr($value, 1, -1);
                    }
                    $result[$key] = $value;
                }
            }
            // 处理嵌套行（缩进的列表项如 - item）
            elseif ($currentKey && preg_match('/^\s{2,}-\s*(.+)$/', $line, $m)) {
                $result[$currentKey][] = trim($m[1], " '\"");
            }
            // 嵌套键值对
            elseif ($currentKey && preg_match('/^\s{2,}([a-zA-Z_][a-zA-Z0-9_]*)\s*:\s*(.+)$/', $line, $m)) {
                $subKey = $m[1];
                $subVal = trim($m[2], " '\"");
                if (!is_array($result[$currentKey])) $result[$currentKey] = [];
                $result[$currentKey][$subKey] = $subVal;
            }
        }

        return $result;
    }

    /**
     * 注册自动加载器：拦截 ThinkPHP 的 controller/model/service 命名空间，
     * 优先查找技能包目录中的文件
     */
    private static function registerAutoloader(): void
    {
        // 策略：spl_autoload_register 默认追加到末尾。
        // 用 prepend=true 放到 Composer 之前，这样技能包的文件优先被发现。
        // 但必须确保：如果技能包中没找到，能正确 fallback 到 Composer。
        // 通过检查文件是否存在来实现，不存在则返回 false 让 Composer 继续查找。

        $skillDirs = [];
        foreach (self::$skills as $name => $skill) {
            $backendDir = $skill['base_dir'] . '/backend';
            if (is_dir($backendDir)) {
                $skillDirs[$name] = $backendDir;
            }
        }

        if (empty($skillDirs)) return;

        spl_autoload_register(function ($class) use ($skillDirs) {
            // 只处理 app\store 命名空间（controller/model/service）
            $prefixes = [
                'app\\store\\controller\\crm\\' => 'controller',
                'app\\store\\controller\\'      => 'controller',
                'app\\store\\model\\crm\\'      => 'model',
                'app\\store\\model\\'           => 'model',
                'app\\common\\model\\crm\\'     => 'model',
                'app\\common\\model\\'          => 'model',
                'app\\common\\service\\crm\\'   => 'service',
                'app\\common\\service\\'        => 'service',
            ];

            foreach ($prefixes as $prefix => $type) {
                if (strpos($class, $prefix) !== 0) continue;

                // 计算相对于命名空间的类名部分
                $relativeClass = substr($class, strlen($prefix));
                $fileName = str_replace('\\', '/', $relativeClass) . '.php';

                // 在所有技能包中查找
                foreach ($skillDirs as $baseDir) {
                    $filePath = $baseDir . '/' . $type . '/' . $fileName;
                    if (file_exists($filePath)) {
                        require $filePath;
                        return true;
                    }
                }

                // 没找到，返回 false 让 Composer 继续查找
                // (原始位置仍会存在)
                return false;
            }

            return false; // 不匹配任何前缀，让其他自动加载器处理
        }, true, true); // prepend=true 放到 Composer 前面
    }

    /**
     * 自动执行技能包数据库迁移
     * 扫描每个技能包的 migrations/ 目录，按版本号顺序执行未跑过的 .sql 文件
     */
    private static function runMigrations(): void
    {
        if (empty(self::$skills)) return;

        foreach (self::$skills as $name => $skill) {
            $migrationsDir = $skill['base_dir'] . '/migrations';
            if (!is_dir($migrationsDir)) continue;

            // 获取所有 SQL 文件，按文件名排序（文件名约定：{序号}_{版本号}_{描述}.sql）
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

                    // 按分号拆分，逐条执行（跳过空行和注释）
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
                    $logFile = runtime_path() . 'log' . DIRECTORY_SEPARATOR . 'skill_migrations.log';
                    $logDir = dirname($logFile);
                    if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
                    @file_put_contents($logFile, date('Y-m-d H:i:s') . " [{$name}] {$filename} ✓\n", FILE_APPEND);

                } catch (\Throwable $e) {
                    $logFile = runtime_path() . 'log' . DIRECTORY_SEPARATOR . 'skill_migrations.log';
                    $logDir = dirname($logFile);
                    if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
                    @file_put_contents($logFile, date('Y-m-d H:i:s') . " [{$name}] {$filename} ✗ {$e->getMessage()}\n", FILE_APPEND);
                    // 迁移失败不阻塞系统启动，但记录日志
                }
            }
        }
    }

    /**
     * 从文件名提取版本号
     * 格式：001_1.0.0_init.sql → 1.0.0
     */
    private static function extractVersion(string $filename): string
    {
        if (preg_match('/_(\d+\.\d+\.\d+)_/', $filename, $m)) {
            return $m[1];
        }
        // 回退：用整个文件名（去掉扩展名）作为版本
        return pathinfo($filename, PATHINFO_FILENAME);
    }

    /**
     * 检查迁移是否已执行
     */
    private static function isMigrationExecuted(string $skillName, string $version): bool
    {
        try {
            return \think\facade\Db::name('skill_migration')
                ->where('skill_name', $skillName)
                ->where('version', $version)
                ->count() > 0;
        } catch (\Throwable $e) {
            // 表可能还不存在（首次运行）
            return false;
        }
    }

    /**
     * 记录迁移已执行
     */
    private static function markMigrationExecuted(string $skillName, string $version, string $filename): void
    {
        \think\facade\Db::name('skill_migration')->insert([
            'skill_name' => $skillName,
            'version' => $version,
            'filename' => $filename,
            'executed_at' => time(),
            'store_id' => 0,
        ]);
    }

    /**
     * 拆分 SQL 语句（按分号分隔，忽略注释行）
     */
    private static function splitSql(string $sql): array
    {
        // 移除注释
        $sql = preg_replace('/--.*$/m', '', $sql);
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);

        $statements = [];
        $current = '';
        $lines = explode("\n", $sql);

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') continue;

            $current .= $line . "\n";

            // 分号结尾 → 完整语句
            if (substr($trimmed, -1) === ';') {
                $statements[] = $current;
                $current = '';
            }
        }

        // 残留（没有分号结尾的最后一句）
        if (trim($current) !== '') {
            $statements[] = $current;
        }

        return $statements;
    }

    /**
     * 获取已加载的技能包列表
     */
    public static function getSkills(): array
    {
        return self::$skills;
    }

    /**
     * 获取某个技能包的信息
     */
    public static function getSkill(string $name): ?array
    {
        return self::$skills[$name] ?? null;
    }

    /**
     * 热刷新（重新扫描所有技能包）
     */
    public static function refresh(): void
    {
        self::$skills = [];
        self::$booted = false;
        self::boot();
    }
}
