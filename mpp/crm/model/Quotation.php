<?php
namespace mpp\crm\model;

use cores\BaseModel;

use think\facade\Db;

class Quotation extends BaseModel
{
    protected $name = 'crm_quotation';
    protected $autoWriteTimestamp = true;
    protected $append = ['customer_name', 'owner_user_name', 'status_text'];
    
    public function getList($where = [], $param = [])
    {
        $allowedFields = ['id', 'quotation_no', 'customer_id', 'status', 'owner_user_id', 'is_delete'];
        $filterWhere = [];
        foreach ($where as $key => $value) {
            if (in_array($key, $allowedFields)) $filterWhere[$key] = $value;
        }
        if (!empty($where['customer_name'])) {
            $customerIds = Customer::where('customer_name', 'like', '%' . $where['customer_name'] . '%')
                ->where('is_delete', 0)->column('id');
            $filterWhere[] = !empty($customerIds) ? ['customer_id', 'in', $customerIds] : ['customer_id', '=', -1];
        }
        if (!empty($where['quotation_date_from'])) {
            $filterWhere[] = ['quotation_date', '>=', strtotime($where['quotation_date_from'])];
        }
        if (!empty($where['quotation_date_to'])) {
            $filterWhere[] = ['quotation_date', '<=', strtotime($where['quotation_date_to'] . ' 23:59:59')];
        }
        return $this->where($filterWhere)->where('is_delete', 0)
            ->order(['id' => 'desc'])->paginate($param);
    }

    public function detail($id)
    {
        $q = $this->where('id', $id)->find();
        if ($q) {
            $q['items'] = QuotationItem::where('quotation_id', $id)
                ->where('is_delete', 0)->order(['sort_order' => 'asc'])->select();
        }
        return $q;
    }

    public function add($data)
    {
        $data['store_id'] = static::$storeId;
        if (empty($data['customer_id'])) { $this->error = '请选择客户'; return false; }
        $data['quotation_date'] = !empty($data['quotation_date']) ? strtotime($data['quotation_date']) : time();
        $data['quotation_no'] = $this->generateNo();
        $data['total_amount'] = $this->calcTotal($data['items'] ?? []);
        $data['final_amount'] = round($data['total_amount'] - floatval($data['discount_amount'] ?? 0), 2);
        $data['currency'] = $data['currency'] ?? 'CNY';
        $data['status'] = 1;

        Db::startTrans();
        try {
            if (!$this->save($data)) { Db::rollback(); return false; }
            if (!empty($data['items'])) $this->saveItems($this->id, $data['items']);
            Db::commit();
            return true;
        } catch (\Exception $e) { Db::rollback(); $this->error = $e->getMessage(); return false; }
    }

    public function edit($data)
    {
        if (empty($data['customer_id'])) { $this->error = '请选择客户'; return false; }
        $data['quotation_date'] = !empty($data['quotation_date']) ? strtotime($data['quotation_date']) : $this->quotation_date;
        $data['total_amount'] = $this->calcTotal($data['items'] ?? []);
        $data['final_amount'] = round($data['total_amount'] - floatval($data['discount_amount'] ?? 0), 2);

        Db::startTrans();
        try {
            if (!$this->save($data)) { Db::rollback(); return false; }
            QuotationItem::where('quotation_id', $this->id)->update(['is_delete' => 1]);
            if (!empty($data['items'])) $this->saveItems($this->id, $data['items']);
            Db::commit();
            return true;
        } catch (\Exception $e) { Db::rollback(); $this->error = $e->getMessage(); return false; }
    }

    public function setDelete($id)
    {
        $this->is_delete = 1;
        return $this->save();
    }

    public function changeStatus($id, $status)
    {
        $q = $this->where('id', $id)->find();
        if (!$q) { $this->error = '报价单不存在'; return false; }
        $allowed = [1 => '草稿', 2 => '已发送', 3 => '已确认', 4 => '已拒绝', 5 => '已转订单'];
        if (!isset($allowed[$status])) { $this->error = '无效状态'; return false; }
        $q->status = $status;
        $result = $q->save();
        // 首次发送报价 → 推进漏斗到阶段3
        if ($result && $status == 2 && $q->customer_id) {
            $customer = Customer::where('id', $q->customer_id)->find();
            if ($customer && $customer->funnel_stage < 3) {
                $customer->funnel_stage = 3;
                $customer->save();
            }
        }
        return $result;
    }

    protected function generateNo()
    {
        $prefix = 'Q-' . date('Ymd') . '-';
        $last = $this->where('quotation_no', 'like', $prefix . '%')->order(['id' => 'desc'])->find();
        $seq = $last ? intval(substr($last['quotation_no'], -3)) + 1 : 1;
        return $prefix . str_pad($seq, 3, '0', STR_PAD_LEFT);
    }

    protected function calcTotal($items)
    {
        $total = 0;
        foreach ($items as $item) {
            $total += round(floatval($item['quantity'] ?? 0) * floatval($item['unit_price'] ?? 0), 2);
        }
        return $total;
    }

    protected function saveItems($quotationId, $items)
    {
        foreach ($items as $i => $item) {
            $qty = floatval($item['quantity'] ?? 0);
            $price = floatval($item['unit_price'] ?? 0);
            (new QuotationItem)->save([
                'store_id' => static::$storeId,
                'quotation_id' => $quotationId,
                'product_id' => $item['product_id'] ?? null,
                'product_name' => $item['product_name'] ?? '',
                'specification' => $item['specification'] ?? '',
                'unit' => $item['unit'] ?? '',
                'quantity' => $qty,
                'unit_price' => $price,
                'amount' => round($qty * $price, 2),
                'sort_order' => $i,
                'create_time' => time(),
                'update_time' => time(),
            ]);
        }
    }
}
