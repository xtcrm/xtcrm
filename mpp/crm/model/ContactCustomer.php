<?php
namespace mpp\crm\model;

use cores\BaseModel;

class ContactCustomer extends BaseModel
{
    protected $name = 'crm_contact_customer';
    protected $autoWriteTimestamp = true;

    /**
     * 查询联系人的所有关联公司
     */
    public function getByContactId(int $contactId): array
    {
        return $this->db()
            ->alias('cc')
            ->join('yoshop_crm_customer c', 'c.id = cc.customer_id AND c.is_delete = 0')
            ->where('cc.contact_id', $contactId)
            ->field('cc.*, c.customer_name')
            ->order('cc.is_primary', 'desc')
            ->select()->toArray();
    }

    /**
     * 查询某客户的所有联系人
     */
    public function getByCustomerId(int $customerId): array
    {
        return $this->db()
            ->alias('cc')
            ->join('yoshop_crm_contact ct', 'ct.id = cc.contact_id AND ct.is_delete = 0')
            ->where('cc.customer_id', $customerId)
            ->field('cc.id as link_id, cc.position, cc.department, cc.is_primary, ct.id, ct.contact_name, ct.mobile, ct.gender, ct.birthday, ct.hometown, ct.email, ct.wechat, ct.telephone, ct.id_card, ct.address')
            ->order('cc.is_primary', 'desc')
            ->select()->toArray();
    }

    /**
     * 绑定联系人到客户（去重）
     */
    public function bind(int $contactId, int $customerId, array $data = []): bool
    {
        $exist = $this->db()
            ->where('contact_id', $contactId)
            ->where('customer_id', $customerId)
            ->find();
        if ($exist) {
            // 更新职位等
            return $this->db()->where('id', $exist['id'])->update([
                'position'   => $data['position'] ?? $exist['position'],
                'department' => $data['department'] ?? $exist['department'],
                'is_primary' => $data['is_primary'] ?? $exist['is_primary'],
                'update_time' => time(),
            ]) !== false;
        }

        // 如果设为首要，先取消该客户其他首要
        if (!empty($data['is_primary'])) {
            $this->db()->where('customer_id', $customerId)->update(['is_primary' => 0]);
        }

        $row = [
            'store_id'    => static::$storeId,
            'contact_id'  => $contactId,
            'customer_id' => $customerId,
            'position'    => $data['position'] ?? '',
            'department'  => $data['department'] ?? '',
            'is_primary'  => $data['is_primary'] ?? 0,
            'create_time' => time(),
            'update_time' => time(),
        ];
        return $this->save($row) !== false;
    }

    /**
     * 解除绑定
     */
    public function unbind(int $contactId, int $customerId): bool
    {
        return $this->db()
            ->where('contact_id', $contactId)
            ->where('customer_id', $customerId)
            ->delete() !== false;
    }
}
