<?php
namespace skills\invoice\model;

use cores\BaseModel;

class InvoiceCompanyMember extends BaseModel
{
    protected $name = 'invoice_company_member';
    protected $autoWriteTimestamp = true;

    /**
     * 绑定会员到公司
     */
    public function bind(int $companyId, int $memberId, int $storeId): bool
    {
        $exist = $this->db()
            ->where('company_id', $companyId)
            ->where('member_id', $memberId)
            ->where('store_id', $storeId)
            ->find();

        if ($exist) {
            if ($exist['status'] == 1) return true; // 已绑定
            // 重新激活
            return (bool) $this->where('id', $exist['id'])->update(['status' => 1]);
        }

        return (bool) $this->save([
            'store_id'   => $storeId,
            'company_id' => $companyId,
            'member_id'  => $memberId,
            'status'     => 1,
        ]);
    }

    /**
     * 解绑会员
     */
    public function unbind(int $companyId, int $memberId): bool
    {
        return (bool) $this->db()
            ->where('company_id', $companyId)
            ->where('member_id', $memberId)
            ->update(['status' => 0]);
    }

    /**
     * 获取公司的所有绑定会员
     */
    public function getMembers(int $companyId): array
    {
        return $this->db()
            ->alias('m')
            ->join('user u', 'm.member_id = u.user_id', 'LEFT')
            ->where('m.company_id', $companyId)
            ->where('m.status', 1)
            ->field('m.member_id, m.create_time as bind_time, m.status, u.nick_name as nickname, u.mobile, u.avatar_id as avatar_url')
            ->select()
            ->toArray();
    }
}
