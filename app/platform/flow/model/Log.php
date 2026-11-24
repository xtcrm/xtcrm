<?php

declare(strict_types=1);

namespace app\platform\flow\model;

use cores\BaseModel;

/**
 * 流程日志（只追加）
 */
class Log extends BaseModel
{
    protected $name = 'flow_log';
    protected $autoWriteTimestamp = false;
}
