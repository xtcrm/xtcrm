<?php

declare(strict_types=1);

namespace app\platform\flow\model;

use cores\BaseModel;

/**
 * 流程令牌（并行时一个实例多个活跃令牌）
 */
class Token extends BaseModel
{
    protected $name = 'flow_token';
    protected $autoWriteTimestamp = false;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_WAITING = 'waiting';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
}
