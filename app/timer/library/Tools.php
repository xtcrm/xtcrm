<?php
// +----------------------------------------------------------------------
// | XiongTao CRM
// +----------------------------------------------------------------------
// | Copyright (c) 2017~2025 https://www.xtocn.com
// +----------------------------------------------------------------------
// | Licensed under the Apache License, Version 2.0
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace app\timer\library;

/**
 * 工具类
 * Class Tools
 * @package app\timer\library
 */
class Tools
{
    /**
     * 为定时任务写日志
     * @param string $taskKey
     * @param string $method
     * @param array $param
     */
    static function taskLogs(string $taskKey, string $method, array $param = [])
    {
        log_record(['name' => '定时任务', 'Task-Key' => $taskKey, 'method' => $method, 'param' => $param]);
    }
}