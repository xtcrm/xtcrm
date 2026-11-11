<?php

declare (strict_types=1);

namespace mpp\crm\service;

use app\platform\BaseService;
use think\facade\Db;

/**
 * AI 对话引擎 —— 会话管理 + 上下文组装 + 消息持久化
 *
 * 包装 AgentService，负责：
 *  - 会话生命周期（创建/复用/归档）
 *  - 上下文组装（页面 + 客户记忆 + 用户偏好）
 *  - 消息持久化（crm_ai_conversation）
 *  - 历史加载（最近 N 条 + 长对话压缩）
 */
class AiConversationService extends BaseService
{
    private int $userId;
    private string $table = 'crm_ai_conversation';

    public function __construct(int $storeId, int $userId)
    {
        $this->storeId = $storeId;
        $this->userId  = $userId;
    }

    // ============================================================
    //  会话管理
    // ============================================================

    /**
     * 解析当前上下文对应的会话
     *
     * 同一 (store_id, user_id, context_type, context_id) → 同一会话
     * 24h 内有活动 → 复用；否则新建
     *
     * @param array $context ['page' => 'customer-detail', 'customer_id' => 51, ...]
     */
    public function resolveSession(array $context): string
    {
        $contextType = $context['page'] ?? 'general';
        $contextId   = (int)($context['customer_id'] ?? $context['lead_id'] ?? 0);

        // 查是否有活跃会话
        $existing = Db::name($this->table)
            ->where('store_id', $this->storeId)
            ->where('user_id', $this->userId)
            ->where('context_type', $contextType)
            ->where('context_id', $contextId)
            ->where('status', 1)
            ->where('create_time', '>', time() - 86400)
            ->where('role', 'user')
            ->order('id', 'desc')
            ->value('session_id');

        if ($existing) {
            return $existing;
        }

        return $this->createSession($contextType, $contextId);
    }

    /**
     * 强制新建会话（用户点击「新对话」）
     */
    public function newSession(array $context): string
    {
        $contextType = $context['page'] ?? 'general';
        $contextId   = (int)($context['customer_id'] ?? $context['lead_id'] ?? 0);
        return $this->createSession($contextType, $contextId);
    }

    /**
     * 获取用户的所有会话列表
     */
    public function listSessions(int $limit = 20): array
    {
        return Db::query(
            "SELECT t1.session_id, t1.context_type, t1.context_id,
                    (SELECT content FROM {$this->table} t2
                     WHERE t2.session_id = t1.session_id AND t2.role = 'user'
                     ORDER BY t2.id ASC LIMIT 1) AS first_message,
                    MAX(t1.create_time) AS last_time,
                    COUNT(*) AS message_count
             FROM {$this->table} t1
             WHERE t1.store_id = ? AND t1.user_id = ? AND t1.status = 1
             GROUP BY t1.session_id, t1.context_type, t1.context_id
             ORDER BY last_time DESC
             LIMIT ?",
            [$this->storeId, $this->userId, $limit]
        );
    }

    // ============================================================
    //  历史加载
    // ============================================================

    /**
     * 加载会话历史（最近 N 条，超过阈值自动压缩）
     *
     * @return array  [['role' => 'user'|'assistant', 'content' => '...'], ...]
     */
    public function loadHistory(string $sessionId, int $limit = 30): array
    {
        $rows = Db::name($this->table)
            ->where('store_id', $this->storeId)
            ->where('session_id', $sessionId)
            ->where('role', '<>', 'system')
            ->order('id', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();

        // 倒序为时间正序
        $rows = array_reverse($rows);

        // 超过 30 轮—生成摘要压缩
        $total = Db::name($this->table)
            ->where('store_id', $this->storeId)
            ->where('session_id', $sessionId)
            ->where('role', '<>', 'system')
            ->count();

        if ($total > 30 && count($rows) >= 30) {
            $summary = $this->summarizeHistory($sessionId, $rows);
            // 保留最近 10 条原始消息 + 摘要
            $rows = array_merge(
                [['role' => 'system', 'content' => '[对话摘要] ' . $summary]],
                array_slice($rows, -10)
            );
        }

        return array_map(function ($r) {
            return ['role' => $r['role'], 'content' => $r['content']];
        }, $rows);
    }

    // ============================================================
    //  上下文组装
    // ============================================================

    /**
     * 构建 system prompt（含页面上下文 + 客户记忆 + 用户偏好 + 安全硬化）
     */
    public function buildSystemPrompt(string $roleKey, array $context): string
    {
        // 1. 角色基础 prompt
        $role = Db::name('crm_ai_role')
            ->where('role_key', $roleKey)
            ->where('is_active', 1)
            ->find();

        $prompt = $role['system_prompt'] ?? '你是CRM智能助手。';

        // 2. 租户 + 用户信息 + 当前时间
        $prompt .= "\n当前日期: " . date('Y-m-d H:i') . "，租户ID: {$this->storeId}，用户ID: {$this->userId}";

        // 2.5. 注入数据字典（AI 了解系统数据结构）
        $prompt .= $this->buildSchemaContext();

        // 3. 页面上下文
        $prompt .= $this->buildPageContext($context);

        // 4. 客户记忆 L3（最近跟进记录 — 不额外调 LLM）
        $prompt .= $this->buildCustomerMemory($context);

        // 5. 用户记忆 L4
        $prompt .= $this->buildUserMemory();

        // 6. 安全硬化
        $prompt = PromptGuard::hardenedSystemPrompt($prompt, $this->storeId, $this->userId);

        return $prompt;
    }

    /**
     * 构建 Payload（给 AiController 用的完整数据包）
     */
    public function buildPayload(string $message, string $roleKey, array $context, string $sessionId): array
    {
        $systemPrompt = $this->buildSystemPrompt($roleKey, $context);
        $history      = $this->loadHistory($sessionId, 30);

        return [
            'session_id'    => $sessionId,
            'role_key'      => $roleKey,
            'system_prompt' => $systemPrompt,
            'history'       => $history,
            'message'       => $message,
            'context'       => $context,
            'store_id'      => $this->storeId,
            'user_id'       => $this->userId,
        ];
    }

    /**
     * 加载会话的完整消息列表（供前端展示）
     */
    public function loadMessages(string $sessionId, int $limit = 50): array
    {
        $rows = Db::name($this->table)
            ->where('store_id', $this->storeId)
            ->where('session_id', $sessionId)
            ->where('role', '<>', 'system')
            ->order('id', 'asc')
            ->limit($limit)
            ->field('role, content, role_key, tool_calls, create_time')
            ->select()
            ->toArray();

        return array_map(function ($r) {
            return [
                'role'    => $r['role'],
                'content' => $r['content'],
            ];
        }, $rows);
    }

    // ============================================================
    //  消息持久化
    // ============================================================

    /**
     * 保存用户消息
     */
    public function saveUserMessage(string $sessionId, string $content, array $context)
    {
        return Db::name($this->table)->insertGetId([
            'store_id'     => $this->storeId,
            'user_id'      => $this->userId,
            'session_id'   => $sessionId,
            'role'         => 'user',
            'content'      => $content,
            'context_type' => $context['page'] ?? 'general',
            'context_id'   => (int)($context['customer_id'] ?? $context['lead_id'] ?? 0),
            'create_time'  => time(),
        ]);
    }

    /**
     * 保存助手消息
     */
    public function saveAssistantMessage(string $sessionId, string $content, array $toolCalls, string $roleKey)
    {
        return Db::name($this->table)->insertGetId([
            'store_id'     => $this->storeId,
            'user_id'      => $this->userId,
            'session_id'   => $sessionId,
            'role_key'     => $roleKey,
            'role'         => 'assistant',
            'content'      => $content,
            'tool_calls'   => empty($toolCalls) ? null : json_encode($toolCalls, JSON_UNESCAPED_UNICODE),
            'create_time'  => time(),
        ]);
    }

    /**
     * 追加助手消息（异步任务回填用，已指定 ID）
     */
    public function appendAssistantMessage(string $sessionId, string $content, string $roleKey, int $id): void
    {
        Db::name($this->table)->where('id', $id)->update([
            'content' => $content,
        ]);
    }

    // ============================================================
    //  异步任务
    // ============================================================

    /**
     * 查询异步任务状态
     */
    public function checkTaskStatus(string $taskId): array
    {
        $task = Db::name('crm_ai_async_task')
            ->where('task_id', $taskId)
            ->where('store_id', $this->storeId)
            ->find();

        if (!$task) {
            return ['status' => 'not_found'];
        }

        return [
            'status'          => $task['status'],
            'conversation_id' => $task['conversation_id'] ?? null,
            'preview'         => $task['status'] === 'completed'
                ? mb_substr($task['result'] ?? '', 0, 200) . '...' : null,
            'error'           => $task['error'] ?? null,
            'duration_ms'     => $task['duration_ms'] ?? null,
        ];
    }

    // ============================================================
    //  工具执行路由（委托给 AgentService）
    // ============================================================

    /**
     * 执行单个工具（供 SSE 循环和 MCP 共用）
     *
     * @return array 工具执行结果
     */
    public function executeTool(string $name, array $args): array
    {
        $agentService = new AgentService();
        // 通过反射调用 AgentService::executeTool（它是 protected 方法）
        $ref = new \ReflectionMethod(AgentService::class, 'executeTool');
        $ref->setAccessible(true);
        return $ref->invoke($agentService, $name, $args, $this->storeId, $this->userId);
    }

    // ============================================================
    //  Private helpers
    // ============================================================

    /**
     * 加载数据字典，注入 system prompt
     * 让 AI 理解系统有哪些表、字段、关系和规则
     */
    private function buildSchemaContext(): string
    {
        $path = dirname(__DIR__) . '/schema.json';
        if (!file_exists($path)) return '';

        $schema = json_decode(file_get_contents($path), true);
        if (!$schema) return '';

        $lines = ["\n## 系统数据字典"];

        // 表关系
        $lines[] = "\n### 数据关系";
        $relations = $schema['relations'] ?? [];
        foreach ($relations as $label => $items) {
            $tables = [];
            foreach ($items as $item) {
                $tables[] = "{$item['table']}(via {$item['via']})" . (isset($item['note']) ? "[{$item['note']}]" : '');
            }
            $lines[] = "- {$label}: " . implode(', ', $tables);
        }

        // 表定义（精简版：只列关键字段和查询提示）
        $lines[] = "\n### 表结构（仅列出查询常用字段）";
        $tables = $schema['tables'] ?? [];
        foreach ($tables as $name => $def) {
            $fields = [];
            foreach ($def['fields'] as $fname => $finfo) {
                $note = isset($finfo['note']) ? "({$finfo['note']})" : '';
                $values = isset($finfo['values']) ? '=' . json_encode($finfo['values'], JSON_UNESCAPED_UNICODE) : '';
                if (in_array($finfo['type'] ?? '', ['int','string','date','timestamp','text','enum','bool','decimal'])) {
                    $fields[] = "{$fname}({$finfo['label']}{$values}){$note}";
                }
            }
            $lines[] = "- {$name}({$def['name']}): " . implode(', ', array_slice($fields, 0, 12));
            if (count($fields) > 12) $lines[] = "  ...";
        }

        // 关键规则
        $lines[] = "\n### 查询注意事项";
        $rules = $schema['rules'] ?? [];
        foreach ($rules as $rule) {
            $lines[] = "- {$rule}";
        }

        return implode("\n", $lines);
    }

    private function createSession(string $contextType, int $contextId): string
    {
        return sprintf('%s_%d_%d_%s_%s',
            $contextType,
            $contextId,
            $this->userId,
            date('YmdHis'),
            substr(bin2hex(random_bytes(4)), 0, 8)
        );
    }

    private function buildPageContext(array $context): string
    {
        $lines = [];

        if ($customerId = (int)($context['customer_id'] ?? 0)) {
            $c = Db::name('crm_customer')
                ->where('id', $customerId)
                ->where('store_id', $this->storeId)
                ->where('is_delete', 0)
                ->find();

            if ($c) {
                $lines[] = "当前页面：客户详情（客户ID={$customerId}）";
                $lines[] = "客户：{$c['customer_name']}，行业={$c['industry']}，等级={$c['level_name']}，分组={$c['customer_group']}";
                $lines[] = "最近跟进：{$c['last_followup_time']}";
            }
        }

        if ($leadId = (int)($context['lead_id'] ?? 0)) {
            $l = Db::name('crm_leads')
                ->where('id', $leadId)
                ->where('store_id', $this->storeId)
                ->find();
            if ($l) {
                $lines[] = "当前页面：线索详情";
                $lines[] = "线索：{$l['lead_name']}，公司={$l['company_name']}，状态={$l['status']}";
            }
        }

        if ($date = ($context['date'] ?? '')) {
            $lines[] = "当前查看日期：{$date}";
        }

        if ($listContext = ($context['list_context'] ?? '')) {
            $lines[] = $listContext;
        }

        return $lines ? "\n" . implode("\n", $lines) : '';
    }

    private function buildCustomerMemory(array $context): string
    {
        $customerId = (int)($context['customer_id'] ?? 0);
        if (!$customerId) return '';

        // 取最近 5 条跟进记录（免费数据，不调 LLM）
        $followups = Db::name('crm_followup')
            ->where('customer_id', $customerId)
            ->where('is_delete', 0)
            ->where('store_id', $this->storeId)
            ->order('follow_date', 'desc')
            ->limit(5)
            ->field('follow_type, follow_content, follow_date')
            ->select()
            ->toArray();

        if (empty($followups)) return '';

        // 精简输出：仅取前 100 字 + 日期 + 方式
        $lines = [];
        foreach ($followups as $f) {
            $date   = date('m-d', $f['follow_date']);
            $type   = $f['follow_type'] ?? '-';
            $detail = mb_substr(strip_tags($f['follow_content'] ?? ''), 0, 100);
            $lines[] = "[{$date}] {$type}: {$detail}";
        }

        return "\n最近跟进记录：\n" . implode("\n", $lines);
    }

    private function buildUserMemory(): string
    {
        $prefs = Db::name('crm_ai_user_memory')
            ->where('store_id', $this->storeId)
            ->where('user_id', $this->userId)
            ->where('memory_key', 'preferred_style')
            ->value('memory_value');

        if (!$prefs) return '';

        $p = json_decode($prefs, true);
        if (!$p) return '';

        $parts = [];
        if (!empty($p['tone'])) $parts[] = "回复风格={$p['tone']}";
        if (!empty($p['detail_level'])) $parts[] = "详细程度={$p['detail_level']}";

        return $parts ? "\n用户偏好：" . implode('，', $parts) : '';
    }

    /**
     * 长对话摘要压缩（调 LLM 生成简短摘要）
     */
    private function summarizeHistory(string $sessionId, array $rows): string
    {
        // 简化版：取前 10 条 user 消息的前 50 字作为摘要
        $userMessages = [];
        foreach ($rows as $r) {
            if ($r['role'] === 'user') {
                $userMessages[] = mb_substr(strip_tags($r['content']), 0, 50);
            }
        }

        if (empty($userMessages)) {
            return '历史对话';
        }

        // 只保留最近 5 条关键话题
        $topics = array_slice($userMessages, -5);
        return '之前讨论过: ' . implode('; ', $topics);
    }
}
