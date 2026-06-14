<?php
namespace skills\contentengine\model;

use cores\BaseModel;

class CompanyProfile extends BaseModel
{
    protected $name = 'crm_company_profile';
    protected $autoWriteTimestamp = true;

    public function getByStoreId(int $storeId)
    {
        $row = $this->where('store_id', $storeId)->find();
        return $row ? $row->toArray() : null;
    }

    public function saveProfile(array $data, int $storeId): bool
    {
        $existing = $this->where('store_id', $storeId)->find();
        $data['store_id'] = $storeId;
        $data['update_time'] = time();
        if ($existing) {
            // save() 必须调在模型实例上，Query Builder 用 update()
            $existing->save($data);
            return true;
        }
        $data['create_time'] = time();
        return $this->save($data) !== false;
    }
}
