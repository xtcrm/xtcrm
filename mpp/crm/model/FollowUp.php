<?php
namespace mpp\crm\model;

use cores\BaseModel;

class FollowUp extends BaseModel
{
    protected $name = 'crm_followup';
    protected $autoWriteTimestamp = true;
    protected $append = ['owner_user_name', 'contact_name'];
    
    public function getList($customerId, $param = [])
    {
        return $this->where('customer_id', $customerId)
            ->where('is_delete', 0)
            ->order(['follow_date' => 'desc', 'id' => 'desc'])
            ->paginate($param);
    }

    public function add($data)
    {
        $data['store_id'] = static::$storeId;
        if (empty($data['customer_id'])) {
            $this->error = '所属客户不能为空';
            return false;
        }
        if (empty($data['follow_content'])) {
            $this->error = '跟进内容不能为空';
            return false;
        }
        $data['follow_date'] = !empty($data['follow_date']) ? strtotime($data['follow_date']) : time();
        $data['next_follow_date'] = !empty($data['next_follow_date']) ? strtotime($data['next_follow_date']) : null;

        $result = $this->save($data);
        if ($result) {
            // 更新客户最后跟进时间和漏斗阶段
            $customer = Customer::where('id', $data['customer_id'])->find();
            if ($customer) {
                $customer->last_followup_time = time();
                if ($customer->funnel_stage == 1 && !empty($data['result']) && $data['result'] == '有效') {
                    $customer->funnel_stage = 2;
                }
                $customer->save();
            }
            // AI跟进建议（静默失败不影响主流程）
            try {
                $aiCfg = \think\facade\Db::name('config')
                    ->where('config_type', 'crm_setting')
                    ->where('config_name', 'ai_api_key')
                    ->value('config_value');
                if (!empty($aiCfg)) {
                    $recent = self::where('customer_id', $data['customer_id'])
                        ->where('is_delete', 0)
                        ->order(['follow_date' => 'desc'])->limit(10)
                        ->field(['follow_type', 'follow_content', 'result', 'follow_date'])
                        ->select()->toArray();
                    $fuText = '';
                    foreach ($recent as $fu) {
                        $fuText .= date('Y-m-d', $fu['follow_date'])
                            . ' [' . $fu['follow_type'] . '/' . $fu['result'] . '] '
                            . mb_substr($fu['follow_content'], 0, 150) . "\n";
                    }
                    $cust = $customer ?: Customer::where('id', $data['customer_id'])->find();
                    $safeName = '客户';
                    if (!empty($cust['customer_name'])) {
                        $safeName = mb_substr($cust['customer_name'], 0, 1) . '客户';
                    }
                    $prompt = "客户：{$safeName}\n行业：{$cust['industry']}\n等级：{$cust['level_name']}\n最近跟进：\n{$fuText}\n请给出下一步跟进建议。";
                    $aiResult = \app\common\service\AiService::quickAsk($prompt);
                    if ($aiResult['success'] && !empty($aiResult['data'])) {
                        self::where('id', $this->id)->update(['ai_suggestion' => $aiResult['data'], 'update_time' => time()]);
                    }
                }
            } catch (\Exception $e) {
                // 静默失败
            }
        }
        return $result;
    }

    /** owner_user_name 访问器 */
    public function getOwnerUserNameAttr($value, $data)
    {
        if (!empty($data['owner_user_id'])) {
            $u = \think\facade\Db::name('store_user')
                ->where('store_user_id', $data['owner_user_id'])
                ->field('real_name, user_name')->find();
            return $u ? ($u['real_name'] ?: $u['user_name']) : '';
        }
        return '';
    }

    /** contact_name 访问器 */
    public function getContactNameAttr($value, $data)
    {
        if (!empty($data['contact_id'])) {
            $c = \think\facade\Db::name('crm_contact')
                ->where('id', $data['contact_id'])
                ->value('contact_name');
            return $c ?: '';
        }
        return '';
    }

    public function edit($data)
    {
        if (empty($data['follow_content'])) {
            $this->error = '跟进内容不能为空';
            return false;
        }
        $data['follow_date'] = !empty($data['follow_date']) ? strtotime($data['follow_date']) : $this->follow_date;
        $data['next_follow_date'] = !empty($data['next_follow_date']) ? strtotime($data['next_follow_date']) : null;
        return $this->save($data);
    }
}
