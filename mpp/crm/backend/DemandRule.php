<?php
namespace mpp\crm\backend;

use app\platform\backend\BaseController;
use mpp\crm\model\DemandRule as DemandRuleModel;
use mpp\crm\service\DemandService;

class DemandRule extends BaseController
{
    protected $methodRules = [
        'lists'      => 'GET',
        'add'        => 'POST',
        'edit'       => 'POST',
        'aiGenerate' => 'POST',
        'setStatus'  => 'POST',
        'delete'     => 'POST',
    ];

    public function lists()
    {
        if (!$this->checkAction('/crm/demandRule/lists', '查询需求规则')) return;
        $model = new DemandRuleModel();
        $rules = $model->where('store_id', $this->storeId)
            ->order(['priority' => 'asc', 'id' => 'asc'])
            ->select()
            ->toArray();
        return $this->renderSuccess(['list' => $rules]);
    }

    /** AI 根据自然语言描述生成规则 */
    public function aiGenerate()
    {
        if (!$this->checkAction('/crm/demandRule/add', '新增需求规则')) return;
        $description = $this->request->param('description', '');
        if (empty($description)) return $this->renderError('请输入规则描述');

        // 获取当前租户的自定义字段
        $customFields = \think\facade\Db::name('crm_custom_field')
            ->where('store_id', $this->storeId)
            ->where('entity', 'customer')
            ->where('status', 1)
            ->column('field_key');

        $nativeFields = 'customer_name, short_name, industry, industry_id, level_id, source_id, group_id, tax_number, registered_capital, paid_in_capital, legal_representative, established_date, registration_status, business_registration_no, business_scope, introduction, credit_limit, taxpayer_qualification, registered_address, address, last_followup_time, funnel_stage, ai_tags';
        $extraHint = !empty($customFields) ? "\n自定义字段（引用方式 extra.字段名）：" . implode(', ', $customFields) : '';

        $fieldsHint = "原生字段：" . $nativeFields . $extraHint;

        $result = \app\common\service\AiService::generateDemandRule($description, $fieldsHint);
        if (!$result['success']) {
            return $this->renderError('AI调用失败：' . ($result['error'] ?? '未知错误'));
        }
        $aiData = $result['data'];

        try {
            // 确保必填字段
            $aiData['rule_key'] = $aiData['rule_key'] ?? ('custom_' . time());
            $aiData['rule_name'] = $aiData['rule_name'] ?? 'AI生成规则';
            $aiData['logic'] = $aiData['logic'] ?? 'all';
            $aiData['priority'] = intval($aiData['priority'] ?? 2);
            $aiData['conditions'] = json_encode($aiData['conditions'], JSON_UNESCAPED_UNICODE);

            // 自动创建 AI 建议的新自定义字段（过滤掉已有原生字段和已有扩展字段）
            $newFields = $aiData['new_fields'] ?? [];
            $existingNative = ['customer_name','short_name','industry','industry_id','level_id','source_id','group_id',
                'tax_number','registered_capital','paid_in_capital','legal_representative','established_date',
                'registration_status','business_registration_no','organization_code','business_scope','introduction',
                'credit_limit','taxpayer_qualification','insured_count','registered_address','address',
                'last_followup_time','funnel_stage','telephone','email','website','remark','ai_tags'];
            $existingCustom = \think\facade\Db::name('crm_custom_field')
                ->where('store_id', $this->storeId)->where('entity', 'customer')
                ->column('field_key');
            $skipFields = array_merge($existingNative, $existingCustom);

            $createdFields = [];
            foreach ($newFields as $nf) {
                $key = $nf['field_key'] ?? '';
                if (empty($key) || in_array($key, $skipFields)) continue; // 跳过已有字段
                $key = $nf['field_key'] ?? '';
                if (empty($key)) continue;
                $exists = \think\facade\Db::name('crm_custom_field')
                    ->where('store_id', $this->storeId)
                    ->where('entity', 'customer')
                    ->where('field_key', $key)
                    ->find();
                if (!$exists) {
                    \think\facade\Db::name('crm_custom_field')->insert([
                        'store_id' => $this->storeId,
                        'entity' => 'customer',
                        'field_key' => $key,
                        'field_label' => $nf['field_label'] ?? $key,
                        'field_type' => $nf['field_type'] ?? 'text',
                        'status' => 1,
                        'sort' => 100,
                        'create_time' => time(),
                    ]);
                    $createdFields[] = $key;
                }
            }

            // 兜底：检查 conditions 中的 extra.xxx 引用是否存在，不存在的自动建
            $conds = json_decode($aiData['conditions'], true) ?: [];
            foreach ($conds as $cond) {
                $field = $cond['field'] ?? '';
                if (strpos($field, 'extra.') === 0) {
                    $key = substr($field, 6);
                    // 跳过中文 key（已在 prompt 中要求 AI 不要生成中文 key）
                    if (preg_match('/[\x{4e00}-\x{9fff}]/u', $key)) continue;
                    $exists = \think\facade\Db::name('crm_custom_field')
                        ->where('store_id', $this->storeId)
                        ->where('entity', 'customer')
                        ->where('field_key', $key)
                        ->find();
                    if (!$exists && !in_array($key, $createdFields)) {
                        \think\facade\Db::name('crm_custom_field')->insert([
                            'store_id' => $this->storeId,
                            'entity' => 'customer',
                            'field_key' => $key,
                            'field_label' => $key,
                            'field_type' => 'text',
                            'status' => 1,
                            'sort' => 100,
                            'create_time' => time(),
                        ]);
                        $createdFields[] = $key;
                    }
                }
            }
            $aiData['created_fields'] = $createdFields;

            return $this->renderSuccess($aiData, 'AI已生成规则');
        } catch (\Throwable $e) {
            return $this->renderError('AI调用失败：' . $e->getMessage());
        }
    }

    /** 用户确认保存规则 */
    public function add()
    {
        if (!$this->checkAction('/crm/demandRule/add', '新增需求规则')) return;
        $data = $this->postData();
        if (empty($data['rule_key']) || empty($data['rule_name']) || empty($data['conditions'])) {
            return $this->renderError('缺少必填字段');
        }
        $model = new DemandRuleModel();
        $row = $model->where('store_id', $this->storeId)->where('rule_key', $data['rule_key'])->find();
        if ($row) return $this->renderError('该规则标识已存在');

        // ThinkPHP 默认 htmlspecialchars 过滤会把 " 转成 &quot;，需还原
        $conditions = htmlspecialchars_decode($data['conditions'] ?? '', ENT_QUOTES);

        $model->save([
            'store_id' => $this->storeId,
            'rule_key' => $data['rule_key'],
            'rule_name' => $data['rule_name'],
            'conditions' => $conditions,
            'logic' => $data['logic'] ?? 'all',
            'priority' => intval($data['priority'] ?? 2),
            'status' => 1,
            'is_builtin' => 0,
            'create_time' => time(),
        ]);

        // 新建规则后立即扫描所有客户
        try { (new DemandService())->dailyScan($this->storeId); } catch (\Throwable $e) {}

        return $this->renderSuccess(['id' => $model->id], '规则已创建并扫描完成');
    }

    public function edit()
    {
        if (!$this->checkAction('/crm/demandRule/edit', '编辑需求规则')) return;
        $data = $this->postData();
        $id = $data['id'] ?? 0;
        if (empty($id)) return $this->renderError('缺少规则ID');

        $model = new DemandRuleModel();
        $row = $model->where('id', $id)->find();
        if (!$row) return $this->renderError('规则不存在');

        $conditions = htmlspecialchars_decode($data['conditions'] ?? '', ENT_QUOTES);
        $row->save([
            'rule_name'  => $data['rule_name'] ?? $row['rule_name'],
            'conditions' => $conditions ?: $row['conditions'],
            'logic'      => $data['logic'] ?? $row['logic'],
            'priority'   => intval($data['priority'] ?? $row['priority']),
            'update_time' => time(),
        ]);

        // 重新扫描
        try { (new DemandService())->dailyScan($this->storeId); } catch (\Throwable $e) {}
        return $this->renderSuccess([], '规则已更新并重新扫描');
    }

    public function delete()
    {
        if (!$this->checkAction('/crm/demandRule/delete', '删除需求规则')) return;
        $id = $this->request->param('id', 0);
        $model = new DemandRuleModel();
        $row = $model->where('id', $id)->find();
        if (!$row) return $this->renderError('规则不存在');
        $row->delete();
        return $this->renderSuccess([], '已删除');
    }

    public function setStatus()
    {
        if (!$this->checkAction('/crm/demandRule/setStatus', '启用停用规则')) return;
        $id = $this->request->param('id', 0);
        $status = $this->request->param('status', 1);
        $model = new DemandRuleModel();
        $row = $model->where('id', $id)->find();
        if (!$row) return $this->renderError('规则不存在');
        $row->save(['status' => $status, 'update_time' => time()]);
        return $this->renderSuccess([], '操作成功');
    }
}
