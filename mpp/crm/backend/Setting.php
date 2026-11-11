<?php
namespace mpp\crm\backend;

use app\platform\backend\BaseController;
use app\platform\ai\AiGateway;
use app\platform\ai\AiSetting;
use think\facade\Db;

class Setting extends BaseController
{
    protected $methodRules = [
        'detail'   => 'GET',
        'save'     => 'POST',
        'testAi'   => 'POST',
        'aiModels' => 'POST',
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
            // 公海自动回收默认关闭：只有显式开启（pool_enabled=1）才会到点掉公海
            'pool_enabled'             => intval($s['pool_enabled'] ?? 0),
            'pool_days'                => intval($s['pool_days'] ?? 180),
            'approval_trigger_amount'  => floatval($s['approval_trigger_amount'] ?? 50000),
            'approval_levels'          => intval($s['approval_levels'] ?? 2),
            'openclaw_enabled'         => $s['openclaw_enabled'] ?? '0',
            'openclaw_token'           => $s['openclaw_token'] ?? '',
            'openclaw_base_url'        => $s['openclaw_base_url'] ?? 'http://127.0.0.1:18789',
            'openclaw_agent_id'        => $s['openclaw_agent_id'] ?? 'main',
            // AI 接入方式：platform（平台网关 api.xtocn.com）/ custom（自定义 OpenAI 兼容接口）
            'ai_provider'              => $this->resolveAiProvider($s),
            // 自定义地址：平台接入时前端不展示，但保留下来，切回自定义不用重填
            'ai_api_url'               => $this->resolveAiCustomUrl($s),
            // Key 不再回传明文，只给掩码
            'ai_api_key'               => '',
            'ai_has_key'               => !empty($s['ai_api_key']),
            'ai_api_key_mask'          => AiSetting::mask((string)($s['ai_api_key'] ?? '')),
            'ai_model'                 => $s['ai_model'] ?? 'deepseek-chat',
            'ai_temperature'           => floatval($s['ai_temperature'] ?? 0.7),
            'ai_max_tokens'            => intval($s['ai_max_tokens'] ?? 4096),
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
            'pool_enabled'             => ['default' => 0,          'type' => 'number'],
            'pool_days'                => ['default' => 180,        'type' => 'number'],
            'approval_trigger_amount'  => ['default' => 50000,      'type' => 'number'],
            'approval_levels'          => ['default' => 2,          'type' => 'number'],
            'openclaw_enabled'         => ['default' => '0',        'type' => 'number'],
            'openclaw_token'           => ['default' => '',         'type' => 'string'],
            'openclaw_base_url'        => ['default' => 'http://127.0.0.1:18789', 'type' => 'string'],
            'openclaw_agent_id'        => ['default' => 'main',     'type' => 'string'],
            // AI 配置单独处理：平台/自定义两种接入有联动，见 saveAiConfig()
            // （Key 留空 = 保持原值；平台接入的地址由后端决定，不让前端传）
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
            $this->saveAiConfig($data, $now);
            Db::commit();
            \app\platform\ai\AiConfig::clearCache();
            return $this->renderSuccess([], '保存成功');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->renderError('保存失败：' . $e->getMessage());
        }
    }

    /**
     * 测试AI连接
     *
     * 支持带上未保存的表单值（和商城「AI 配置」一致），方便填完先测再存。
     */
    public function testAi()
    {
        $cfg = $this->resolveAiConfig($this->postData());
        if ($cfg['api_key'] === '')  return $this->renderError('还没有填写 API Key');
        if ($cfg['api_url'] === '')  return $this->renderError('还没有填写 API 地址');

        $result = AiGateway::chatWithConfig($cfg, [
            ['role' => 'user', 'content' => '只回复两个字：正常'],
        ], ['max_tokens' => 8, 'temperature' => 0, 'timeout' => 20]);

        return $result->success
            ? $this->renderSuccess(['reply' => (string)$result->content], '连接成功')
            : $this->renderError('连接失败：' . $result->error);
    }

    /** 读取当前 Key 可用的模型列表（支持尚未保存的表单值） */
    public function aiModels()
    {
        $cfg = $this->resolveAiConfig($this->postData());
        if ($cfg['api_key'] === '') return $this->renderError('请先填写 API Key');

        $url = $cfg['provider'] === AiSetting::PROVIDER_PLATFORM
            ? AiSetting::platformBase() . '/v1/models'
            : preg_replace('#/chat/completions$#', '/models', $cfg['api_url']);

        $result = AiGateway::listModels((string)$url, $cfg['api_key']);

        return $result['success']
            ? $this->renderSuccess(['models' => $result['data']])
            : $this->renderError($result['error']);
    }

    // ── AI 配置 ──

    /**
     * 保存 AI 配置
     *
     * 两套接入方式共用 crm_setting 命名空间（保持历史数据可用）：
     *   platform — 地址固定为平台网关，库里也存完整地址，历史直读 ai_api_url 的功能不用改
     *   custom   — 地址由租户填写，额外存一份 ai_custom_api_url，切到平台后不丢
     * Key 留空 = 保持原值（前端不再回传明文）。
     */
    private function saveAiConfig(array $data, int $now): void
    {
        // 表单里完全没带 AI 字段（例如只改公海规则的 API 调用）就不动 AI 配置
        $hasAiInput = false;
        foreach (['ai_provider', 'ai_api_key', 'ai_api_url', 'ai_model', 'ai_temperature', 'ai_max_tokens'] as $k) {
            if (array_key_exists($k, $data)) { $hasAiInput = true; break; }
        }
        if (!$hasAiInput) return;

        $current  = $this->readAiRows();
        $provider = (string)($data['ai_provider'] ?? '');
        if (!in_array($provider, [AiSetting::PROVIDER_PLATFORM, AiSetting::PROVIDER_CUSTOM], true)) {
            // 老前端不传 ai_provider：沿用已保存的接入方式，没有则按历史语义当自定义
            $saved = (string)($current['ai_provider'] ?? '');
            $provider = in_array($saved, [AiSetting::PROVIDER_PLATFORM, AiSetting::PROVIDER_CUSTOM], true)
                ? $saved : AiSetting::PROVIDER_CUSTOM;
        }

        if ($provider === AiSetting::PROVIDER_PLATFORM) {
            $apiUrl = AiSetting::platformBase() . '/v1/chat/completions';
        } else {
            $custom = trim((string)($data['ai_api_url'] ?? ''));
            if ($custom === '') $custom = (string)($current['ai_custom_api_url'] ?? $current['ai_api_url'] ?? '');
            if ($custom === '') $custom = 'https://api.deepseek.com/v1/chat/completions';
            self::upsertConfig('ai_custom_api_url', $custom, $now);
            $apiUrl = self::chatUrl($custom);
        }

        self::upsertConfig('ai_provider', $provider, $now);
        self::upsertConfig('ai_api_url', $apiUrl, $now);

        $key = trim((string)($data['ai_api_key'] ?? ''));
        if ($key !== '') self::upsertConfig('ai_api_key', $key, $now);

        $model = trim((string)($data['ai_model'] ?? ''));
        if ($model !== '') self::upsertConfig('ai_model', $model, $now);

        if (isset($data['ai_temperature']) && $data['ai_temperature'] !== '') {
            self::upsertConfig('ai_temperature', (string)floatval($data['ai_temperature']), $now);
        }
        if (isset($data['ai_max_tokens']) && $data['ai_max_tokens'] !== '') {
            self::upsertConfig('ai_max_tokens', (string)intval($data['ai_max_tokens']), $now);
        }
    }

    /** 接入方式：显式配置 > 历史推断（填过 Key 的一律按自定义处理） */
    private function resolveAiProvider(array $s): string
    {
        $provider = (string)($s['ai_provider'] ?? '');
        if (in_array($provider, [AiSetting::PROVIDER_PLATFORM, AiSetting::PROVIDER_CUSTOM], true)) {
            return $provider;
        }
        return !empty($s['ai_api_key']) ? AiSetting::PROVIDER_CUSTOM : AiSetting::PROVIDER_PLATFORM;
    }

    /** 前端要回显的自定义地址（平台接入时也保留，方便切回来） */
    private function resolveAiCustomUrl(array $s): string
    {
        $custom = trim((string)($s['ai_custom_api_url'] ?? ''));
        if ($custom !== '') return $custom;

        // 历史租户：自定义地址就存在 ai_api_url 里
        if ($this->resolveAiProvider($s) === AiSetting::PROVIDER_CUSTOM) {
            $url = trim((string)($s['ai_api_url'] ?? ''));
            return $url !== '' ? $url : 'https://api.deepseek.com/v1/chat/completions';
        }
        return '';
    }

    /**
     * 合并「已保存配置 + 本次表单值」，得到可直接请求的配置
     * 保存前测试连接/取模型都走这里。
     */
    private function resolveAiConfig(array $in): array
    {
        $current  = $this->readAiRows();
        $provider = (string)($in['ai_provider'] ?? '');
        if (!in_array($provider, [AiSetting::PROVIDER_PLATFORM, AiSetting::PROVIDER_CUSTOM], true)) {
            $provider = $this->resolveAiProvider($current);
        }

        if ($provider === AiSetting::PROVIDER_PLATFORM) {
            $apiUrl = AiSetting::platformBase() . '/v1/chat/completions';
        } else {
            $apiUrl = self::chatUrl((string)($in['ai_api_url'] ?? ''));
            if ($apiUrl === '') {
                $apiUrl = self::chatUrl((string)($current['ai_custom_api_url'] ?? $current['ai_api_url'] ?? ''));
            }
        }

        $key = trim((string)($in['ai_api_key'] ?? ''));
        if ($key === '') $key = (string)($current['ai_api_key'] ?? '');

        $model = trim((string)($in['ai_model'] ?? ''));
        if ($model === '') $model = (string)($current['ai_model'] ?? '');

        $temperature = (isset($in['ai_temperature']) && $in['ai_temperature'] !== '')
            ? (float)$in['ai_temperature'] : (float)($current['ai_temperature'] ?? 0.7);
        $maxTokens = (isset($in['ai_max_tokens']) && $in['ai_max_tokens'] !== '')
            ? (int)$in['ai_max_tokens'] : (int)($current['ai_max_tokens'] ?? 4096);

        return [
            'provider'    => $provider,
            'api_url'     => $apiUrl,
            'api_key'     => $key,
            'model'       => $model,
            'temperature' => $temperature,
            'max_tokens'  => $maxTokens,
        ];
    }

    /** 当前租户在 crm_setting 里的 AI 相关行 */
    private function readAiRows(): array
    {
        $rows = Db::name('config')
            ->where('config_type', 'crm_setting')
            ->where('store_id', app()->request->storeId())
            ->whereIn('config_name', [
                'ai_provider', 'ai_api_url', 'ai_custom_api_url', 'ai_api_key',
                'ai_model', 'ai_temperature', 'ai_max_tokens',
            ])
            ->select()->toArray();

        $out = [];
        foreach ($rows as $r) $out[$r['config_name']] = $r['config_value'];
        return $out;
    }

    /** 与 AiConfig 的补全规则保持一致 */
    private static function chatUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') return '';
        return strpos($url, '/chat/completions') === false
            ? rtrim($url, '/') . '/chat/completions'
            : $url;
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
