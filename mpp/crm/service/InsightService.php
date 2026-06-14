<?php
namespace mpp\crm\service;

use app\platform\BaseService;
use mpp\crm\model\Insight as InsightModel;
use think\facade\Db;

/**
 * 洞察引擎 — 主动扫描、发现机会、智能推荐
 * A 类扫描（阶段2）：纯规则，不调 AI
 * B 类扫描（阶段3）：规则 + AI 语义
 */
class InsightService extends BaseService
{
    // 默认配置
    protected $config = [
        'repurchase_threshold' => 0.8,
        'quotation_overdue_days' => 7,
        'pool_warning_days' => 7,
        'churn_multiplier' => 1.5,
        'churn_no_followup_days' => 30,
        'dormant_days' => 90,
        'insight_max_per_user' => 10,
        'onboarding_customer_threshold' => 10,
        'onboarding_order_threshold' => 5,
    ];

    protected function loadConfig(int $storeId): void
    {
        // 从 crm_setting 读取洞察参数（与 CRM 设置页共用同一配置）
        $rows = Db::name('config')
            ->where('config_type', 'crm_setting')
            ->where(function ($q) use ($storeId) {
                $q->where('store_id', $storeId)->whereOr('store_id', 0);
            })
            ->select()->toArray();
        // 租户优先，系统兜底
        $values = [];
        foreach ($rows as $row) {
            $k = $row['config_name'];
            if (!isset($values[$k]) || $row['store_id'] == $storeId) {
                $values[$k] = $row['config_value'];
            }
        }
        // 映射 config_name → insight 参数
        $map = [
            'insight_repurchase_threshold' => 'repurchase_threshold',
            'insight_quotation_overdue_days' => 'quotation_overdue_days',
            'insight_pool_warning_days' => 'pool_warning_days',
            'insight_churn_multiplier' => 'churn_multiplier',
            'insight_churn_no_followup_days' => 'churn_no_followup_days',
            'insight_dormant_days' => 'dormant_days',
            'insight_max_per_user' => 'insight_max_per_user',
        ];
        foreach ($map as $configKey => $paramKey) {
            if (isset($values[$configKey]) && $values[$configKey] !== null && $values[$configKey] !== '') {
                $this->config[$paramKey] = $values[$configKey];
            }
        }
    }

    /** 每日扫描入口 */
    public function dailyScan(int $storeId): array
    {
        $this->loadConfig($storeId);
        $stats = ['cold_start' => false, 'cleaned' => 0, 'generated' => 0, 'rules' => []];

        // 0. 冷启动检查
        $customerCount = Db::name('crm_customer')
            ->where('store_id', $storeId)->where('is_delete', 0)->count();
        if ($customerCount < $this->config['onboarding_customer_threshold']) {
            $this->createOnboardingInsights($storeId);
            $stats['cold_start'] = true;
            return $stats;
        }

        // 1. 清理孤儿 insight
        $stats['cleaned'] = $this->cleanupOrphans($storeId);

        // 2. 加载数据（2 条 SQL）
        $customers = $this->loadCustomersWithData($storeId);
        $activeQuotations = $this->loadActiveQuotations($storeId);

        // 3. A 类扫描
        $allInsights = [];
        foreach ($customers as $c) {
            $cQuotations = $activeQuotations[$c['id']] ?? [];
            $allInsights = array_merge($allInsights, $this->scanFollowupOverdue($c, $cQuotations));
            $allInsights = array_merge($allInsights, $this->scanRepurchaseWindow($c));
            $allInsights = array_merge($allInsights, $this->scanPoolWarning($c));
            $allInsights = array_merge($allInsights, $this->scanChurnRisk($c, $cQuotations));
            $allInsights = array_merge($allInsights, $this->scanDormant($c));
        }

        // 4. 限制每人每日最大 insight 数
        $allInsights = $this->limitPerUser($allInsights);

        // 5. 批量 upsert
        $stats['generated'] = $this->batchUpsertInsights($allInsights);
        $stats['rules'] = $this->countByRule($allInsights);

        // 6. B 类 AI 增强（异步补：为 churn_risk/dormant/repurchase_window 生成建议文案）
        if ($stats['generated'] > 0) {
            try {
                $stats['ai_suggestions'] = $this->enrichWithAI($storeId);
            } catch (\Throwable $e) {
                $stats['ai_suggestions'] = 0;
            }
        }

        return $stats;
    }

    /** 实时事件驱动的 insight 失效 */
    public static function handleEvent(string $eventType, int $targetId): void
    {
        $map = [
            'follow_up_completed' => ['followup_overdue'],
            'quotation_sent'      => ['followup_overdue'],
            'quotation_confirmed' => ['followup_overdue', 'churn_risk'],
            'quotation_rejected'  => ['followup_overdue'],
            'quotation_converted' => ['followup_overdue', 'churn_risk'],
            'order_created'       => ['repurchase_window', 'churn_risk', 'dormant'],
            'customer_claimed'    => ['pool_warning'],
            'customer_released'   => ['pool_warning'],
            'order_status_changed'=> ['churn_risk'],
        ];
        $types = $map[$eventType] ?? [];
        foreach ($types as $type) {
            InsightModel::where('type', $type)
                ->where('target_type', 'customer')
                ->where('target_id', $targetId)
                ->where('status', 'active')
                ->update(['status' => 'resolved', 'update_time' => time()]);
        }
    }

    // ============ 扫描规则 ============

    /** 回复逾期：报价已发送但超时未回复 */
    protected function scanFollowupOverdue(array $customer, array $quotations): array
    {
        $results = [];
        $avgReplyDays = max($customer['avg_reply_days'] ?? 0, 3);
        $overdueDays = $this->config['quotation_overdue_days'];
        $threshold = max($avgReplyDays, $overdueDays);

        foreach ($quotations as $q) {
            if ($q['status'] != 2) continue; // 只查"已发送"
            $daysSince = intval((time() - $q['update_time']) / 86400);
            if ($daysSince < $threshold) continue;

            $date = date('Y-m-d');
            $key = "followup_overdue:customer:{$customer['id']}:{$customer['owner_user_id']}:{$date}";
            $results[] = [
                'idempotency_key' => $key,
                'type' => 'followup_overdue',
                'priority' => $daysSince > $threshold * 2 ? 2 : 1,
                'status' => 'active',
                'target_type' => 'customer',
                'target_id' => $customer['id'],
                'user_id' => $customer['owner_user_id'],
                'title' => "{$customer['customer_name']} 报价已发{$daysSince}天未回复",
                'summary' => "报价单 {$q['quotation_no']} 发送于 " . date('Y-m-d', $q['update_time']) . "，金额 ¥{$q['final_amount']}，已过{$daysSince}天未回复",
                'detail' => json_encode(['quotation_id' => $q['id'], 'quotation_no' => $q['quotation_no'], 'days_since_send' => $daysSince, 'avg_reply_days' => $avgReplyDays], JSON_UNESCAPED_UNICODE),
                'source_rule' => 'scanFollowupOverdue',
                'rule_params' => json_encode(['threshold' => $threshold, 'avg_reply_days' => $avgReplyDays], JSON_UNESCAPED_UNICODE),
                'action_url' => "/crm/quotation/detail?id={$q['id']}",
                'create_date' => $date,
                'create_time' => time(),
                'store_id' => $customer['store_id'],
            ];
        }
        return $results;
    }

    /** 复购窗口：距上次采购达到历史间隔的80% */
    protected function scanRepurchaseWindow(array $customer): array
    {
        if (($customer['order_count'] ?? 0) < 2) return [];
        if (empty($customer['avg_purchase_interval']) || $customer['avg_purchase_interval'] <= 0) return [];

        $daysSinceLast = intval((time() - $customer['last_order_time']) / 86400);
        $threshold = intval($customer['avg_purchase_interval'] * $this->config['repurchase_threshold']);
        if ($daysSinceLast < $threshold) return [];

        $date = date('Y-m-d');
        $key = "repurchase_window:customer:{$customer['id']}:{$customer['owner_user_id']}:{$date}";
        return [[
            'idempotency_key' => $key,
            'type' => 'repurchase_window',
            'priority' => $daysSinceLast >= $customer['avg_purchase_interval'] ? 2 : 1,
            'status' => 'active',
            'target_type' => 'customer',
            'target_id' => $customer['id'],
            'user_id' => $customer['owner_user_id'],
            'title' => "{$customer['customer_name']} 已进入复购窗口期",
            'summary' => "距上次采购{$daysSinceLast}天，历史平均采购间隔{$customer['avg_purchase_interval']}天，上次采购金额 ¥{$customer['last_order_amount']}",
            'detail' => json_encode(['days_since_last' => $daysSinceLast, 'avg_interval' => $customer['avg_purchase_interval'], 'threshold' => $threshold, 'last_order_id' => $customer['last_order_id'], 'last_order_amount' => $customer['last_order_amount']], JSON_UNESCAPED_UNICODE),
            'source_rule' => 'scanRepurchaseWindow',
            'rule_params' => json_encode(['threshold' => $this->config['repurchase_threshold']], JSON_UNESCAPED_UNICODE),
            'action_url' => "/crm/customer/detail?id={$customer['id']}",
            'create_date' => $date,
            'create_time' => time(),
            'store_id' => $customer['store_id'],
        ]];
    }

    /** 公海预警：即将掉入公海 */
    protected function scanPoolWarning(array $customer): array
    {
        if ($customer['owner_user_id'] == 0) return [];
        $poolDays = $this->getPoolDays($customer['store_id']);
        $lastFollowup = $customer['last_followup_time'] ?: $customer['create_time'];
        $daysSince = intval((time() - $lastFollowup) / 86400);
        $warningDays = $poolDays - $this->config['pool_warning_days'];
        if ($daysSince < $warningDays) return [];

        $remaining = $poolDays - $daysSince;
        $date = date('Y-m-d');
        $key = "pool_warning:customer:{$customer['id']}:{$customer['owner_user_id']}:{$date}";
        return [[
            'idempotency_key' => $key,
            'type' => 'pool_warning',
            'priority' => $remaining <= 3 ? 2 : 1,
            'status' => 'active',
            'target_type' => 'customer',
            'target_id' => $customer['id'],
            'user_id' => $customer['owner_user_id'],
            'title' => "{$customer['customer_name']} 还剩{$remaining}天将掉入公海",
            'summary' => "最后跟进于 " . date('Y-m-d', $lastFollowup) . "，距公海掉入还有{$remaining}天（公海规则：{$poolDays}天未跟进自动掉入）",
            'detail' => json_encode(['days_since_followup' => $daysSince, 'pool_days' => $poolDays, 'remaining_days' => $remaining, 'last_followup_time' => $lastFollowup], JSON_UNESCAPED_UNICODE),
            'source_rule' => 'scanPoolWarning',
            'rule_params' => json_encode(['pool_days' => $poolDays, 'warning_days' => $this->config['pool_warning_days']], JSON_UNESCAPED_UNICODE),
            'action_url' => "/crm/customer/detail?id={$customer['id']}",
            'create_date' => $date,
            'create_time' => time(),
            'store_id' => $customer['store_id'],
        ]];
    }

    /** 流失风险 */
    protected function scanChurnRisk(array $customer, array $quotations): array
    {
        if (($customer['order_count'] ?? 0) < 1) return [];
        if (empty($customer['avg_purchase_interval'])) return [];

        $daysSinceOrder = intval((time() - $customer['last_order_time']) / 86400);
        $threshold = intval($customer['avg_purchase_interval'] * $this->config['churn_multiplier']);
        if ($daysSinceOrder < $threshold) return [];

        // 近30天无跟进
        $lastFollowup = $customer['last_followup_time'] ?: 0;
        $daysSinceFollowup = intval((time() - $lastFollowup) / 86400);
        if ($daysSinceFollowup < $this->config['churn_no_followup_days']) return [];

        // 有进行中的报价不预警
        foreach ($quotations as $q) {
            if (in_array($q['status'], [1, 2])) return []; // 草稿或已发送
        }

        $date = date('Y-m-d');
        $key = "churn_risk:customer:{$customer['id']}:{$customer['owner_user_id']}:{$date}";
        return [[
            'idempotency_key' => $key,
            'type' => 'churn_risk',
            'priority' => 2,
            'status' => 'active',
            'target_type' => 'customer',
            'target_id' => $customer['id'],
            'user_id' => $customer['owner_user_id'],
            'title' => "{$customer['customer_name']} 存在流失风险",
            'summary' => "距上次采购{$daysSinceOrder}天（历史间隔{$customer['avg_purchase_interval']}天），近30天无跟进记录",
            'detail' => json_encode(['days_since_order' => $daysSinceOrder, 'avg_interval' => $customer['avg_purchase_interval'], 'days_since_followup' => $daysSinceFollowup], JSON_UNESCAPED_UNICODE),
            'source_rule' => 'scanChurnRisk',
            'rule_params' => json_encode(['churn_multiplier' => $this->config['churn_multiplier']], JSON_UNESCAPED_UNICODE),
            'action_url' => "/crm/customer/detail?id={$customer['id']}",
            'create_date' => $date,
            'create_time' => time(),
            'store_id' => $customer['store_id'],
        ]];
    }

    /** 沉睡客户 */
    protected function scanDormant(array $customer): array
    {
        if (($customer['order_count'] ?? 0) < 1) return [];
        $lastFollowup = $customer['last_followup_time'] ?: 0;
        $daysSince = intval((time() - $lastFollowup) / 86400);
        if ($daysSince < $this->config['dormant_days']) return [];

        $date = date('Y-m-d');
        $key = "dormant:customer:{$customer['id']}:{$customer['owner_user_id']}:{$date}";
        return [[
            'idempotency_key' => $key,
            'type' => 'dormant',
            'priority' => 1,
            'status' => 'active',
            'target_type' => 'customer',
            'target_id' => $customer['id'],
            'user_id' => $customer['owner_user_id'],
            'title' => "{$customer['customer_name']} 已沉睡{$daysSince}天",
            'summary' => "该客户曾有成交但超过{$daysSince}天未跟进，建议主动联系唤醒",
            'detail' => json_encode(['days_since_followup' => $daysSince, 'last_order_amount' => $customer['last_order_amount'] ?? 0, 'order_count' => $customer['order_count']], JSON_UNESCAPED_UNICODE),
            'source_rule' => 'scanDormant',
            'rule_params' => json_encode(['dormant_days' => $this->config['dormant_days']], JSON_UNESCAPED_UNICODE),
            'action_url' => "/crm/customer/detail?id={$customer['id']}",
            'create_date' => $date,
            'create_time' => time(),
            'store_id' => $customer['store_id'],
        ]];
    }

    // ============ 数据加载 ============

    protected function loadCustomersWithData(int $storeId): array
    {
        // 1. 加载客户（2 条 SQL，MySQL 5.7 兼容）
        $customers = Db::name('crm_customer')
            ->where('store_id', $storeId)->where('is_delete', 0)->where('status', 1)
            ->field(['id', 'customer_name', 'store_id', 'owner_user_id', 'create_time', 'last_followup_time', 'funnel_stage'])
            ->select()->toArray();

        // 2. 加载所有有效订单（按时间排序，用于 PHP 计算间隔）
        $orders = Db::name('crm_order')
            ->where('store_id', $storeId)->where('is_delete', 0)
            ->whereIn('status', [4, 5]) // 已发货 + 已完成
            ->field(['id', 'customer_id', 'final_amount', 'create_time'])
            ->order('customer_id')->order('create_time', 'asc')
            ->select()->toArray();

        // 3. 按客户分组订单，计算采购间隔
        $orderByCustomer = [];
        foreach ($orders as $o) {
            $orderByCustomer[$o['customer_id']][] = $o;
        }

        // 4. 为每个客户补充订单统计
        foreach ($customers as &$c) {
            $custOrders = $orderByCustomer[$c['id']] ?? [];
            $c['order_count'] = count($custOrders);
            if ($c['order_count'] > 0) {
                $last = end($custOrders);
                $c['last_order_time'] = $last['create_time'];
                $c['last_order_id'] = $last['id'];
                $c['last_order_amount'] = $last['final_amount'];

                // 计算平均采购间隔（天数）
                if ($c['order_count'] >= 2) {
                    $gaps = [];
                    $prevTime = null;
                    foreach ($custOrders as $o) {
                        if ($prevTime !== null) {
                            $gaps[] = intval(($o['create_time'] - $prevTime) / 86400);
                        }
                        $prevTime = $o['create_time'];
                    }
                    $c['avg_purchase_interval'] = !empty($gaps) ? intval(array_sum($gaps) / count($gaps)) : 0;
                } else {
                    $c['avg_purchase_interval'] = 0;
                }
            } else {
                $c['last_order_time'] = null;
                $c['last_order_id'] = null;
                $c['last_order_amount'] = 0;
                $c['avg_purchase_interval'] = 0;
            }

            // 平均回复天数：从报价表统计（已发送→已确认的时间差）
            $replyStats = Db::name('crm_quotation')
                ->where('store_id', $storeId)->where('is_delete', 0)
                ->where('customer_id', $c['id'])
                ->whereIn('status', [3, 4, 5]) // 已确认/已拒绝/已转订单
                ->where('update_time', '>', 0)
                ->field('create_time, update_time')
                ->select()->toArray();
            if (!empty($replyStats)) {
                $replyGaps = [];
                foreach ($replyStats as $r) {
                    $gap = intval(($r['update_time'] - $r['create_time']) / 86400);
                    if ($gap > 0) $replyGaps[] = $gap;
                }
                $c['avg_reply_days'] = !empty($replyGaps) ? intval(array_sum($replyGaps) / count($replyGaps)) : 0;
            } else {
                $c['avg_reply_days'] = 0;
            }
        }
        unset($c);

        return $customers;
    }

    protected function loadActiveQuotations(int $storeId): array
    {
        $rows = Db::name('crm_quotation')
            ->where('store_id', $storeId)
            ->where('is_delete', 0)
            ->whereIn('status', [1, 2]) // 草稿 + 已发送
            ->field(['id', 'quotation_no', 'customer_id', 'status', 'final_amount', 'update_time'])
            ->select()->toArray();

        $byCustomer = [];
        foreach ($rows as $r) {
            $byCustomer[$r['customer_id']][] = $r;
        }
        return $byCustomer;
    }

    // ============ 辅助方法 ============

    protected function getPoolDays(int $storeId): int
    {
        $config = Db::name('config')
            ->where('store_id', 0)
            ->where('config_type', 'crm_setting')
            ->where('config_name', 'pool_days')
            ->value('config_value');
        return intval($config ?: 180);
    }

    protected function createOnboardingInsights(int $storeId): void
    {
        // 冷启动时不生成洞察，面板显示引导
    }

    protected function cleanupOrphans(int $storeId): int
    {
        $deletedIds = Db::name('crm_customer')
            ->where('store_id', $storeId)->where('is_delete', 1)->column('id');
        if (empty($deletedIds)) return 0;

        return InsightModel::where('target_type', 'customer')
            ->whereIn('target_id', $deletedIds)
            ->where('status', 'active')
            ->update(['status' => 'resolved', 'update_time' => time()]);
    }

    protected function limitPerUser(array $insights): array
    {
        $max = intval($this->config['insight_max_per_user']);
        $byUser = [];
        foreach ($insights as $insight) {
            $byUser[$insight['user_id']][] = $insight;
        }
        $result = [];
        foreach ($byUser as $uid => $list) {
            // 按 priority 降序排列，取前 N
            usort($list, function ($a, $b) { return $b['priority'] - $a['priority']; });
            $result = array_merge($result, array_slice($list, 0, $max));
        }
        return $result;
    }

    protected function batchUpsertInsights(array $insights): int
    {
        $count = 0;
        foreach ($insights as $row) {
            $exists = InsightModel::where('idempotency_key', $row['idempotency_key'])->find();
            if ($exists) {
                // 更新 summary、priority（保留 dismissed 状态）
                if ($exists['status'] === 'dismissed') continue;
                $exists->summary = $row['summary'];
                $exists->priority = $row['priority'];
                $exists->detail = $row['detail'];
                $exists->update_time = time();
                $exists->save();
            } else {
                $model = new InsightModel();
                foreach ($row as $k => $v) {
                    $model->$k = $v;
                }
                $model->save();
                $count++;
            }
        }
        return $count;
    }

    protected function countByRule(array $insights): array
    {
        $rules = [];
        foreach ($insights as $insight) {
            $rule = $insight['source_rule'] ?? 'unknown';
            $rules[$rule] = ($rules[$rule] ?? 0) + 1;
        }
        return $rules;
    }

    // ============ B 类 AI 增强 ============

    /** 为今日生成的 churn_risk / dormant / repurchase_window 洞察生成 AI 建议文案 */
    protected function enrichWithAI(int $storeId): int
    {
        $today = date('Y-m-d');
        $insights = InsightModel::where('store_id', $storeId)
            ->where('status', 'active')->where('create_date', $today)
            ->whereIn('type', ['churn_risk', 'dormant', 'repurchase_window'])
            ->whereNull('suggestion_id')
            ->select()->toArray();

        $count = 0;
        foreach ($insights as $insight) {
            try {
                switch ($insight['type']) {
                    case 'churn_risk': $suggestionId = $this->enrichChurnRisk($insight); break;
                    case 'dormant': $suggestionId = $this->enrichDormant($insight); break;
                    case 'repurchase_window': $suggestionId = $this->enrichRepurchaseWindow($insight); break;
                    default: $suggestionId = null; break;
                }
                if ($suggestionId) {
                    InsightModel::where('id', $insight['id'])->update([
                        'suggestion_id' => $suggestionId, 'update_time' => time()
                    ]);
                    $count++;
                }
            } catch (\Throwable $e) {
                // 单条失败不影响其他
            }
        }
        return $count;
    }

    protected function enrichChurnRisk(array $insight): ?int
    {
        $detail = json_decode($insight['detail'], true) ?: [];
        $prompt = "客户{$insight['title']}。{$insight['summary']}。请给出具体的挽留策略和跟进话术，30字以内。";
        $result = \app\common\service\AiService::quickAsk($prompt);
        if (!$result['success'] || empty($result['data'])) return null;
        return $this->saveAiSuggestion($result['data'], $insight, 'insight');
    }

    protected function enrichDormant(array $insight): ?int
    {
        $prompt = "{$insight['title']}。{$insight['summary']}。请给出重新激活该客户的策略建议，30字以内。";
        $result = \app\common\service\AiService::quickAsk($prompt);
        if (!$result['success'] || empty($result['data'])) return null;
        return $this->saveAiSuggestion($result['data'], $insight, 'insight');
    }

    protected function enrichRepurchaseWindow(array $insight): ?int
    {
        $prompt = "{$insight['title']}。{$insight['summary']}。请给出主动联系话术建议，30字以内。";
        $result = \app\common\service\AiService::quickAsk($prompt);
        if (!$result['success'] || empty($result['data'])) return null;
        return $this->saveAiSuggestion($result['data'], $insight, 'insight');
    }

    protected function saveAiSuggestion(string $content, array $insight, string $source): int
    {
        $model = new \mpp\crm\model\AiSuggestion();
        $model->insight_id = $insight['id'];
        $model->source = $source;
        $model->target_type = $insight['target_type'];
        $model->target_id = $insight['target_id'];
        $model->content = json_encode(['text' => $content], JSON_UNESCAPED_UNICODE);
        $model->prompt_hash = substr(sha1($content), 0, 16);
        $model->create_time = time();
        $model->store_id = $insight['store_id'];
        $model->save();
        return $model->id;
    }
}
