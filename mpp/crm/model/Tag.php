<?php
namespace mpp\crm\model;

use cores\BaseModel;

class Tag extends BaseModel
{
    protected $name = 'crm_tag';
    protected $autoWriteTimestamp = true;

    public function getList(array $where = []): array
    {
        $query = $this->db()->alias('t')
            ->join('yoshop_crm_tag_group g', 'g.id = t.group_id', 'LEFT')
            ->where('t.store_id', static::$storeId)
            ->field('t.*, g.name as group_name');

        if (!empty($where['target_type'])) {
            $query->where('t.target_type', $where['target_type']);
        }
        if (!empty($where['group_id'])) {
            $query->where('t.group_id', $where['group_id']);
        }
        if (!empty($where['keyword'])) {
            $query->where('t.name', 'like', '%' . $where['keyword'] . '%');
        }

        return $query->order('t.sort_order', 'asc')->select()->toArray();
    }

    public function detail(int $id)
    {
        return $this->db()->where('id', $id)->where('store_id', static::$storeId)->find();
    }

    public function add(array $data): bool
    {
        $data['store_id'] = static::$storeId;
        if (empty($data['name'])) {
            $this->error = '标签名称不能为空';
            return false;
        }
        // 检查同 store 同 target_type 下名称唯一
        $exist = $this->db()
            ->where('store_id', static::$storeId)
            ->where('name', $data['name'])
            ->where('target_type', $data['target_type'] ?? 'customer')
            ->find();
        if ($exist) {
            $this->error = '标签名称已存在';
            return false;
        }
        return $this->save($data);
    }

    public function edit(array $data): bool
    {
        if (empty($data['name'])) {
            $this->error = '标签名称不能为空';
            return false;
        }
        return $this->save($data);
    }

    /**
     * 删除标签：同时删除所有关联的 pivot 行
     */
    public function deleteTag(int $id): bool
    {
        $tag = $this->detail($id);
        if (!$tag) {
            $this->error = '标签不存在';
            return false;
        }
        // 删除客户标签关联
        (new CustomerTag())->db()->where('tag_id', $id)->delete();
        // 删除联系人标签关联
        (new ContactTag())->db()->where('tag_id', $id)->delete();
        // 删除标签本身
        return $this->db()->where('id', $id)->where('store_id', static::$storeId)->delete() !== false;
    }

    /**
     * 获取某 target_type 下所有标签（分组树结构）
     */
    public function getGroupedByTarget(string $targetType): array
    {
        $storeId = static::$storeId;

        $groups = \think\facade\Db::name('crm_tag_group')
            ->where('store_id', $storeId)
            ->where('target_type', $targetType)
            ->order('sort_order', 'asc')
            ->select()->toArray();

        $tags = \think\facade\Db::name('crm_tag')
            ->where('store_id', $storeId)
            ->where('target_type', $targetType)
            ->order('sort_order', 'asc')
            ->select()->toArray();

        // 组装分组树
        $grouped = [];
        $ungroupedTags = [];

        // 先放有分组的
        foreach ($groups as $group) {
            $groupTags = [];
            foreach ($tags as $tag) {
                if ($tag['group_id'] == $group['id']) {
                    $groupTags[] = $tag;
                }
            }
            $grouped[] = [
                'id'   => $group['id'],
                'name' => $group['name'],
                'tags' => $groupTags,
            ];
        }

        // 未分组的标签
        foreach ($tags as $tag) {
            if (empty($tag['group_id'])) {
                $ungroupedTags[] = $tag;
            }
        }
        if (!empty($ungroupedTags)) {
            $grouped[] = [
                'id'   => 0,
                'name' => '未分组',
                'tags' => $ungroupedTags,
            ];
        }

        return $grouped;
    }
}
