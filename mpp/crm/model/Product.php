<?php
namespace mpp\crm\model;

use cores\BaseModel;

class Product extends BaseModel
{
    protected $name = 'crm_product';
    protected $autoWriteTimestamp = true;
    protected $append = ['status_text', 'category_name'];
    
    public function getList($where = [], $param = [])
    {
        $allowedFields = [
            'id', 'product_name', 'product_code', 'category', 'status',
            'is_delete', 'create_time', 'update_time'
        ];
        $filterWhere = [];
        foreach ($where as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $filterWhere[$key] = $value;
            }
        }
        if (!empty($where['product_name'])) {
            $filterWhere[] = ['product_name', 'like', '%' . $where['product_name'] . '%'];
        }
        return $this->where($filterWhere)
            ->where('is_delete', 0)
            ->order(['sort_order' => 'asc', 'id' => 'desc'])
            ->paginate($param);
    }

    public function select($where = [])
    {
        return $this->where('is_delete', 0)
            ->where('status', 1)
            ->field(['id', 'product_name', 'product_code', 'specification', 'unit', 'reference_price'])
            ->order(['sort_order' => 'asc'])
            ->select();
    }

    public function detail($id)
    {
        return $this->where('id', $id)->find();
    }

    public function add($data)
    {
        $data['store_id'] = static::$storeId;
        if (empty($data['product_name'])) {
            $this->error = '产品名称不能为空';
            return false;
        }
        return $this->save($data);
    }

    public function edit($data)
    {
        if (empty($data['product_name'])) {
            $this->error = '产品名称不能为空';
            return false;
        }
        return $this->save($data);
    }
}
