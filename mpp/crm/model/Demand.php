<?php
namespace mpp\crm\model;

use cores\BaseModel;

class Demand extends BaseModel
{
    protected $name = 'crm_demand';
    protected $autoWriteTimestamp = true;

    public function getCustomerDemands($customerId)
    {
        return $this->where('customer_id', $customerId)
            ->where('status', 1)
            ->order(['create_time' => 'desc'])
            ->select()
            ->toArray();
    }
}
