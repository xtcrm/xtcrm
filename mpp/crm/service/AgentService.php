<?php
namespace mpp\crm\service;

use app\platform\BaseService;
use app\platform\ai\AiGateway as AiService;
use cores\SkillPipeline;
use skills\contentengine\service\PresetLoader;
use think\facade\Db;

class AgentService extends BaseService
{
    /** @var string 当前角色 key（sales/creator/coach），用于工具过滤 */
    protected $roleKey = 'sales';
    /** @var int 当前用户 ID，用于权限过滤 */
    protected $userId = 0;

    /**
     * 设置当前对话的角色
     */
    public function setRoleKey(string $roleKey): void
    {
        $this->roleKey = $roleKey;
    }

    /**
     * 设置当前用户 ID
     */
    public function setUserId(int $userId): void
    {
        $this->userId = $userId;
    }

    /** 工具注册表（含技能包工具） */
    protected function getTools(): array
    {
        // 优先从 AiToolRegistry 读取（按当前角色过滤权限）
        $roleTools = AiToolRegistry::getToolsForUser(
            $this->userId ?? 0,
            $this->storeId ?? 0,
            $this->roleKey
        );

        if (!empty($roleTools)) {
            // 动态注入已安装技能包的工具
            $skillTools = SkillPipeline::getAllSkillTools();
            $allTools = array_merge($roleTools, $skillTools);
            // 修复：properties 空数组 → 空对象（DeepSeek 要求 object 类型）
            foreach ($allTools as &$t) {
                if (isset($t['parameters']['properties']) && is_array($t['parameters']['properties']) && empty($t['parameters']['properties'])) {
                    $t['parameters']['properties'] = (object)[];
                }
            }
            return $allTools;
        }

        // Fallback：AiToolRegistry 未初始化时用硬编码工具（保持向后兼容）
        return $this->getFallbackTools();
    }

    /**
     * Fallback 工具列表（AiToolRegistry 中的角色配置未写入 DB 时使用）
     */
    private function getFallbackTools(): array
    {
        $tools = [
            [
                'name' => 'searchCustomer',
                'description' => '按名称/行业/地区搜索客户，返回匹配的客户列表',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'keyword' => ['type' => 'string', 'description' => '搜索关键词（名称/行业/地区）'],
                    ],
                    'required' => ['keyword'],
                ],
            ],
            [
                'name' => 'getCustomerDetail',
                'description' => '获取指定客户的详细信息，包含跟进摘要、订单数量、报价情况',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'customer_id' => ['type' => 'integer', 'description' => '客户ID'],
                    ],
                    'required' => ['customer_id'],
                ],
            ],
            [
                'name' => 'getOrderList',
                'description' => '查询指定客户的订单列表，返回订单号、金额、状态、时间',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'customer_id' => ['type' => 'integer', 'description' => '客户ID'],
                    ],
                    'required' => ['customer_id'],
                ],
            ],
            [
                'name' => 'getQuotationList',
                'description' => '查询指定客户的报价列表',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'customer_id' => ['type' => 'integer', 'description' => '客户ID'],
                    ],
                    'required' => ['customer_id'],
                ],
            ],
            [
                'name' => 'getFollowupHistory',
                'description' => '获取指定客户的近期跟进记录',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'customer_id' => ['type' => 'integer', 'description' => '客户ID'],
                    ],
                    'required' => ['customer_id'],
                ],
            ],
            [
                'name' => 'getMyStats',
                'description' => '查询当前用户的业绩数据（客户数、本月成交额、排名）',
                'parameters' => ['type' => 'object', 'properties' => (object)[]],
            ],
            [
                'name' => 'getDashboard',
                'description' => '获取仪表盘数据：本月新增客户（含名称）、本月业绩（报价/订单）、待跟进客户列表、团队概况',
                'parameters' => ['type' => 'object', 'properties' => (object)[]],
            ],
            [
                'name' => 'searchProduct',
                'description' => '搜索产品库，返回匹配的产品列表',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'keyword' => ['type' => 'string', 'description' => '产品名称关键词'],
                    ],
                    'required' => ['keyword'],
                ],
            ],
            // Write tools（fallback 也要有）
            [
                'name' => 'addFollowup',
                'description' => '为客户添加跟进记录，需要 customer_id 和 follow_content',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'customer_id'   => ['type' => 'integer', 'description' => '客户ID'],
                        'follow_content'=> ['type' => 'string', 'description' => '跟进内容'],
                        'follow_type'   => ['type' => 'string', 'description' => '跟进方式：电话/拜访/微信/邮件'],
                    ],
                    'required' => ['customer_id', 'follow_content'],
                ],
            ],
            [
                'name' => 'addCustomerNote',
                'description' => '为客户追加备注',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'customer_id' => ['type' => 'integer', 'description' => '客户ID'],
                        'note'        => ['type' => 'string', 'description' => '备注内容'],
                    ],
                    'required' => ['customer_id', 'note'],
                ],
            ],
        ];

        // 动态注入已安装技能包的工具
        $skillTools = SkillPipeline::getAllSkillTools();
        $allTools = array_merge($tools, $skillTools);
        // 修复：properties 空数组 → 空对象（DeepSeek要求 object 类型）
        foreach ($allTools as &$t) {
            if (isset($t['parameters']['properties']) && is_array($t['parameters']['properties']) && empty($t['parameters']['properties'])) {
                $t['parameters']['properties'] = (object)[];
            }
        }
        return $allTools;
    }

    /** 对话入口 */
    public function chat(string $message, int $userId): array
    {
        $storeId = $this->storeId;
        $tools = $this->getTools();
        $allToolResults = [];

        // 系统提示（含技能包上下文）
        $skillsCtx = SkillPipeline::getSkillsContext();
        $systemPrompt = "你是CRM销售助手。根据用户需求调用工具获取数据并简洁回答。金额用万元/元表示，日期用中文格式。回答控制在300字以内。\n\n"
            . "工具使用策略：\n"
            . "1. 用户提到报价单号（如Q-20260506-103）→ 优先用技能包工具 skill_quote-cn_getQuotationData 查找\n"
            . "2. 用户提到客户名+报价 → 先用 searchCustomer 找到客户，再用技能工具查报价\n"
            . "3. 查到报价单后，在回复末尾用 Markdown 链接格式给出操作入口，如：[📄 预览中文报价](/crm/quotation/cn-preview?id=14)\n"
            . "4. 链接必须用 [文字](路径) 格式，让用户能直接点击跳转\n"
            . "5. 用户问业绩/订单/产品 → 用对应的内置工具\n"
            . "当前租户ID: {$storeId}，当前用户ID: {$userId}。";
        if ($skillsCtx) {
            $systemPrompt .= "\n" . $skillsCtx;
        }
        $messages = [['role' => 'system', 'content' => $systemPrompt]];
        $messages[] = ['role' => 'user', 'content' => $message];

        $maxRounds = 8;
        for ($round = 0; $round < $maxRounds; $round++) {
            $result = AiService::functionCall($messages, $tools);

            if (!$result['success']) {
                return ['success' => false, 'data' => null, 'error' => $result['error'], 'tools' => $allToolResults];
            }

            $data = $result['data'];
            $allToolResults = $data['tool_results'] ?? [];

            if ($data['type'] === 'text') {
                // AI 返回最终文本
                $sessionId = md5($message . time());
                $this->logAgentSession($sessionId, $message, $allToolResults, $data['content'], $userId);
                return [
                    'success' => true,
                    'data' => ['content' => $data['content'], 'tools' => $allToolResults, 'session_id' => $sessionId],
                    'error' => '',
                ];
            }

            // tool_call → 执行工具
            $messages = $data['messages'];
            // 必须添加 assistant 的 tool_calls 消息，否则 DeepSeek 报错：
            // "Messages with role 'tool' must be a response to a preceding message with 'tool_calls'"
            $messages[] = ['role' => 'assistant', 'tool_calls' => $data['tool_calls']];
            foreach ($data['tool_calls'] as $tc) {
                $funcName = $tc['function']['name'];
                $funcArgs = json_decode($tc['function']['arguments'], true) ?: [];
                $toolResult = $this->executeTool($funcName, $funcArgs, $storeId, $userId);
                $messages[] = [
                    'role' => 'tool',
                    'tool_call_id' => $tc['id'],
                    'content' => json_encode($toolResult, JSON_UNESCAPED_UNICODE),
                ];
            }
        }

        return ['success' => false, 'data' => null, 'error' => '对话轮次超限，请简化问题重试', 'tools' => $allToolResults];
    }

    /**
     * 多轮对话（接受外部组装的 system prompt + history）
     *
     * 供 AiConversationService 调用。与 chat() 的区别：
     *  - system prompt 由外部组装（含上下文、角色、安全规则）
     *  - history 由外部加载（含历史消息和摘要）
     *  - 不做 session 管理（由调用方负责）
     */
    public function chatWithContext(string $systemPrompt, array $history, string $userMessage, int $userId, int $storeId): array
    {
        $tools = $this->getTools();
        $allToolResults = [];

        $messages = [['role' => 'system', 'content' => $systemPrompt]];
        foreach ($history as $h) {
            $messages[] = ['role' => $h['role'], 'content' => $h['content']];
        }
        $messages[] = ['role' => 'user', 'content' => $userMessage];

        for ($round = 0; $round < 8; $round++) {
            $result = AiService::functionCall($messages, $tools);

            if (!$result['success']) {
                return ['success' => false, 'data' => null, 'error' => $result['error'], 'tools' => $allToolResults];
            }

            $data = $result['data'];

            if ($data['type'] === 'text') {
                $sessionId = md5($userMessage . time());
                $this->logAgentSession($sessionId, $userMessage, $allToolResults, $data['content'], $userId);
                return [
                    'success' => true,
                    'data' => ['content' => $data['content'], 'tools' => $allToolResults, 'session_id' => $sessionId],
                    'error' => '',
                ];
            }

            // tool_call → 执行工具
            $messages = $data['messages'];
            $messages[] = ['role' => 'assistant', 'tool_calls' => $data['tool_calls']];
            foreach ($data['tool_calls'] as $tc) {
                $funcName = $tc['function']['name'];
                $funcArgs = json_decode($tc['function']['arguments'], true) ?: [];
                $toolResult = $this->executeTool($funcName, $funcArgs, $storeId, $userId);
                $allToolResults[] = ['name' => $funcName, 'args' => $funcArgs, 'result' => $toolResult];
                $messages[] = [
                    'role' => 'tool',
                    'tool_call_id' => $tc['id'],
                    'content' => json_encode($toolResult, JSON_UNESCAPED_UNICODE),
                ];
            }
        }

        return ['success' => false, 'data' => null, 'error' => '对话轮次超限，请简化问题重试', 'tools' => $allToolResults];
    }

    /**
     * 流式多轮对话（回调驱动，供 SSE 使用）
     *
     * 与 chatWithContext() 的区别：
     *  - LLM 调用改为流式（AiGateway::functionCallStream）
     *  - 每步执行时通过 $emit 推 SSE 事件
     *  - tool_call 的 arguments 在流式模式下需拼装
     *
     * @param callable $emit function(array $event): void
     */
    public function chatStream(string $systemPrompt, array $history, string $userMessage, int $userId, int $storeId, callable $emit): array
    {
        $tools = $this->getTools();
        $allToolResults = [];

        $emit(['type' => 'status', 'text' => '正在分析…']);

        $messages = [['role' => 'system', 'content' => $systemPrompt]];
        foreach ($history as $h) {
            $messages[] = ['role' => $h['role'], 'content' => $h['content']];
        }
        $messages[] = ['role' => 'user', 'content' => $userMessage];

        $maxRounds = 8;
        for ($round = 0; $round < $maxRounds; $round++) {
            // —— 流式调 LLM ——
            $textBuffer = '';
            $toolCallFragments = [];   // ['call_xxx' => ['id'=>'...', 'name'=>'...', 'args'=>'...']]
            $currentToolId = null;
            $hasError = false;

            try {
                AiService::functionCallStream($messages, $tools, function ($chunk) use ($emit, &$textBuffer, &$toolCallFragments, &$currentToolId) {
                    switch ($chunk['type']) {
                        case 'text_chunk':
                            $textBuffer .= $chunk['content'];
                            $emit(['type' => 'text', 'content' => $chunk['content']]);
                            break;

                        case 'tool_call_start':
                            $currentToolId = $chunk['id'];
                            $toolCallFragments[$currentToolId] = [
                                'id'   => $chunk['id'],
                                'name' => $chunk['name'],
                                'args' => '',
                            ];
                            $emit(['type' => 'tool_call', 'name' => $chunk['name'], 'status' => 'calling']);
                            break;

                        case 'tool_call_args':
                            if ($currentToolId && isset($toolCallFragments[$currentToolId])) {
                                $toolCallFragments[$currentToolId]['args'] .= $chunk['fragment'];
                            }
                            break;

                        case 'tool_calls_finished':
                            // 组装为标准 DeepSeek tool_calls 格式
                            $assembled = [];
                            foreach ($toolCallFragments as $tc) {
                                $assembled[] = [
                                    'id'       => $tc['id'],
                                    'type'     => 'function',
                                    'function' => [
                                        'name'      => $tc['name'],
                                        'arguments' => $tc['args'],
                                    ],
                                ];
                            }
                            $toolCallFragments = $assembled;
                            break;
                    }
                });
            } catch (\Throwable $e) {
                $emit(['type' => 'error', 'message' => 'AI 调用失败: ' . $e->getMessage()]);
                $hasError = true;
            }

            if ($hasError) {
                return ['content' => '', 'tools' => $allToolResults];
            }

            // —— 判断本轮结果 ——
            if (!empty($toolCallFragments) && isset($toolCallFragments[0]['function'])) {
                // 有 tool_call → 执行工具
                $messages[] = ['role' => 'assistant', 'tool_calls' => $toolCallFragments];

                foreach ($toolCallFragments as $tc) {
                    $funcName = $tc['function']['name'];
                    $funcArgs = json_decode($tc['function']['arguments'], true) ?: [];

                    $emit(['type' => 'tool_call', 'name' => $funcName, 'args' => $funcArgs, 'status' => 'executing']);

                    try {
                        $toolResult = $this->executeTool($funcName, $funcArgs, $storeId, $userId);
                        $emit(['type' => 'tool_result', 'name' => $funcName, 'summary' => $this->summarizeResult($funcName, $toolResult)]);
                    } catch (\Throwable $e) {
                        $toolResult = ['error' => $e->getMessage()];
                        $emit(['type' => 'tool_result', 'name' => $funcName, 'summary' => '执行失败', 'error' => $e->getMessage()]);
                    }

                    $allToolResults[] = ['name' => $funcName, 'result' => $toolResult];
                    $messages[] = [
                        'role'         => 'tool',
                        'tool_call_id' => $tc['id'],
                        'content'      => json_encode($toolResult, JSON_UNESCAPED_UNICODE),
                    ];
                }
            } elseif (!empty($textBuffer)) {
                // 文本响应 → 结束
                $emit(['type' => 'done']);
                return ['content' => $textBuffer, 'tools' => $allToolResults];
            } else {
                // 空响应（罕见）
                $emit(['type' => 'done']);
                return ['content' => '', 'tools' => $allToolResults];
            }
        }

        $emit(['type' => 'done']);
        return ['content' => '', 'tools' => $allToolResults, 'error' => '对话轮次超限'];
    }

    /**
     * 汇总工具结果（减少 SSE 传输量，只给前端显示摘要）
     */
    private function summarizeResult(string $toolName, array $result): string
    {
        if (isset($result['error'])) {
            return '执行失败: ' . $result['error'];
        }

        switch ($toolName) {
            case 'searchCustomer':
            case 'search_customers':
                $count = $result['count'] ?? count($result['customers'] ?? []);
                return "找到 {$count} 个客户";
            case 'lookup_company':
                return ($result['data']['customer_name'] ?? '') ?: '企业信息已获取';
            case 'getCustomerDetail':
            case 'get_customer':
                return ($result['customer_name'] ?? '') ?: '客户详情已加载';
            case 'getOrderList':
            case 'get_orders':
                $count = $result['count'] ?? count($result['orders'] ?? []);
                return "{$count} 笔订单";
            case 'getQuotationList':
            case 'get_quotations':
                $count = $result['count'] ?? count($result['quotations'] ?? []);
                return "{$count} 笔报价";
            case 'getFollowupHistory':
            case 'get_followups':
                $count = $result['count'] ?? count($result['followups'] ?? []);
                return "{$count} 条跟进记录";
            case 'getDashboard':
            case 'get_dashboard':
                return '仪表盘数据已加载';
            case 'getMyStats':
            case 'get_my_stats':
                return '业绩数据已加载';
            case 'searchProduct':
            case 'search_products':
                $count = $result['count'] ?? count($result['products'] ?? []);
                return "找到 {$count} 个产品";
            case 'searchKnowledge':
            case 'search_knowledge':
                return '知识库已搜索';
            case 'searchLeads':
            case 'search_leads':
                $count = $result['count'] ?? 0;
                return "找到 {$count} 条线索";
            default:
                // 技能包工具
                if (strpos($toolName, 'skill_') === 0) {
                    return '执行完成';
                }
                return '执行完成';
        }
    }

    /** 执行工具调用 */
    protected function executeTool(string $name, array $args, int $storeId, int $userId): array
    {
        switch ($name) {
            case 'searchCustomer':
            case 'search_customers':
                $kw = $args['keyword'] ?? '';
                $rows = Db::name('crm_customer')
                    ->where('store_id', $storeId)->where('is_delete', 0)
                    ->where(function ($q) use ($kw) {
                        $q->where('customer_name', 'like', "%{$kw}%")
                          ->whereOr('industry', 'like', "%{$kw}%")
                          ->whereOr('short_name', 'like', "%{$kw}%");
                    })
                    ->field('id,customer_name,industry,level_name,funnel_stage,last_followup_time')
                    ->limit(10)->select()->toArray();
                return ['count' => count($rows), 'customers' => $rows];

            case 'getCustomerDetail':
            case 'get_customer':
                $cid = $args['customer_id'] ?? 0;
                $c = Db::name('crm_customer')->where('id', $cid)->where('store_id', $storeId)->find();
                if (!$c) return ['error' => '客户不存在'];
                unset($c['is_delete'], $c['store_id']);
                $c['order_count'] = Db::name('crm_order')->where('customer_id', $cid)->where('is_delete', 0)->count();
                $c['quotation_count'] = Db::name('crm_quotation')->where('customer_id', $cid)->where('is_delete', 0)->count();
                $c['followup_count'] = Db::name('crm_followup')->where('customer_id', $cid)->where('is_delete', 0)->count();
                $lastFollowup = Db::name('crm_followup')->where('customer_id', $cid)->where('is_delete', 0)
                    ->order('follow_date', 'desc')->find();
                $c['last_followup'] = $lastFollowup ? date('Y-m-d', $lastFollowup['follow_date']) . ' ' . $lastFollowup['follow_type'] : '无';
                return $c;

            case 'getOrderList':
            case 'get_orders':
                $cid = $args['customer_id'] ?? 0;
                $rows = Db::name('crm_order')
                    ->where('customer_id', $cid)->where('is_delete', 0)->where('store_id', $storeId)
                    ->field('id,order_no,final_amount,status,payment_status,create_time')
                    ->order('id', 'desc')->limit(10)->select()->toArray();
                $statusMap = [1=>'待确认',2=>'生产中',3=>'待发货',4=>'已发货',5=>'已完成',6=>'已取消'];
                foreach ($rows as &$r) {
                    $r['status_name'] = $statusMap[$r['status']] ?? '未知';
                    $r['date'] = date('Y-m-d', $r['create_time']);
                }
                return ['count' => count($rows), 'orders' => $rows];

            case 'getQuotationList':
            case 'get_quotations':
                $cid = $args['customer_id'] ?? 0;
                $rows = Db::name('crm_quotation')
                    ->where('customer_id', $cid)->where('is_delete', 0)->where('store_id', $storeId)
                    ->field('id,quotation_no,final_amount,status,create_time')
                    ->order('id', 'desc')->limit(10)->select()->toArray();
                $sm = [1=>'草稿',2=>'已发送',3=>'已确认',4=>'已拒绝',5=>'已转订单'];
                foreach ($rows as &$r) {
                    $r['status_name'] = $sm[$r['status']] ?? '未知';
                    $r['date'] = date('Y-m-d', $r['create_time']);
                }
                return ['count' => count($rows), 'quotations' => $rows];

            case 'getFollowupHistory':
            case 'get_followups':
                $cid = $args['customer_id'] ?? 0;
                $rows = Db::name('crm_followup')
                    ->where('customer_id', $cid)->where('is_delete', 0)->where('store_id', $storeId)
                    ->field('follow_type,follow_content,result,follow_date')
                    ->order('follow_date', 'desc')->limit(10)->select()->toArray();
                foreach ($rows as &$r) {
                    $r['date'] = date('Y-m-d', $r['follow_date']);
                    $r['follow_content'] = strip_tags($r['follow_content']);
                }
                return ['count' => count($rows), 'followups' => $rows];

            case 'getMyStats':
            case 'get_my_stats':
                $month = date('Y-m');
                // 我的客户 = 我是销售 ∪ 我是客服
                $custCount = Db::name('crm_customer')->where('store_id', $storeId)->where('is_delete', 0)
                    ->where(function ($q) use ($userId) {
                        $q->where('owner_user_id', $userId)->whereOr('cs_user_id', $userId);
                    })->count();
                // 业绩口径 = 回款确认的业绩记录
                $commissionAmt = Db::name('crm_commission_record')->where('store_id', $storeId)
                    ->where('user_id', $userId)->where('confirm_month', $month)->sum('amount');
                return ['customer_count' => $custCount, 'month_amount' => floatval($commissionAmt), 'month' => $month];

            case 'getDashboard':
            case 'get_dashboard':
                $mStart = strtotime(date('Y-m-01'));
                $newCount = Db::name('crm_customer')->where('store_id', $storeId)->where('is_delete', 0)
                    ->where('create_time', '>=', $mStart)->count();
                $newNames = Db::name('crm_customer')->where('store_id', $storeId)->where('is_delete', 0)
                    ->where('create_time', '>=', $mStart)->order(['create_time' => 'desc'])->limit(10)
                    ->column('customer_name');
                $mQuotation = Db::name('crm_quotation')->where('store_id', $storeId)
                    ->where('create_time', '>=', $mStart)->sum('final_amount');
                $mOrder = Db::name('crm_order')->where('store_id', $storeId)
                    ->where('create_time', '>=', $mStart)->sum('final_amount');
                $total = Db::name('crm_customer')->where('store_id', $storeId)->where('is_delete', 0)->where('status', 1)->count();
                $needFollow = Db::name('crm_customer')->where('store_id', $storeId)->where('is_delete', 0)
                    ->where(function ($q) { $q->where('last_followup_time', '<', time() - 7 * 86400)->whereOr('last_followup_time', null); })->count();
                $needNames = Db::name('crm_customer')->where('store_id', $storeId)->where('is_delete', 0)
                    ->where(function ($q) { $q->where('last_followup_time', '<', time() - 7 * 86400)->whereOr('last_followup_time', null); })
                    ->order(['last_followup_time' => 'asc'])->limit(8)->column('customer_name');
                // 我的数据
                $myTotal = Db::name('crm_customer')->where('store_id', $storeId)->where('is_delete', 0)
                    ->where('owner_user_id', $userId)->count();
                $myNeed = Db::name('crm_customer')->where('store_id', $storeId)->where('is_delete', 0)
                    ->where('owner_user_id', $userId)
                    ->where(function ($q) { $q->where('last_followup_time', '<', time() - 7 * 86400)->whereOr('last_followup_time', null); })->count();
                return [
                    'total_customers' => $total,
                    'need_followup_count' => $needFollow,
                    'need_followup_names' => $needNames,
                    'month_new_count' => $newCount,
                    'month_new_names' => $newNames,
                    'month_quotation' => floatval($mQuotation),
                    'month_order' => floatval($mOrder),
                    'my_total' => $myTotal,
                    'my_need_followup' => $myNeed,
                ];

            case 'searchProduct':
            case 'search_products':
                $kw = $args['keyword'] ?? '';
                $rows = Db::name('crm_product')
                    ->where('store_id', $storeId)->where('is_delete', 0)->where('status', 1)
                    ->where(function ($q) use ($kw) {
                        if ($kw) {
                            $q->where('product_name', 'like', "%{$kw}%")
                              ->whereOr('category', 'like', "%{$kw}%");
                        }
                    })
                    ->field('id,product_name,specification,unit,reference_price,category')
                    ->limit($kw ? 10 : 20)->select()->toArray();
                return ['count' => count($rows), 'products' => $rows];

            case 'searchKnowledge':
            case 'search_knowledge':
                $kw = $args['keyword'] ?? '';
                $rows = Db::name('crm_knowledge')
                    ->where('store_id', $storeId)->where('is_delete', 0)
                    ->where(function ($q) use ($kw) {
                        if ($kw) {
                            $q->where('title', 'like', "%{$kw}%")
                              ->whereOr('tags', 'like', "%{$kw}%");
                        }
                    })
                    ->field('id,title,category,tags,LEFT(content,200) as summary')
                    ->order('id', 'desc')
                    ->limit($kw ? 10 : 15)->select()->toArray();
                return ['count' => count($rows), 'items' => $rows];

            case 'searchLeads':
            case 'search_leads':
                $kw = $args['keyword'] ?? '';
                $rows = Db::name('crm_lead')
                    ->where('store_id', $storeId)->where('is_delete', 0)
                    ->where(function ($q) use ($kw) {
                        if ($kw) {
                            $q->where('lead_name', 'like', "%{$kw}%")
                              ->whereOr('company_name', 'like', "%{$kw}%");
                        }
                    })
                    ->field('id,lead_name,company_name,status,source,priority,create_time')
                    ->order('id', 'desc')
                    ->limit($kw ? 20 : 20)->select()->toArray();
                $stMap = [1=>'跟进中',2=>'已转化',3=>'已放弃'];
                foreach ($rows as &$r) {
                    $r['status_name'] = $stMap[$r['status']] ?? '未知';
                    $r['date'] = date('Y-m-d', $r['create_time']);
                }
                return ['count' => count($rows), 'leads' => $rows];

            // ── 公司信息解析 ──
            case 'parseCompany':
            case 'parse_company':
                $companyName = $args['company_name'] ?? $args['keyword'] ?? '';
                if (!$companyName) return ['error' => '请输入公司名称'];
                // 先查已有客户库
                $existing = Db::name('crm_customer')
                    ->where('store_id', $storeId)->where('is_delete', 0)
                    ->where('customer_name', 'like', "%{$companyName}%")
                    ->field('customer_name,short_name,industry,province,city,area,address,telephone,email,website,tax_number,legal_representative,registered_capital,established_date,business_scope')
                    ->find();
                if ($existing) {
                    return ['success' => true, 'source' => 'crm', 'data' => $existing];
                }
                // AI 传入的字段直接返回（用于填表）
                $fields = ['customer_name','short_name','industry','province','city','area','address',
                    'telephone','email','website','tax_number','legal_representative',
                    'registered_capital','established_date','business_scope','introduction'];
                $data = ['customer_name' => $companyName];
                foreach ($fields as $f) {
                    if (!empty($args[$f])) $data[$f] = $args[$f];
                }
                return ['success' => true, 'source' => 'ai', 'data' => $data];

            // ── 企业工商查询（entquery skill） ──
            case 'lookup_company':
                $keyword = trim((string)($args['keyword'] ?? ($args['name'] ?? '')));
                if ($keyword === '') return ['error' => '请输入公司名称或统一社会信用代码'];
                $detail = (new \skills\entquery\service\CompanyQuery($storeId, $userId))
                    ->detail($keyword, !empty($args['force']));
                if (empty($detail['success'])) return ['error' => $detail['error'] ?? '查询失败'];
                $c = $detail['company'] ?: [];
                return [
                    'success'   => true,
                    'cache_hit' => !empty($detail['cache_hit']),
                    'stale'     => !empty($detail['stale']),
                    'data'      => [
                        'customer_name'        => $c['name'] ?? '',
                        'tax_number'           => $c['credit_code'] ?? '',
                        'legal_representative' => $c['legal_representative'] ?? '',
                        'registered_capital'   => $c['reg_capital'] ?? '',
                        'paid_in_capital'      => $c['actual_capital'] ?? '',
                        'established_date'     => $c['establish_date'] ?? '',
                        'registration_status'  => $c['registration_status'] ?? '',
                        'national_industry'    => $c['national_industry'] ?? '',
                        'registered_address'   => $c['registered_address'] ?? '',
                        'business_scope'       => $c['business_scope'] ?? '',
                        'telephone'            => $c['phone'] ?? '',
                        'province'             => $c['province'] ?? '',
                        'city'                 => $c['city'] ?? '',
                        'area'                 => $c['area'] ?? '',
                    ],
                ];

            case 'fetch_webpage':
            case 'fetch_webpage':
                $url = $args['url'] ?? '';
                if (!$url || !preg_match('/^https?:\/\/.+/', $url)) return ['error' => '请输入有效的网址'];
                try {
                    $ctx = stream_context_create(['http' => ['timeout' => 10, 'user_agent' => 'Mozilla/5.0']]);
                    $html = @file_get_contents($url, false, $ctx);
                    if (!$html) return ['error' => '无法访问该网址'];
                    $title = ''; $desc = '';
                    if (preg_match('/<title>([^<]+)<\/title>/i', $html, $m)) $title = trim($m[1]);
                    if (preg_match('/<meta[^>]+name="description"[^>]+content="([^"]+)"/i', $html, $m)) $desc = $m[1];
                    $text = strip_tags($html);
                    $text = preg_replace('/\s+/', ' ', $text);
                    return ['success' => true, 'data' => [
                        'url' => $url, 'title' => $title, 'description' => $desc,
                        'content_preview' => trim(mb_substr($text, 0, 4000)),
                    ]];
                } catch (\Throwable $e) {
                    return ['error' => '抓取失败: ' . $e->getMessage()];
                }

            // ── 日历查询（带事件展开，与日历页面数据一致）──
            case 'calendar':
            case 'get_calendar':
                $start = $args['start'] ?? date('Y-m-01');
                $end   = $args['end'] ?? date('Y-m-t');
                $cid   = (int)($args['customer_id'] ?? 0);
                $result = \mpp\crm\service\FollowUpService::calendarEvents($storeId, $start, $end, 0, $cid);

                // 追加需求引擎事件（日历上也显示，但从 listAll API 加载）
                $demands = Db::name('crm_demand')->alias('d')
                    ->leftJoin('yoshop_crm_customer c', 'c.id = d.customer_id')
                    ->leftJoin('yoshop_crm_demand_rule r', 'r.rule_key = d.rule_key AND r.store_id = ' . $storeId)
                    ->where('d.store_id', $storeId)->where('d.status', 1)
                    ->field('d.id,d.customer_id,d.reason,r.rule_name,c.customer_name')
                    ->select()->toArray();
                $today = date('Y-m-d');
                foreach ($demands as $dm) {
                    $result['list'][] = [
                        'id' => 'dm' . $dm['id'],
                        'customer_id' => (int)($dm['customer_id'] ?? 0),
                        'customer_name' => $dm['customer_name'] ?? '',
                        'follow_type' => $dm['rule_name'] ?? '需求',
                        'next_follow_content' => $dm['reason'] ?? '',
                        'next_follow_date' => strtotime($today),
                        'event_type' => 'demand',
                        'is_lunar' => 0,
                    ];
                }
                $result['total'] = count($result['list']);

                return [
                    'total' => $result['total'],
                    'events' => array_map(function($e) {
                        return [
                            'date' => date('Y-m-d', $e['next_follow_date'] ?? 0),
                            'type' => $e['event_type'] ?? 'visit',
                            'title' => $e['follow_type'] ?? '',
                            'content' => $e['next_follow_content'] ?? '',
                            'customer_id' => $e['customer_id'] ?? 0,
                            'customer_name' => $e['customer_name'] ?? '',
                            'is_lunar' => $e['is_lunar'] ?? 0,
                        ];
                    }, $result['list']),
                    'start' => $start, 'end' => $end,
                ];

            // ── 通用数据查询 ──
            case 'query':
                $table = $args['table'] ?? '';
                if (!$table) return ['error' => '缺少 table 参数'];

                // 安全层1：表白名单
                $allowed = [
                    'crm_customer','crm_contact','crm_followup','crm_lead',
                    'crm_quotation','crm_quotation_item','crm_order','crm_order_item',
                    'crm_contract','crm_product','crm_knowledge','crm_calendar_event',
                    'crm_ai_conversation','crm_demand','crm_demand_rule',
                ];
                if (!in_array($table, $allowed)) return ['error' => "表不在允许范围: {$table}"];

                // 安全层2：字段白名单（禁止查敏感字段）
                $allowedFields = [
                    'crm_customer' => ['id','customer_name','customer_code','short_name','industry','level_name',
                        'source','customer_group','tax_number','province','city','area','address','telephone','email',
                        'website','legal_representative','registered_capital','established_date','business_scope',
                        'introduction','status','owner_user_id','funnel_stage','last_followup_time','create_time'],
                    'crm_calendar_event' => ['id','title','event_date','is_lunar','repeat_type','color',
                        'content','customer_id','create_time'],
                    'crm_followup' => ['id','customer_id','follow_type','follow_content','result',
                        'follow_date','next_follow_date','next_follow_content','owner_user_id','create_time'],
                    'crm_quotation' => ['id','customer_id','quotation_no','final_amount','status','create_time'],
                    'crm_order' => ['id','customer_id','order_no','final_amount','status','payment_status','create_time'],
                    'crm_contract' => ['id','customer_id','contract_no','contract_amount','status','sign_date','create_time'],
                    'crm_product' => ['id','product_name','specification','unit','reference_price','category'],
                    'crm_lead' => ['id','lead_name','company_name','contact_person','contact_phone','source','status','create_time'],
                    'crm_knowledge' => ['id','title','category','tags','LEFT(content,200) as summary','create_time'],
                    'crm_contact' => ['id','contact_name','mobile','telephone','email','wechat','position','create_time'],
                ];
                $fields = $allowedFields[$table] ?? ['id'];
                $fieldStr = implode(',', $fields);

                // 安全层3：禁止覆盖 store_id 和 is_delete
                unset($args['filters']['store_id'], $args['filters']['is_delete'],
                      $args['where']['store_id'], $args['where']['is_delete']);

                $query = Db::name($table)->where('store_id', $storeId);
                // 排除已删除（日历事件无 is_delete 字段则跳过）
                if (in_array($table, ['crm_customer','crm_contact','crm_followup','crm_lead',
                    'crm_quotation','crm_order','crm_contract','crm_product','crm_knowledge','crm_calendar_event'])) {
                    $query->where('is_delete', 0);
                }
                $query->field($fieldStr);

                // 过滤条件
                $filters = $args['filters'] ?? $args['where'] ?? [];
                if (is_array($filters)) {
                    foreach ($filters as $k => $v) {
                        if ($k === 'store_id' || $k === 'is_delete') continue;
                        if (is_string($v) && strpos($v, '%') !== false) {
                            $query->where($k, 'like', $v);
                        } elseif (is_array($v)) {
                            $query->whereIn($k, $v);
                        } else {
                            $query->where($k, $v);
                        }
                    }
                }
                // 排序
                $order = $args['order'] ?? 'id desc';
                $query->order($order);
                // 限制
                $limit = min((int)($args['limit'] ?? 20), 50);
                $rows = $query->limit($limit)->select()->toArray();

                // 安全层4：审计日志
                try {
                    Db::name('crm_ai_tool_log')->insert([
                        'store_id' => $storeId, 'user_id' => $userId,
                        'tool_name' => 'query', 'args' => json_encode(['table' => $table, 'filters' => $filters, 'limit' => $limit], JSON_UNESCAPED_UNICODE),
                        'success' => 1, 'create_time' => time(),
                    ]);
                } catch (\Throwable $e) {}

                return ['count' => count($rows), 'rows' => $rows, 'table' => $table];

            // ── Write 工具 ──
            case 'addFollowup':
            case 'add_followup':
                $cid = (int)($args['customer_id'] ?? 0);
                if (!$cid) return ['error' => '缺少 customer_id'];
                Db::name('crm_followup')->insert([
                    'store_id'        => $storeId,
                    'customer_id'     => $cid,
                    'follow_type'     => $args['follow_type'] ?? '拜访',
                    'follow_content'  => $args['follow_content'] ?? '',
                    'result'          => $args['result'] ?? '',
                    'follow_date'     => strtotime($args['follow_date'] ?? date('Y-m-d')),
                    'owner_user_id'  => $userId,
                    'create_time'    => time(),
                ]);
                Db::name('crm_customer')->where('id', $cid)->where('store_id', $storeId)
                    ->update(['last_followup_time' => time()]);
                return ['success' => true, 'message' => '跟进记录已添加'];

            case 'createContact':
            case 'create_contact':
                $customerId = (int)($args['customer_id'] ?? 0);
                $contactName = $args['contact_name'] ?? '';
                // mobile 为空用 NULL（唯一索引对 NULL 不生效）
                $mobile = ($args['mobile'] ?? '') ?: null;
                // 手机号已存在则绑定现有联系人到当前客户
                if ($mobile) {
                    $exist = Db::name('crm_contact')
                        ->where('store_id', $storeId)
                        ->where('mobile', $mobile)
                        ->where('is_delete', 0)
                        ->find();
                    if ($exist) {
                        // 绑定到客户
                        if ($customerId > 0) {
                            $bound = Db::name('crm_contact_customer')
                                ->where('store_id', $storeId)
                                ->where('contact_id', $exist['id'])
                                ->where('customer_id', $customerId)
                                ->find();
                            if (!$bound) {
                                Db::name('crm_contact_customer')->insert([
                                    'store_id' => $storeId, 'contact_id' => $exist['id'],
                                    'customer_id' => $customerId, 'is_primary' => 0, 'create_time' => time(),
                                ]);
                            }
                        }
                        return ['success' => true, 'contact_id' => $exist['id'], 'message' => '该手机号联系人已存在，已绑定到当前客户'];
                    }
                }
                $id = Db::name('crm_contact')->insertGetId([
                    'store_id'     => $storeId,
                    'contact_name' => $contactName,
                    'mobile'       => $mobile,
                    'telephone'    => $args['telephone'] ?? '',
                    'email'        => $args['email'] ?? '',
                    'wechat'       => $args['wechat'] ?? '',
                    'create_time'  => time(),
                ]);
                // 新联系人绑定到客户
                if ($customerId > 0 && $id) {
                    Db::name('crm_contact_customer')->insert([
                        'store_id' => $storeId, 'contact_id' => $id,
                        'customer_id' => $customerId, 'is_primary' => 0, 'create_time' => time(),
                    ]);
                }
                return ['success' => true, 'contact_id' => $id, 'message' => '联系人已创建并绑定客户'];

            case 'createLead':
            case 'create_lead':
                Db::name('crm_lead')->insert([
                    'store_id'       => $storeId,
                    'lead_name'      => $args['lead_name'] ?? '',
                    'company_name'   => $args['company_name'] ?? '',
                    'contact_person' => $args['contact_person'] ?? '',
                    'contact_phone'  => $args['contact_phone'] ?? '',
                    'source'         => $args['source'] ?? '',
                    'status'         => 1,
                    'owner_user_id'  => $userId,
                    'create_time'    => time(),
                ]);
                return ['success' => true, 'message' => '线索已创建'];

            case 'addCustomerNote':
            case 'add_customer_note':
                $cid  = (int)($args['customer_id'] ?? 0);
                $note = $args['note'] ?? '';
                if (!$cid || !$note) return ['error' => '缺少 customer_id 或 note'];
                $old = Db::name('crm_customer')->where('id', $cid)->where('store_id', $storeId)->value('remark');
                Db::name('crm_customer')->where('id', $cid)->where('store_id', $storeId)
                    ->update(['remark' => ($old ? $old . "\n" : '') . '[' . date('Y-m-d H:i') . ' AI] ' . $note]);
                return ['success' => true, 'message' => '备注已追加'];

            case 'createCustomer':
            case 'create_customer':
                // 用模型创建，自动生成唯一 customer_code + 去重校验
                $customerModel = new \mpp\crm\model\Customer();
                \mpp\crm\model\Customer::$storeId = $storeId;
                $data = [
                    'customer_name'   => $args['customer_name'] ?? '',
                    'industry'        => $args['industry'] ?? '',
                    'telephone'       => $args['telephone'] ?? '',
                    'short_name'      => $args['short_name'] ?? '',
                    'source'          => $args['source'] ?? '',
                    'province'        => $args['province'] ?? '',
                    'city'            => $args['city'] ?? '',
                    'address'         => $args['address'] ?? '',
                    'tax_number'      => $args['tax_number'] ?? '',
                    'status'          => 1,
                    'owner_user_id'   => $userId,
                    'creator_user_id' => $userId,
                    'funnel_stage'    => 1,
                    'last_followup_time' => 0,
                ];
                if ($customerModel->add($data)) {
                    return ['success' => true, 'customer_id' => $customerModel->id, 'message' => '客户已创建'];
                }
                return ['error' => $customerModel->getError() ?: '创建客户失败'];
                return ['success' => true, 'customer_id' => $id, 'message' => '客户已创建'];

            case 'updateCustomer':
            case 'update_customer':
                $cid  = (int)($args['id'] ?? 0);
                $data = [];
                foreach (['industry','telephone','address','email','website','short_name'] as $f) {
                    if (isset($args[$f])) $data[$f] = $args[$f];
                }
                if (!$cid || empty($data)) return ['error' => '缺少 id 或更新字段'];
                $data['update_time'] = time();
                Db::name('crm_customer')->where('id', $cid)->where('store_id', $storeId)->update($data);
                return ['success' => true, 'message' => '客户信息已更新'];

            default:
                if (strpos($name, 'skill_') === 0) {
                    $rest = substr($name, 6);
                    foreach (\cores\SkillLoader::getSkills() as $sn => $info) {
                        $prefix = $sn . '_';
                        if (strpos($rest, $prefix) === 0) {
                            $toolName = substr($rest, strlen($prefix));
                            return SkillPipeline::executeTool($sn, $toolName, $args);
                        }
                    }
                    return ['error' => "技能工具未找到: {$name}"];
                }
                return ['error' => "未知工具: {$name}"];
        }
    }

    protected function logAgentSession(string $sessionId, string $intent, array $tools, string $result, int $userId): void
    {
        try {
            $log = new \mpp\crm\model\AgentLog();
            $log->session_id = $sessionId;
            $log->intent = mb_substr($intent, 0, 200);
            $log->tools_called = json_encode($tools, JSON_UNESCAPED_UNICODE);
            $log->result = json_encode(['content' => $result], JSON_UNESCAPED_UNICODE);
            $log->status = 1;
            $log->user_id = $userId;
            $log->create_time = time();
            $log->store_id = $this->storeId;
            $log->save();
        } catch (\Throwable $e) {
            // 日志记录失败不影响主流程
        }
    }

    // ——— 以下来自 content-engine skill，管理 AI Agent 预设 ———

    /** 预设列表 */
    public function listAll(int $storeId): array
    {
        $presets = PresetLoader::getAgents();
        $custom = Db::name('crm_content_agent')
            ->where('store_id', $storeId)
            ->order('id', 'asc')->select()->toArray();
        foreach ($custom as &$c) {
            if (is_string($c['allowed_vars'] ?? '')) {
                $c['allowed_vars'] = json_decode($c['allowed_vars'], true) ?: [];
            }
        }
        return ['success' => true, 'data' => ['presets' => $presets, 'custom' => $custom], 'error' => ''];
    }

    /** 从预设复制 */
    public function copyFromPreset(string $presetKey, int $storeId): array
    {
        $preset = PresetLoader::getAgent($presetKey);
        if (!$preset) return ['success' => false, 'data' => null, 'error' => '预设不存在'];
        $id = Db::name('crm_content_agent')->insertGetId([
            'store_id' => $storeId, 'name' => $preset['name'] . '(我的)',
            'agent_type' => $preset['type'] ?? '', 'preset_style' => $preset['preset_style'] ?? '',
            'allowed_vars' => json_encode($preset['allowed_vars'] ?? []),
            'instructions' => $preset['instructions'] ?? '', 'status' => 1,
            'create_time' => time(), 'update_time' => time(),
        ]);
        $row = Db::name('crm_content_agent')->where('id', $id)->find();
        return ['success' => true, 'data' => $row, 'error' => ''];
    }

    /** 新增自定义 Agent */
    public function add(array $data, int $storeId): array
    {
        $data['store_id'] = $storeId;
        if (empty($data['name'])) return ['success' => false, 'data' => null, 'error' => '名称不能为空'];
        if (is_array($data['allowed_vars'] ?? null)) $data['allowed_vars'] = json_encode($data['allowed_vars']);
        $data['create_time'] = time(); $data['update_time'] = time();
        $id = Db::name('crm_content_agent')->insertGetId($data);
        $row = Db::name('crm_content_agent')->where('id', $id)->find();
        return ['success' => true, 'data' => $row, 'error' => ''];
    }

    /** 编辑自定义 Agent */
    public function edit(array $data, int $storeId): array
    {
        if (is_array($data['allowed_vars'] ?? null)) $data['allowed_vars'] = json_encode($data['allowed_vars']);
        Db::name('crm_content_agent')->where('id', $data['id'])->where('store_id', $storeId)
            ->update(array_merge($data, ['update_time' => time()]));
        return ['success' => true, 'data' => [], 'error' => ''];
    }

    /** 删除自定义 Agent */
    public function delete(int $id, int $storeId): array
    {
        Db::name('crm_content_agent')->where('id', $id)->where('store_id', $storeId)->delete();
        return ['success' => true, 'data' => [], 'error' => ''];
    }
}
