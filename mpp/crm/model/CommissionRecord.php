<?php
namespace mpp\crm\model;

use cores\BaseModel;

/**
 * 业绩确认记录（回款时生成；退款生成负数行冲销）
 */
class CommissionRecord extends BaseModel
{
    protected $name = 'crm_commission_record';
    protected $autoWriteTimestamp = true;
    protected $dateFormat = false;
}
