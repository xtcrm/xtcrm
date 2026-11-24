<?php

declare(strict_types=1);

namespace app\platform\flow\model;

use cores\BaseModel;

/**
 * 流程节点
 */
class Node extends BaseModel
{
    protected $name = 'flow_node';
    protected $autoWriteTimestamp = false;

    public const TYPE_START = 'start';
    public const TYPE_TASK = 'task';
    public const TYPE_APPROVAL = 'approval';
    public const TYPE_SIGN = 'sign';
    public const TYPE_PAYMENT = 'payment';
    public const TYPE_GATEWAY = 'gateway';
    public const TYPE_END = 'end';

    public const TASK_TYPES = [
        self::TYPE_TASK => '任务',
        self::TYPE_APPROVAL => '审批',
        self::TYPE_SIGN => '签字',
        self::TYPE_PAYMENT => '收款',
    ];

    public static function typeMap(): array
    {
        return [
            self::TYPE_START => '开始',
            self::TYPE_TASK => '任务',
            self::TYPE_APPROVAL => '审批',
            self::TYPE_SIGN => '签字',
            self::TYPE_PAYMENT => '收款',
            self::TYPE_GATEWAY => '网关',
            self::TYPE_END => '结束',
        ];
    }

    public function isTaskNode(): bool
    {
        return isset(self::TASK_TYPES[(string)$this->getData('type')]);
    }
}
