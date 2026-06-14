<?php
namespace mpp\crm\model;

use cores\BaseModel;

class Contact extends BaseModel
{
    protected $name = 'crm_contact';
    protected $autoWriteTimestamp = true;
    
    public function getList($customerId, $param = [])
    {
        return $this->where('customer_id', $customerId)
            ->where('is_delete', 0)
            ->order(['is_primary' => 'desc', 'id' => 'desc'])
            ->paginate($param);
    }

    public function detail($id)
    {
        return $this->where('id', $id)->find();
    }

    public function add($data)
    {
        $data['store_id'] = static::$storeId;
        if (empty($data['customer_id'])) {
            $this->error = '所属客户不能为空';
            return false;
        }
        if (empty($data['contact_name'])) {
            $this->error = '联系人姓名不能为空';
            return false;
        }
        // 如果设置为首要联系人，先取消该客户的其他首要联系人
        if (!empty($data['is_primary'])) {
            $this->where('customer_id', $data['customer_id'])->update(['is_primary' => 0]);
        }
        return $this->save($data);
    }

    public function edit($data)
    {
        if (empty($data['contact_name'])) {
            $this->error = '联系人姓名不能为空';
            return false;
        }
        // 如果设置为首要联系人，先取消该客户的其他首要联系人
        if (!empty($data['is_primary'])) {
            (new static)->where('customer_id', $this->customer_id)
                ->where('id', '<>', $this->id)
                ->update(['is_primary' => 0]);
        }
        return $this->save($data);
    }
}
