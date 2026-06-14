<?php

declare (strict_types=1);

namespace app\platform\migration;

/**
 * 平台数据库迁移执行器
 *
 * 扫描 app/platform/migrations/ 目录，按序号顺序执行未跑过的 .sql 文件。
 * 追踪记录复用 yoshop_skill_migration 表（skill_name='platform'）。
 *
 * 启动顺序：Platform Runner → ModuleLoader → SkillLoader
 */
class Runner
{
    private const SKILL_NAME = 'platform';

    /**
     * 启动迁移（AppInit 时调用，早于 ModuleLoader 和 SkillLoader）
     */
    public static function boot(): void
    {
        $dir = __DIR__ . '/../migrations';
        if (!is_dir($dir)) return;

        $files = glob($dir . '/*.sql');
        if (empty($files)) return;

        sort($files, SORT_STRING);

        foreach ($files as $file) {
            $filename = basename($file);
            $version = self::extractVersion($filename);

            if (self::isExecuted($filename)) continue;

            try {
                self::executeFile($file, $version, $filename);
            } catch (\Throwable $e) {
                self::logError($filename, $e->getMessage());
            }
        }
    }

    /**
     * 执行单个 SQL 文件
     */
    private static function executeFile(string $file, string $version, string $filename): void
    {
        $sql = file_get_contents($file);
        if (empty(trim($sql))) return;

        $statements = self::splitSql($sql);
        $db = \think\facade\Db::connect();

        foreach ($statements as $stmt) {
            $stmt = trim($stmt);
            if (empty($stmt)) continue;
            $db->execute($stmt);
        }

        self::markExecuted($version, $filename);

        $logFile = runtime_path() . 'log' . DIRECTORY_SEPARATOR . 'platform_migrations.log';
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
        @file_put_contents($logFile, date('Y-m-d H:i:s') . " [platform] {$filename} ✓\n", FILE_APPEND);
    }

    /**
     * 从文件名提取版本号
     * 格式：001_0.1.0_desc.sql → 0.1.0
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
    private static function isExecuted(string $filename): bool
    {
        try {
            return \think\facade\Db::name('skill_migration')
                ->where('skill_name', self::SKILL_NAME)
                ->where('filename', $filename)
                ->count() > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private static function markExecuted(string $version, string $filename): void
    {
        \think\facade\Db::name('skill_migration')->insertGetId([
            'skill_name' => self::SKILL_NAME,
            'version' => $version,
            'filename' => $filename,
            'executed_at' => time(),
            'store_id' => 0,
        ]);
    }

    /**
     * 拆分 SQL 语句（按分号分隔，忽略注释）
     */
    private static function splitSql(string $sql): array
    {
        // 去掉行注释 --
        $sql = preg_replace('/--.*$/m', '', $sql);
        // 去掉块注释 /* */
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
     * 记录迁移错误
     */
    private static function logError(string $filename, string $error): void
    {
        $logFile = runtime_path() . 'log' . DIRECTORY_SEPARATOR . 'platform_migrations.log';
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
        @file_put_contents($logFile, date('Y-m-d H:i:s') . " [platform] {$filename} ✗ {$error}\n", FILE_APPEND);
    }
}
