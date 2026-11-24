<?php

declare(strict_types=1);

namespace app\platform\flow\model;

use cores\BaseModel;

/**
 * 流程实例
 */
class Instance extends BaseModel
{
    protected $name = 'flow_instance';
    protected $autoWriteTimestamp = true;
    protected $dateFormat = false;

    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_TERMINATED = 'terminated';

    public static function statusMap(): array
    {
        return [
            self::STATUS_RUNNING => '进行中',
            self::STATUS_COMPLETED => '已完成',
            self::STATUS_REJECTED => '已驳回',
            self::STATUS_CANCELLED => '已取消',
            self::STATUS_TERMINATED => '已终止',
        ];
    }

    public function detail(int $id)
    {
        return $this->where('id', $id)->find();
    }
}
