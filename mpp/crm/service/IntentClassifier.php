<?php

declare (strict_types=1);

namespace mpp\crm\service;

use think\facade\Db;

/**
 * 意图分类器 —— 用户消息 → 数字员工角色
 *
 * 规则优先级：
 *   1. @手动指定 (@销售 / @创作 / @教练) → 直接路由
 *   2. 关键词权重评分（从 crm_ai_role.triggers 读取）
 *   3. 默认 → sales（销售助手）
 */
class IntentClassifier
{
    /** @var array<string, array> 规则缓存 */
    private static $rules = [];

    /**
     * 分类用户消息
     *
     * @param string $message 用户原始消息
     * @return string 角色 key: sales / creator / coach
     */
    public static function classify(string $message): string
    {
        // 1. @手动指定优先
        if (preg_match('/@销售|@助手|@sales/i', $message)) {
            return 'sales';
        }
        if (preg_match('/@创作|@写手|@方案|@creator/i', $message)) {
            return 'creator';
        }
        if (preg_match('/@教练|@数据|@报表|@coach/i', $message)) {
            return 'coach';
        }

        // 2. 关键词权重评分
        $rules = self::getRules();
        $scores = ['sales' => 0, 'creator' => 0, 'coach' => 0];

        foreach ($rules as $roleKey => $rule) {
            if (!isset($scores[$roleKey])) {
                continue;
            }
            foreach ($rule['keywords'] as $kw) {
                if (mb_stripos($message, $kw) !== false) {
                    $scores[$roleKey] += ($rule['priority'] ?? 1);
                }
            }
        }

        // 3. 返回最高分
        arsort($scores);
        $top = array_key_first($scores);

        return ($scores[$top] > 0) ? $top : 'sales';
    }

    /**
     * 从数据库加载各角色触发规则
     */
    private static function getRules(): array
    {
        if (self::$rules) {
            return self::$rules;
        }

        $roles = Db::name('crm_ai_role')
            ->where('is_active', 1)
            ->select()
            ->toArray();

        foreach ($roles as $role) {
            $triggers = json_decode($role['triggers'], true);
            self::$rules[$role['role_key']] = [
                'keywords' => $triggers['keywords'] ?? [],
                'priority' => $triggers['priority'] ?? 1,
            ];
        }

        // 如果 DB 中没有数据（首次启动），使用硬编码默认值
        if (empty(self::$rules)) {
            self::$rules = self::getDefaults();
        }

        return self::$rules;
    }

    /**
     * 默认规则（DB 中没有角色数据时的 fallback）
     */
    private static function getDefaults(): array
    {
        return [
            'sales' => [
                'priority' => 1,
                'keywords' => [
                    '查', '看', '找', '联系', '电话', '跟进', '拜访',
                    '工商', '补全', '填充', '企查查', '天眼查',
                    '合同', '续约', '复购', '回访', '满意度', '到期',
                    '怎么样', '最近', '客户',
                ],
            ],
            'creator' => [
                'priority' => 1,
                'keywords' => [
                    '写', '文章', '推文', '公众号', '朋友圈', '文案',
                    '方案', '提案', '投标', '标书', '话术', '脚本',
                    '产品介绍', '对比', '大纲',
                ],
            ],
            'coach' => [
                'priority' => 1,
                'keywords' => [
                    '报表', '数据', '统计', '分析', '周报', '月报',
                    '趋势', 'KPI', '演练', '模拟', '排名',
                    '怎么说', '怎么提升', '原因', '转化率', '漏斗',
                ],
            ],
        ];
    }

    /**
     * 清除缓存（角色配置更新后调用）
     */
    public static function clearCache(): void
    {
        self::$rules = [];
    }

    /**
     * 获取角色名称
     */
    public static function getRoleName(string $roleKey): string
    {
        $names = [
            'sales'   => '销售助手',
            'creator' => '创作与方案专家',
            'coach'   => '业绩教练',
        ];
        return $names[$roleKey] ?? '销售助手';
    }
}
