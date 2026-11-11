<?php
namespace mpp\crm\model;

use cores\BaseModel;

class TagGroup extends BaseModel
{
    protected $name = 'crm_tag_group';
    protected $autoWriteTimestamp = true;

    public function getList(array $where = []): array
    {
        $query = $this->db()->where('store_id', static::$storeId);
        if (!empty($where['target_type'])) {
            $query->where('target_type', $where['target_type']);
        }
        return $query->order('sort_order', 'asc')->select()->toArray();
    }

    public function detail(int $id)
    {
        return $this->db()->where('id', $id)->where('store_id', static::$storeId)->find();
    }

    public function add(array $data): bool
    {
        $data['store_id'] = static::$storeId;
        if (empty($data['name'])) {
            $this->error = '分组名称不能为空';
            return false;
        }
        return $this->save($data);
    }

    public function edit(array $data): bool
    {
        if (empty($data['name'])) {
            $this->error = '分组名称不能为空';
            return false;
        }
        return $this->save($data);
    }

    /**
     * 删除分组：检查分组下是否有标签
     */
    public function deleteGroup(int $id): bool
    {
        $tagCount = (new Tag())->db()
            ->where('group_id', $id)
            ->where('store_id', static::$storeId)
            ->count();
        if ($tagCount > 0) {
            $this->error = "该分组下有 {$tagCount} 个标签，请先删除或移走标签";
            return false;
        }
        return $this->db()->where('id', $id)->where('store_id', static::$storeId)->delete() !== false;
    }
}
