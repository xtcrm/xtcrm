<?php
// +----------------------------------------------------------------------
// | weshop商城系统 [ 致力于通过产品和服务，帮助商家高效化开拓市场 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2017~2023 https://www.xtocn.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed Apache-2.0 (see LICENSE)
// +----------------------------------------------------------------------
// | Author: 雄韬科技 <xc@xtocn.com>
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