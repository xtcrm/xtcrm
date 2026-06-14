<?php
namespace mpp\crm\model;

use cores\BaseModel;

class Knowledge extends BaseModel
{
    protected $name = 'crm_knowledge';
    protected $autoWriteTimestamp = true;
    
    public function getList($where = [], $param = [])
    {
        $query = $this->where('is_delete', 0);
        if (!empty($where['category'])) $query->where('category', $where['category']);
        if (!empty($where['keyword'])) $query->where('title|content|tags', 'like', '%' . $where['keyword'] . '%');
        return $query->order(['sort_order' => 'asc', 'id' => 'desc'])->paginate($param);
    }

    public function add($data)
    {
        $data['store_id'] = static::$storeId;
        if (empty($data['title'])) { $this->error = '标题不能为空'; return false; }
        return $this->save($data);
    }

    public function edit($data)
    {
        if (empty($data['title'])) { $this->error = '标题不能为空'; return false; }
        return $this->save($data);
    }
}
