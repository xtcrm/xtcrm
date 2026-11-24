<?php

declare(strict_types=1);

namespace app\platform\flow\model;

use cores\BaseModel;

/**
 * 流程待办
 */
class Task extends BaseModel
{
    protected $name = 'flow_task';
    protected $autoWriteTimestamp = true;
    protected $dateFormat = false;

    public const STATUS_PENDING = 'pending';
    public const STATUS_CLAIMED = 'claimed';
    public const STATUS_DONE = 'done';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_TRANSFERRED = 'transferred';
    public const STATUS_CANCELLED = 'cancelled';

    public const OPEN_STATUSES = [self::STATUS_PENDING, self::STATUS_CLAIMED];

    public static function statusMap(): array
    {
        return [
            self::STATUS_PENDING => '待处理',
            self::STATUS_CLAIMED => '处理中',
            self::STATUS_DONE => '已完成',
            self::STATUS_REJECTED => '已驳回',
            self::STATUS_TRANSFERRED => '已转交',
            self::STATUS_CANCELLED => '已取消',
        ];
    }

    public function detail(int $id)
    {
        return $this->where('id', $id)->find();
    }
}
