<?php
namespace mpp\crm\model;

use cores\BaseModel;

class CustomerTag extends BaseModel
{
    protected $name = 'crm_customer_tag';
    protected $autoWriteTimestamp = true;
    protected $globalScope = [];  // 中间表无 store_id 列

    /**
     * 获取客户的所有标签（含标签详情）
     */
    public function getByCustomer(int $customerId): array
    {
        return $this->db()->alias('ct')
            ->join('yoshop_crm_tag t', 't.id = ct.tag_id')
            ->leftJoin('yoshop_crm_tag_group g', 'g.id = t.group_id')
            ->where('ct.customer_id', $customerId)
            ->field('ct.id as link_id, t.id, t.name, t.color, t.group_id, g.name as group_name')
            ->order('t.sort_order', 'asc')
            ->select()->toArray();
    }

    /**
     * 获取某标签下的所有客户 ID
     */
    public function getCustomerIdsByTagId(int $tagId): array
    {
        $rows = $this->db()->where('tag_id', $tagId)->column('customer_id');
        return $rows ?: [];
    }

    /**
     * 按多个标签 ID 查出命中的客户 ID（AND 逻辑：必须同时命中所有标签）
     */
    public function getCustomerIdsByTagIds(array $tagIds): array
    {
        if (empty($tagIds)) return [];
        $rows = $this->db()
            ->whereIn('tag_id', $tagIds)
            ->group('customer_id')
            ->having('COUNT(DISTINCT tag_id) = ' . count($tagIds))
            ->column('customer_id');
        return $rows ?: [];
    }

    /**
     * 替换客户的标签集（先删后插）
     */
    public function setTags(int $customerId, array $tagIds): bool
    {
        // 删除旧关联
        $this->db()->where('customer_id', $customerId)->delete();
        // 批量插入
        if (empty($tagIds)) return true;
        $rows = [];
        foreach ($tagIds as $tagId) {
            $rows[] = [
                'customer_id' => $customerId,
                'tag_id'      => $tagId,
                'create_time' => time(),
            ];
        }
        return $this->saveAll($rows) !== false;
    }
}
