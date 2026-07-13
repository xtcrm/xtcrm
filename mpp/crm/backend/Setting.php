<?php
namespace mpp\crm\backend;

use app\platform\backend\BaseController;
use think\facade\Db;

class Setting extends BaseController
{
    protected $methodRules = [
        'detail' => 'GET',
        'save'   => 'POST',
        'testAi' => 'POST',
    ];

    /** 读取 CRM 设置（租户优先，系统兜底） */
    public function detail()
    {
        $configs = Db::name('config')
            ->where('config_type', 'crm_setting')
            ->where(function ($q) { $q->where('store_id', $this->storeId)->whereOr('store_id', 0); })
            ->select()->toArray();
        $s = [];
        foreach ($configs as $c) {
            if (!isset($s[$c['config_name']]) || $c['store_id'] == $this->storeId) $s[$c['config_name']] = $c['config_value'];
        }
        return $this->renderSuccess([
            'pool_days'                => intval($s['pool_days'] ?? 180),
            'approval_trigger_amount'  => floatval($s['approval_trigger_amount'] ?? 50000),
            'approval_levels'          => intval($s['approval_levels'] ?? 2),
            'openclaw_enabled'         => $s['openclaw_enabled'] ?? '0',
            'openclaw_token'           => $s['openclaw_token'] ?? '',
            'openclaw_base_url'        => $s['openclaw_base_url'] ?? 'http://127.0.0.1:18789',
            'openclaw_agent_id'        => $s['openclaw_agent_id'] ?? 'main',
            'ai_api_url'               => $s['ai_api_url'] ?? 'https://api.deepseek.com/v1/chat/completions',
            'ai_api_key'               => $s['ai_api_key'] ?? '',
            'ai_model'                 => $s['ai_model'] ?? 'deepseek-chat',
            'ai_temperature'           => floatval($s['ai_temperature'] ?? 0.7),
            'ai_max_tokens'            => intval($s['ai_max_tokens'] ?? 1024),
            // AI 洞察参数
            'insight_repurchase_threshold' => floatval($s['insight_repurchase_threshold'] ?? 0.8),
            'insight_quotation_overdue_days' => intval($s['insight_quotation_overdue_days'] ?? 7),
            'insight_pool_warning_days' => intval($s['insight_pool_warning_days'] ?? 7),
            'insight_churn_multiplier' => floatval($s['insight_churn_multiplier'] ?? 1.5),
            'insight_churn_no_followup_days' => intval($s['insight_churn_no_followup_days'] ?? 30),
            'insight_dormant_days' => intval($s['insight_dormant_days'] ?? 90),
            'insight_max_per_user' => intval($s['insight_max_per_user'] ?? 10),
        ]);
    }

    /** 保存 CRM 设置 */
    public function save()
    {
        $all  = $this->postData();
        $data = $all['form'] ?? $all;
        $fields = [
            'pool_days'                => ['default' => 180,        'type' => 'number'],
            'approval_trigger_amount'  => ['default' => 50000,      'type' => 'number'],
            'approval_levels'          => ['default' => 2,          'type' => 'number'],
            'openclaw_enabled'         => ['default' => '0',        'type' => 'number'],
            'openclaw_token'           => ['default' => '',         'type' => 'string'],
            'openclaw_base_url'        => ['default' => 'http://127.0.0.1:18789', 'type' => 'string'],
            'openclaw_agent_id'        => ['default' => 'main',     'type' => 'string'],
            'ai_api_url'               => ['default' => 'https://api.deepseek.com/v1/chat/completions', 'type' => 'string'],
            'ai_api_key'               => ['default' => '',         'type' => 'string'],
            'ai_model'                 => ['default' => 'deepseek-chat', 'type' => 'string'],
            'ai_temperature'           => ['default' => 0.7,        'type' => 'number'],
            'ai_max_tokens'            => ['default' => 1024,       'type' => 'number'],
            // AI 洞察参数
            'insight_repurchase_threshold'     => ['default' => 0.8,   'type' => 'number'],
            'insight_quotation_overdue_days'   => ['default' => 7,     'type' => 'number'],
            'insight_pool_warning_days'        => ['default' => 7,     'type' => 'number'],
            'insight_churn_multiplier'         => ['default' => 1.5,   'type' => 'number'],
            'insight_churn_no_followup_days'   => ['default' => 30,    'type' => 'number'],
            'insight_dormant_days'             => ['default' => 90,    'type' => 'number'],
            'insight_max_per_user'             => ['default' => 10,    'type' => 'number'],
        ];
        $now = time();
        Db::startTrans();
        try {
            foreach ($fields as $key => $rule) {
                $value = $data[$key] ?? $rule['default'];
                $value = $rule['type'] === 'number' ? strval(floatval($value)) : strval($value);
                self::upsertConfig($key, $value, $now);
            }
            Db::commit();
            \app\platform\ai\AiConfig::clearCache();
            return $this->renderSuccess([], '保存成功');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->renderError('保存失败：' . $e->getMessage());
        }
    }

    /** 测试AI连接 */
    public function testAi()
    {
        $result = \app\platform\ai\AiGateway::request('你是一个助手', '回复"OK"');
        if ($result->success) {
            return $this->renderSuccess(['reply' => $result->content], '连接成功');
        }
        return $this->renderError('连接失败：' . $result->error);
    }

    private static function upsertConfig($key, $value, $now)
    {
        $row = Db::name('config')
            ->where('store_id', app()->request->storeId())
            ->where('config_type', 'crm_setting')
            ->where('config_name', $key)
            ->find();
        if ($row) {
            Db::name('config')->where('id', $row['id'])->update(['config_value' => $value, 'update_time' => $now]);
        } else {
            Db::name('config')->insert([
                'store_id' => app()->request->storeId(), 'config_type' => 'crm_setting',
                'config_name' => $key, 'config_value' => $value,
                'sort_order' => 0, 'is_system' => 1, 'status' => 1,
                'create_time' => $now, 'update_time' => $now,
            ]);
        }
    }
}
