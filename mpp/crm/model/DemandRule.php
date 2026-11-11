<?php
namespace mpp\crm\model;

use cores\BaseModel;

class DemandRule extends BaseModel
{
    protected $name = 'crm_demand_rule';
    protected $autoWriteTimestamp = true;

    public function getEnabledRules($storeId = 0)
    {
        return $this->where('status', 1)
            ->where('store_id', $storeId)
            ->order(['priority' => 'asc', 'id' => 'asc'])
            ->select()
            ->toArray();
    }
}
