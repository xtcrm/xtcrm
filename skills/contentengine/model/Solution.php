<?php
namespace skills\contentengine\model;

use cores\BaseModel;

class Solution extends BaseModel
{
    protected $name = 'crm_solution';
    protected $autoWriteTimestamp = true;

    public function getList(array $where = [], array $param = [])
    {
        $query = $this->where('status', 1);
        if (!empty($where['keyword'])) {
            $query->where('title|target_industry', 'like', '%' . $where['keyword'] . '%');
        }
        return $query->order(['sort_order' => 'asc', 'id' => 'desc'])->paginate($param);
    }

    public function add(array $data): bool
    {
        $data['store_id'] = static::$storeId;
        if (empty($data['title'])) { $this->error = '方案名称不能为空'; return false; }
        $data['create_time'] = time();
        $data['update_time'] = time();
        return $this->save($data) !== false;
    }

    public function edit(array $data): bool
    {
        if (empty($data['title'])) { $this->error = '方案名称不能为空'; return false; }
        $data['update_time'] = time();
        return $this->save($data) !== false;
    }
}
