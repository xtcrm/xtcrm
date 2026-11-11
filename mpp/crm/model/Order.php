<?php
namespace mpp\crm\model;

use cores\BaseModel;
use think\facade\Db;

class Order extends BaseModel
{
    protected $name = 'crm_order';
    protected $autoWriteTimestamp = true;
    protected $append = ['customer_name', 'owner_user_name', 'status_text', 'payment_status_text'];

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
        $map = [1 => '待确认', 2 => '生产中', 3 => '待发货', 4 => '已发货', 5 => '已完成', 6 => '已取消'];
        return $map[$data['status'] ?? 0] ?? '';
    }

    public function getPaymentStatusTextAttr($value, $data)
    {
        $map = [1 => '未付', 2 => '部分', 3 => '已付'];
        return $map[$data['payment_status'] ?? 0] ?? '';
    }

    public function getList($where = [], $param = [])
    {
        $allowed = ['id','order_no','customer_id','status','payment_status','owner_user_id','is_delete'];
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
        $o = $this->where('id', $id)->find();
        if ($o) $o['items'] = OrderItem::where('order_id',$id)->where('is_delete',0)->order(['sort_order'=>'asc'])->select();
        return $o;
    }

    public function add($data)
    {
        $data['store_id'] = static::$storeId;
        if (empty($data['customer_id'])) { $this->error='请选择客户'; return false; }
        $data['order_no'] = $this->generateNo();
        $data['order_date'] = !empty($data['order_date']) ? strtotime($data['order_date']) : time();
        $data['delivery_date'] = !empty($data['delivery_date']) ? strtotime($data['delivery_date']) : null;
        foreach (['service_start_time', 'service_end_time'] as $field) {
            if (!empty($data[$field]) && !is_numeric($data[$field])) {
                $data[$field] = strtotime((string)$data[$field]) ?: null;
            }
            if (isset($data[$field]) && $data[$field] === '') $data[$field] = null;
        }
        $data['total_amount'] = $this->calcTotal($data['items']??[]);
        $data['final_amount'] = round($data['total_amount'] - floatval($data['discount_amount']??0), 2);
        $data['unpaid_amount'] = $data['final_amount'];
        $data['currency'] = $data['currency'] ?? 'CNY';
        $data['status'] = 1;
        Db::startTrans();
        try {
            if (!$this->save($data)) { Db::rollback(); return false; }
            if (!empty($data['items'])) $this->saveItems($this->id, $data['items']);
            // 如果来自报价单，更新报价单状态并推进漏斗
            if (!empty($data['quotation_id'])) {
                $q = Quotation::where('id', $data['quotation_id'])->find();
                if ($q) {
                    $q->status = 5; $q->order_id = $this->id; $q->save();
                    $customer = Customer::where('id', $q->customer_id)->find();
                    if ($customer && $customer->funnel_stage < 5) { $customer->funnel_stage = 5; $customer->save(); }
                }
            }
            Db::commit(); return true;
        } catch (\Exception $e) { Db::rollback(); $this->error=$e->getMessage(); return false; }
    }

    public function edit($data)
    {
        if (empty($data['customer_id'])) { $this->error='请选择客户'; return false; }
        $data['order_date'] = !empty($data['order_date']) ? strtotime($data['order_date']) : $this->order_date;
        $data['delivery_date'] = !empty($data['delivery_date']) ? strtotime($data['delivery_date']) : null;
        foreach (['service_start_time', 'service_end_time'] as $field) {
            if (!empty($data[$field]) && !is_numeric($data[$field])) {
                $data[$field] = strtotime((string)$data[$field]) ?: null;
            }
            if (isset($data[$field]) && $data[$field] === '') $data[$field] = null;
        }
        $data['total_amount'] = $this->calcTotal($data['items']??[]);
        $data['final_amount'] = round($data['total_amount'] - floatval($data['discount_amount']??0), 2);
        Db::startTrans();
        try {
            if (!$this->save($data)) { Db::rollback(); return false; }
            OrderItem::where('order_id', $this->id)->update(['is_delete'=>1]);
            if (!empty($data['items'])) $this->saveItems($this->id, $data['items']);
            Db::commit(); return true;
        } catch (\Exception $e) { Db::rollback(); $this->error=$e->getMessage(); return false; }
    }

    public function setDelete($id) { $this->is_delete=1; return $this->save(); }

    protected function generateNo() {
        $p='SO-'.date('Ymd').'-';
        $last=$this->where('order_no','like',$p.'%')->order(['id'=>'desc'])->find();
        $seq=$last?intval(substr($last['order_no'],-3))+1:1;
        return $p.str_pad($seq,3,'0',STR_PAD_LEFT);
    }
    protected function calcTotal($items){ $t=0; foreach($items as $i) $t+=round(floatval($i['quantity']??0)*floatval($i['unit_price']??0),2); return $t; }
    protected function saveItems($oid,$items){ foreach($items as $i=>$it){ (new OrderItem)->save(['store_id'=>static::$storeId,'order_id'=>$oid,'product_id'=>$it['product_id']??null,'intent_id'=>(int)($it['intent_id']??0),'catalog_id'=>(int)($it['catalog_id']??0),'product_name'=>$it['product_name']??'','specification'=>$it['specification']??'','unit'=>$it['unit']??'','quantity'=>floatval($it['quantity']??0),'unit_price'=>floatval($it['unit_price']??0),'amount'=>round(floatval($it['quantity']??0)*floatval($it['unit_price']??0),2),'sort_order'=>$i,'create_time'=>time(),'update_time'=>time()]); } }
}
