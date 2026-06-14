<?php
namespace mpp\crm\model;

use cores\BaseModel;

class EventLog extends BaseModel
{
    protected $name = 'crm_event_log';
    protected $autoWriteTimestamp = false;
    protected $createTime = 'create_time';
    
}
