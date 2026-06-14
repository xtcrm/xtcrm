<?php
namespace mpp\crm\model;

use cores\BaseModel;

class Insight extends BaseModel
{
    protected $name = 'crm_insight';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    
}
