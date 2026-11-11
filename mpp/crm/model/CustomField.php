<?php
namespace mpp\crm\model;

use cores\BaseModel;

class CustomField extends BaseModel
{
    protected $name = 'crm_custom_field';
    protected $autoWriteTimestamp = true;

    public function getList($where = [], $param = [])
    {
        unset($where['s'], $where['page'], $where['limit']);
        $filterWhere = [];
        foreach ($where as $k => $v) {
            if ($v !== '' && $v !== null && in_array($k, ['entity', 'status'])) {
                $filterWhere[$k] = $v;
            }
        }
        return $this->where($filterWhere)
            ->order(['sort' => 'asc', 'id' => 'asc'])
            ->paginate($param);
    }

    public function add($data)
    {
        $data['store_id'] = static::$storeId;
        $data['options'] = is_array($data['options'] ?? null) ? json_encode($data['options'], JSON_UNESCAPED_UNICODE) : ($data['options'] ?? '');
        return $this->save($data);
    }

    public function edit($data)
    {
        $row = $this->where('id', $data['id'])->find();
        if (!$row) return false;
        // 只更新业务字段，防止 create_time 等时间字段被覆盖为非法值
        $allowed = ['field_label', 'field_type', 'options', 'placeholder', 'is_required', 'show_in_list', 'sort', 'status'];
        $update = [];
        foreach ($allowed as $k) {
            if (array_key_exists($k, $data)) $update[$k] = $data[$k];
        }
        if (isset($update['options']) && is_array($update['options'])) {
            $update['options'] = json_encode($update['options'], JSON_UNESCAPED_UNICODE);
        }
        $update['update_time'] = time();
        return $row->save($update);
    }

    public function getActiveFields($entity)
    {
        return $this->where('entity', $entity)
            ->where('status', 1)
            ->order(['sort' => 'asc', 'id' => 'asc'])
            ->select()
            ->toArray();
    }
}
