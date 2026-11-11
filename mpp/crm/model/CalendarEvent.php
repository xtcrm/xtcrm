<?php
namespace mpp\crm\model;

use cores\BaseModel;

class CalendarEvent extends BaseModel
{
    protected $name = 'crm_calendar_event';
    protected $autoWriteTimestamp = true;
    protected $append = ['customer_name'];

    public function getCustomerNameAttr($value, $data)
    {
        if (!empty($data['customer_id'])) {
            return \think\facade\Db::name('crm_customer')
                ->where('id', $data['customer_id'])->value('customer_name') ?: '';
        }
        return '';
    }
}
