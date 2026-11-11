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

namespace app\common\service\system;

use app\common\service\BaseService;
use app\common\model\system\Process as SystemProcessModel;

/**
 * 系统进程服务类
 * Class Process
 * @package app\common\service\system
 */
class Process extends BaseService
{
    /**
     * 获取指定进程最后运行时间
     * @param string $key
     * @return mixed
     */
    public static function getLastWorkingTime(string $key)   
    {
        return (new SystemProcessModel)->where('key', '=', $key)->value('last_working_time');
    }

    /**
     * 记录指定进程最后运行时间
     * @param string $key
     * @return bool
     */
    public static function setLastWorkingTime(string $key): bool
    {
        return SystemProcessModel::updateOne(['last_working_time' => \time()], ['key' => $key]);
    }
}