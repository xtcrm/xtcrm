<?php
namespace mpp\crm\model;

use cores\BaseModel;
class Contract extends BaseModel
{
    protected $name = 'crm_contract';
    protected $autoWriteTimestamp = true;
    protected $append = ['customer_name', 'owner_user_name', 'status_text'];

    // ——— Accessor ———
    public function getCustomerNameAttr($value, $data)
    {
        if (!empty($data['customer_id'])) {
            return \think\facade\Db::name('crm_customer')
                ->where('id', $data['customer_id'])->value('customer_name') ?: '';
        }
        return '';
    }

    public function getOwnerUserNameAttr($value, $data)
    {
        if (!empty($data['owner_user_id'])) {
            $user = \think\facade\Db::name('store_user')
                ->where('store_user_id', $data['owner_user_id'])
                ->field(['real_name', 'user_name'])->find();
            return $user ? ($user['real_name'] ?: $user['user_name']) : '';
        }
        return '';
    }

    public function getStatusTextAttr($value, $data)
    {
        $map = [1 => '草稿', 2 => '已签订', 3 => '履行中', 4 => '已完成', 5 => '已终止'];
        return $map[$data['status'] ?? 0] ?? '';
    }

    public function getList($where = [], $param = [])
    {
        $allowed = ['id','contract_no','customer_id','status','owner_user_id','is_delete'];
        $fw = [];
        foreach ($where as $k => $v) { if (in_array($k, $allowed) && $v !== '' && $v !== null) $fw[$k] = $v; }
        if (!empty($where['customer_name'])) {
            $ids = Customer::where('customer_name','like','%'.$where['customer_name'].'%')->where('is_delete',0)->column('id');
            $fw[] = !empty($ids) ? ['customer_id','in',$ids] : ['customer_id','=',-1];
        }
        // 分离字符串 key 和数字 key，防止 ThinkPHP strpos(0,'|') 崩溃
        $query = $this->where('is_delete',0);
        foreach ($fw as $k => $v) {
            if (is_string($k)) { $query->where($k, $v); } else { $query->where([$v]); }
        }
        return $query->order(['id'=>'desc'])->paginate($param);
    }

    public function detail($id)
    {
        $c = $this->where('id', $id)->find();
        if ($c) {
            $c['items'] = ContractItem::where('contract_id', $id)
                ->where('is_delete', 0)->order(['sort_order' => 'asc'])->select();
        }
        return $c;
    }

    public function add($data)
    {
        $data['store_id'] = static::$storeId;
        if (empty($data['customer_id'])) { $this->error = '请选择客户'; return false; }
        if (empty($data['contract_name'])) { $this->error = '合同名称不能为空'; return false; }
        $data['contract_no'] = $this->generateNo();
        $data['sign_date'] = !empty($data['sign_date']) ? strtotime($data['sign_date']) : null;
        $data['start_date'] = !empty($data['start_date']) ? strtotime($data['start_date']) : null;
        $data['end_date'] = !empty($data['end_date']) ? strtotime($data['end_date']) : null;
        $data['total_amount'] = $this->calcTotal($data['items'] ?? []);
        $data['contract_amount'] = round($data['total_amount'] - floatval($data['discount_amount'] ?? 0), 2);
        $data['currency'] = $data['currency'] ?? 'CNY';
        $data['status'] = 1;
        \think\facade\Db::startTrans();
        try {
            if (!$this->save($data)) { \think\facade\Db::rollback(); return false; }
            if (!empty($data['items'])) $this->saveItems($this->id, $data['items']);
            \think\facade\Db::commit();
            return true;
        } catch (\Exception $e) { \think\facade\Db::rollback(); $this->error = $e->getMessage(); return false; }
    }

    public function edit($data)
    {
        if (empty($data['contract_name'])) { $this->error = '合同名称不能为空'; return false; }
        $data['sign_date'] = !empty($data['sign_date']) ? strtotime($data['sign_date']) : $this->sign_date;
        $data['start_date'] = !empty($data['start_date']) ? strtotime($data['start_date']) : $this->start_date;
        $data['end_date'] = !empty($data['end_date']) ? strtotime($data['end_date']) : $this->end_date;
        $data['total_amount'] = $this->calcTotal($data['items'] ?? []);
        $data['contract_amount'] = round($data['total_amount'] - floatval($data['discount_amount'] ?? 0), 2);
        \think\facade\Db::startTrans();
        try {
            if (!$this->save($data)) { \think\facade\Db::rollback(); return false; }
            ContractItem::where('contract_id', $this->id)->update(['is_delete' => 1]);
            if (!empty($data['items'])) $this->saveItems($this->id, $data['items']);
            \think\facade\Db::commit();
            return true;
        } catch (\Exception $e) { \think\facade\Db::rollback(); $this->error = $e->getMessage(); return false; }
    }

    public function setDelete($id) { $this->is_delete = 1; return $this->save(); }

    protected function generateNo()
    {
        $p = 'CT-' . date('Ymd') . '-';
        $last = $this->where('contract_no', 'like', $p . '%')->order(['id' => 'desc'])->find();
        $seq = $last ? intval(substr($last['contract_no'], -3)) + 1 : 1;
        return $p . str_pad($seq, 3, '0', STR_PAD_LEFT);
    }

    protected function calcTotal($items)
    {
        $t = 0;
        foreach ($items as $i) $t += round(floatval($i['quantity'] ?? 0) * floatval($i['unit_price'] ?? 0), 2);
        return $t;
    }

    protected function saveItems($contractId, $items)
    {
        foreach ($items as $idx => $it) {
            (new ContractItem)->save([
                'store_id' => static::$storeId,
                'contract_id' => $contractId,
                'product_id' => $it['product_id'] ?? null,
                'product_name' => $it['product_name'] ?? '',
                'specification' => $it['specification'] ?? '',
                'unit' => $it['unit'] ?? '',
                'quantity' => floatval($it['quantity'] ?? 0),
                'unit_price' => floatval($it['unit_price'] ?? 0),
                'amount' => round(floatval($it['quantity'] ?? 0) * floatval($it['unit_price'] ?? 0), 2),
                'sort_order' => $idx,
                'create_time' => time(),
                'update_time' => time(),
            ]);
        }
    }
}
