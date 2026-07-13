<?php
namespace mpp\crm\model;

use cores\BaseModel;

class Contact extends BaseModel
{
    protected $name = 'crm_contact';
    protected $autoWriteTimestamp = true;

    /**
     * 按手机号查找（同 store）
     */
    public function getByMobile(string $mobile, int $storeId)
    {
        if (empty($mobile)) return null;
        return $this->db()
            ->where('store_id', $storeId)
            ->where('mobile', $mobile)
            ->where('is_delete', 0)
            ->find();
    }

    /**
     * 联系人列表（带关联公司数）
     */
    public function getList(array $where = [], array $param = [])
    {
        $query = $this->db()->alias('ct')
            ->where('ct.is_delete', 0);

        if (!empty($where['store_id'])) $query->where('ct.store_id', $where['store_id']);
        if (!empty($where['keyword'])) {
            $kw = $where['keyword'];
            $query->where(function ($q) use ($kw) {
                $q->where('ct.contact_name', 'like', "%{$kw}%")
                  ->whereOr('ct.mobile', 'like', "%{$kw}%");
            });
        }

        return $query->field('ct.*')
            ->order('ct.id', 'desc')
            ->paginate($param);
    }

    public function detail(int $id)
    {
        return $this->db()->where('id', $id)->where('is_delete', 0)->find();
    }

    public function add(array $data)
    {
        $data['store_id'] = static::$storeId;
        if (empty($data['contact_name'])) {
            $this->error = '联系人姓名不能为空';
            return false;
        }
        if (empty($data['mobile'])) {
            $this->error = '手机号不能为空';
            return false;
        }
        return $this->save($data);
    }

    public function edit(array $data)
    {
        if (empty($data['contact_name'])) {
            $this->error = '联系人姓名不能为空';
            return false;
        }
        return $this->save($data);
    }
}
