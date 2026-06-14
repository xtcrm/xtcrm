<?php
namespace mpp\crm\model;

use cores\BaseModel;
use think\facade\Db;

class Lead extends BaseModel
{
    protected $name = 'crm_lead';
    protected $autoWriteTimestamp = true;
    protected $append = ['owner_user_name', 'status_text', 'priority_text'];
    
    public function getList($where = [], $param = [])
    {
        $allowed = ['id','lead_name','status','priority','source','owner_user_id','is_delete','company_name','contact_person','contact_phone','contact_position','industry','expected_amount','requirement','remark','next_follow_date','create_time','customer_id'];
        $fw = [];
        foreach ($where as $k => $v) { if (in_array($k, $allowed)) $fw[$k] = $v; }
        if (!empty($where['lead_name'])) $fw[] = ['lead_name','like','%'.$where['lead_name'].'%'];
        return $this->where($fw)->where('is_delete',0)->order(['id'=>'desc'])->paginate($param);
    }

    public function getOwnerUserNameAttr($v, $data) {
        if (!empty($data['owner_user_id'])) {
            $u = Db::name('store_user')->where('store_user_id', $data['owner_user_id'])->field('real_name,user_name')->find();
            return $u ? ($u['real_name'] ?: $u['user_name']) : '';
        }
        return '';
    }
    public function getStatusTextAttr($v, $data) { return ['','跟进中','已转化','已放弃'][$data['status']??1] ?? ''; }
    public function getPriorityTextAttr($v, $data) { return ['','高','中','低'][$data['priority']??2] ?? ''; }

    public function detail($id) { return $this->where('id', $id)->find(); }

    public function add($data)
    {
        $data['store_id'] = static::$storeId;
        if (empty($data['lead_name'])) { $this->error='线索名称不能为空'; return false; }
        $data['status'] = 1;
        return $this->save($data);
    }

    public function edit($data)
    {
        if (empty($data['lead_name'])) { $this->error='线索名称不能为空'; return false; }
        $data['next_follow_date'] = !empty($data['next_follow_date']) ? strtotime($data['next_follow_date']) : null;
        return $this->save($data);
    }

    public function setDelete($id) { $this->is_delete=1; return $this->save(); }

    /** 线索转客户 */
    public function convertToCustomer($id)
    {
        $lead = $this->where('id', $id)->find();
        if (!$lead) { $this->error='线索不存在'; return false; }
        if ($lead['status'] == 3) { $this->error='该线索已转客户'; return false; }

        $customerName = $lead['company_name'] ?: ($lead['contact_person'] ?: $lead['lead_name']);
        $customerCode = 'C-' . date('Ymd') . '-' . str_pad($id, 3, '0', STR_PAD_LEFT);

        Db::startTrans();
        try {
            $customer = new Customer;
            $customer->save([
                'store_id' => static::$storeId,
                'customer_name' => $customerName,
                'customer_code' => $customerCode,
                'short_name' => $lead['lead_name'],
                'industry' => $lead['industry'],
                'source' => $lead['source'],
                'owner_user_id' => $lead['owner_user_id'],
                'creator_user_id' => $lead['owner_user_id'],
                'funnel_stage' => 1,
                'remark' => '来自线索：' . $lead['lead_name'],
                'status' => 1,
                'create_time' => time(),
                'update_time' => time(),
            ]);

            // 创建联系人
            if (!empty($lead['contact_person'])) {
                $contact = new Contact;
                $contact->save([
                    'store_id' => static::$storeId,
                    'customer_id' => $customer->id,
                    'contact_name' => $lead['contact_person'],
                    'mobile' => $lead['contact_phone'],
                    'position' => $lead['contact_position'],
                    'is_primary' => 1,
                    'create_time' => time(),
                    'update_time' => time(),
                ]);
            }

            // 更新线索状态
            $lead->status = 3;
            $lead->customer_id = $customer->id;
            $lead->update_time = time();
            $lead->save();

            Db::commit();
            return ['customer_id' => $customer->id, 'customer_name' => $customerName];
        } catch (\Exception $e) {
            Db::rollback();
            $this->error = $e->getMessage();
            return false;
        }
    }
}
