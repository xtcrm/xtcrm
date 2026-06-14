<?php
namespace mpp\crm\model;

use cores\BaseModel;
class Contract extends BaseModel
{
    protected $name = 'crm_contract';
    protected $autoWriteTimestamp = true;
    protected $append = ['customer_name', 'status_text'];
    
    public function getList($where = [], $param = [])
    {
        $allowed = ['id','contract_no','customer_id','status','owner_user_id','is_delete'];
        $fw = [];
        foreach ($where as $k => $v) { if (in_array($k, $allowed)) $fw[$k] = $v; }
        if (!empty($where['customer_name'])) {
            $ids = Customer::where('customer_name','like','%'.$where['customer_name'].'%')->where('is_delete',0)->column('id');
            $fw[] = !empty($ids) ? ['customer_id','in',$ids] : ['customer_id','=',-1];
        }
        return $this->where($fw)->where('is_delete',0)->order(['id'=>'desc'])->paginate($param);
    }

    public function detail($id) { return $this->where('id', $id)->find(); }

    public function add($data)
    {
        $data['store_id'] = static::$storeId;
        if (empty($data['customer_id'])) { $this->error='请选择客户'; return false; }
        if (empty($data['contract_name'])) { $this->error='合同名称不能为空'; return false; }
        $data['contract_no'] = $this->generateNo();
        $data['sign_date'] = !empty($data['sign_date']) ? strtotime($data['sign_date']) : null;
        $data['start_date'] = !empty($data['start_date']) ? strtotime($data['start_date']) : null;
        $data['end_date'] = !empty($data['end_date']) ? strtotime($data['end_date']) : null;
        $data['status'] = 1;
        $data['currency'] = $data['currency'] ?? 'CNY';
        return $this->save($data);
    }

    public function edit($data)
    {
        if (empty($data['contract_name'])) { $this->error='合同名称不能为空'; return false; }
        $data['sign_date'] = !empty($data['sign_date']) ? strtotime($data['sign_date']) : $this->sign_date;
        $data['start_date'] = !empty($data['start_date']) ? strtotime($data['start_date']) : $this->start_date;
        $data['end_date'] = !empty($data['end_date']) ? strtotime($data['end_date']) : $this->end_date;
        return $this->save($data);
    }

    public function setDelete($id) { $this->is_delete=1; return $this->save(); }

    protected function generateNo() {
        $p='CT-'.date('Ymd').'-';
        $last=$this->where('contract_no','like',$p.'%')->order(['id'=>'desc'])->find();
        $seq=$last?intval(substr($last['contract_no'],-3))+1:1;
        return $p.str_pad($seq,3,'0',STR_PAD_LEFT);
    }
}
