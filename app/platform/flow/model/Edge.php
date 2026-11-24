<?php

declare(strict_types=1);

namespace app\platform\flow\model;

use cores\BaseModel;

/**
 * 流程连线
 */
class Edge extends BaseModel
{
    protected $name = 'flow_edge';
    protected $autoWriteTimestamp = false;
}
