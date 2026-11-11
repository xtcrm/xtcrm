<?php
namespace mpp\crm\model;

use cores\BaseModel;

class ContactTag extends BaseModel
{
    protected $name = 'crm_contact_tag';
    protected $autoWriteTimestamp = true;
    protected $globalScope = [];  // 中间表无 store_id 列

    /**
     * 获取联系人的所有标签（含标签详情）
     */
    public function getByContact(int $contactId): array
    {
        return $this->db()->alias('ct')
            ->join('yoshop_crm_tag t', 't.id = ct.tag_id')
            ->leftJoin('yoshop_crm_tag_group g', 'g.id = t.group_id')
            ->where('ct.contact_id', $contactId)
            ->field('ct.id as link_id, t.id, t.name, t.color, t.group_id, g.name as group_name')
            ->order('t.sort_order', 'asc')
            ->select()->toArray();
    }

    /**
     * 获取某标签下的所有联系人 ID
     */
    public function getContactIdsByTagId(int $tagId): array
    {
        $rows = $this->db()->where('tag_id', $tagId)->column('contact_id');
        return $rows ?: [];
    }

    /**
     * 替换联系人的标签集（先删后插）
     */
    public function setTags(int $contactId, array $tagIds): bool
    {
        $this->db()->where('contact_id', $contactId)->delete();
        if (empty($tagIds)) return true;
        $rows = [];
        foreach ($tagIds as $tagId) {
            $rows[] = [
                'contact_id'  => $contactId,
                'tag_id'      => $tagId,
                'create_time' => time(),
            ];
        }
        return $this->saveAll($rows) !== false;
    }
}
