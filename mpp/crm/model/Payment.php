<?php
namespace mpp\crm\model;

use cores\BaseModel;

class Payment extends BaseModel
{
    protected $name = 'crm_payment';
    protected $autoWriteTimestamp = true;
    protected $dateFormat = false;
    protected $append = ['order_no', 'actor_user_name'];

    public function getOrderNoAttr($value, $data)
    {
        if (empty($data['order_id'])) return '';
        return (string)\think\facade\Db::name('crm_order')
            ->where('store_id', static::$storeId)->where('id', $data['order_id'])->value('order_no');
    }

    public function getActorUserNameAttr($value, $data)
    {
        if (empty($data['actor_user_id'])) return '';
        $user = \think\facade\Db::name('store_user')
            ->where('store_id', static::$storeId)->where('store_user_id', $data['actor_user_id'])
            ->field(['real_name', 'user_name'])->find();
        return $user ? (string)($user['real_name'] ?: $user['user_name']) : '';
    }

    public function getList(int $projectId = 0, int $orderId = 0)
    {
        $query = $this->where('is_delete', 0);
        if ($projectId > 0) $query->where('project_id', $projectId);
        if ($orderId > 0) $query->where('order_id', $orderId);
        return $query->order(['paid_at' => 'desc', 'id' => 'desc'])->select()->toArray();
    }
}
