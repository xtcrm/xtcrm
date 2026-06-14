<?php
namespace skills\invoice\model;

use cores\BaseModel;

class InvoiceCompany extends BaseModel
{
    protected $name = 'invoice_company';
    protected $autoWriteTimestamp = true;

    /**
     * 获取会员绑定的公司
     */
    public function getByMemberId(int $memberId, int $storeId)
    {
        return $this->db()
            ->alias('c')
            ->join('invoice_company_member m', 'c.id = m.company_id')
            ->where('c.store_id', $storeId)
            ->where('m.member_id', $memberId)
            ->where('m.status', 1)
            ->where('c.is_delete', 0)
            ->field('c.*')
            ->find();
    }

    public function getList(array $where = [], array $param = [])
    {
        $query = $this->db()->where('is_delete', 0);
        // 精确匹配字段
        foreach (['id', 'status', 'store_id'] as $f) {
            if (isset($where[$f]) && $where[$f] !== '') $query->where($f, $where[$f]);
            unset($where[$f]);
        }
        // 模糊搜索
        if (!empty($where['name'])) $query->where('name', 'like', "%{$where['name']}%");
        if (!empty($where['tax_number'])) $query->where('tax_number', 'like', "%{$where['tax_number']}%");
        return $query->order('id desc')->paginate($param);
    }

    public function detail(int $id)
    {
        return $this->db()->where('id', $id)->find();
    }

    public function add(array $data)
    {
        $data['store_id'] = static::$storeId;
        if (empty($data['name'])) { $this->error = '公司名称不能为空'; return false; }
        if (empty($data['tax_number'])) { $this->error = '税号不能为空'; return false; }
        // 检查税号唯一性
        $exist = $this->db()
            ->where('store_id', static::$storeId)
            ->where('tax_number', $data['tax_number'])
            ->count();
        if ($exist > 0) { $this->error = '该税号已存在'; return false; }
        return $this->save($data);
    }

    public function edit(array $data)
    {
        $id = $data['id'];
        unset($data['id']);
        return $this->db()->where('id', $id)->update($data) !== false;
    }

    public function toggleStatus(int $id)
    {
        $row = $this->db()->where('id', $id)->find();
        if (!$row) { $this->error = '公司不存在'; return false; }
        $newStatus = $row['status'] == 1 ? 0 : 1;
        return $this->where('id', $id)->update(['status' => $newStatus]);
    }
}
