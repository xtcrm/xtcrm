<?php
namespace mpp\crm\service;

use app\platform\BaseService;
use mpp\crm\model\DemandRule as DemandRuleModel;

class DemandRuleService extends BaseService
{
    private function evalCondition($fieldValue, $op, $compareValue): bool
    {
        switch ($op) {
            case 'not_empty':
                return !empty($fieldValue) && $fieldValue !== '';
            case 'empty':
                return empty($fieldValue) || $fieldValue === '';
            case 'eq':
                return (string)$fieldValue === (string)$compareValue;
            case 'neq':
                return (string)$fieldValue !== (string)$compareValue;
            case 'contains':
                return mb_strpos((string)$fieldValue, (string)$compareValue) !== false;
            case 'gt':
                return floatval($fieldValue) > floatval($compareValue);
            case 'days_since_gt':
                if (empty($fieldValue)) return false;
                $days = (time() - intval($fieldValue)) / 86400;
                return $days > intval($compareValue);
            case 'split_count_gt':
                if (empty($fieldValue)) return false;
                // 按常见分隔符拆分，统计有效条目数
                $normalized = str_replace(['；', ';', ',', '，', '/', '、'], '、', (string)$fieldValue);
                $parts = explode('、', $normalized);
                $count = count(array_filter($parts, function($p) { return !empty(trim($p)); }));
                return $count > intval($compareValue);
            default:
                return false;
        }
    }

    // 字段中文名映射
    private static $fieldLabels = [
        'legal_representative'     => '法定代表人',
        'registered_capital'       => '注册资本',
        'paid_in_capital'          => '实缴资本',
        'business_scope'           => '经营范围',
        'introduction'             => '公司简介',
        'credit_limit'             => '信用额度',
        'taxpayer_qualification'   => '纳税人资质',
        'registered_address'       => '注册地址',
        'address'                  => '实际地址',
        'last_followup_time'       => '最近跟进',
        'tax_number'               => '统一信用代码',
        'business_registration_no' => '工商注册号',
        'customer_name'            => '客户名称',
        'telephone'                => '公司电话',
        'email'                    => '公司邮箱',
        'website'                  => '网址',
        'established_date'         => '成立日期',
        'funnel_stage'             => '跟进阶段',
        'ai_tags'                  => 'AI标签',
    ];

    // 操作符中文描述
    private static $opLabels = [
        'not_empty'       => '已填写',
        'empty'           => '未填写',
        'eq'              => '等于',
        'neq'             => '不等于',
        'contains'        => '包含',
        'gt'              => '大于',
        'split_count_gt'  => '条目数超过',
        'days_since_gt'   => '超过',
    ];

    private function describeCondition(string $field, string $op, $value): string
    {
        // 解析字段标签
        $label = $field;
        if (strpos($field, 'extra.') === 0) {
            $key = substr($field, 6);
            // 从 custom_field 表查中文名（带缓存）
            $cf = \think\facade\Db::name('crm_custom_field')
                ->where('field_key', $key)->where('status', 1)
                ->value('field_label');
            $label = $cf ?: $key;
        } else {
            $label = self::$fieldLabels[$field] ?? $field;
        }

        // 翻译操作符
        $opDesc = self::$opLabels[$op] ?? $op;

        // 拼描述
        if (in_array($op, ['not_empty', 'empty'])) {
            return $label . $opDesc;
        }
        if ($op === 'days_since_gt') {
            return $label . $opDesc . ' ' . intval($value) . ' 天';
        }
        if ($op === 'split_count_gt') {
            return $label . $opDesc . ' ' . intval($value) . ' 条';
        }
        return $label . $opDesc . ' ' . ($value ?? '');
    }

    private function getFieldValue($customer, $field): string
    {
        if (strpos($field, 'extra.') === 0) {
            $key = substr($field, 6);
            $raw = $customer['extra'] ?? '{}';
            // ThinkPHP 默认 htmlspecialchars 会把 \" 转成 &quot;，需还原
            if (is_string($raw) && strpos($raw, '&quot;') !== false) {
                $raw = htmlspecialchars_decode($raw, ENT_QUOTES);
            }
            $extra = json_decode($raw, true) ?: [];
            return $extra[$key] ?? '';
        }
        return $customer[$field] ?? '';
    }

    public function evaluate(array $customer): array
    {
        $model = new DemandRuleModel();
        $rules = $model->getEnabledRules((int)($customer['store_id'] ?? 0));
        $hits = [];
        foreach ($rules as $rule) {
            $conditions = json_decode($rule['conditions'], true) ?: [];
            if (empty($conditions)) continue;
            $logic = $rule['logic'] ?? 'all';
            $allPass = true;
            $anyPass = false;
            $reasons = [];
            foreach ($conditions as $cond) {
                $val = $this->getFieldValue($customer, $cond['field'] ?? '');
                $pass = $this->evalCondition($val, $cond['op'] ?? 'not_empty', $cond['value'] ?? null);
                if ($pass) {
                    $anyPass = true;
                    $reasons[] = $this->describeCondition($cond['field'] ?? '', $cond['op'] ?? '', $cond['value'] ?? null);
                } else {
                    $allPass = false;
                }
            }
            $matched = ($logic === 'all') ? $allPass : $anyPass;
            if ($matched) {
                $hits[] = [
                    'rule_key' => $rule['rule_key'],
                    'rule_name' => $rule['rule_name'],
                    'priority' => $rule['priority'],
                    'reason' => implode(' + ', $reasons),
                ];
            }
        }
        return $hits;
    }
}
