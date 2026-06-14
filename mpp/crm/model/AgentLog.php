<?php
namespace mpp\crm\model;

use cores\BaseModel;

class AgentLog extends BaseModel
{
    protected $name = 'crm_agent_log';
    protected $autoWriteTimestamp = false;
    protected $createTime = 'create_time';
    
}
