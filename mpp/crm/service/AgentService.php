<?php
namespace mpp\crm\service;

use app\platform\BaseService;
use app\platform\ai\AiGateway as AiService;
use cores\SkillPipeline;
use skills\contentengine\service\PresetLoader;
use think\facade\Db;

class AgentService extends BaseService
{
    /** 工具注册表（含技能包工具） */
    protected function getTools(): array
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

    /** 执行工具调用 */
    protected function executeTool(string $name, array $args, int $storeId, int $userId): array
    {
        switch ($name) {
            case 'searchCustomer':
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
                $mStart = strtotime(date('Y-m-01'));
                $custCount = Db::name('crm_customer')->where('owner_user_id', $userId)->where('is_delete', 0)->count();
                $orderAmt = Db::name('crm_order')->where('owner_user_id', $userId)->where('is_delete', 0)
                    ->where('create_time', '>=', $mStart)->sum('final_amount');
                return ['customer_count' => $custCount, 'month_amount' => floatval($orderAmt), 'month' => date('Y-m')];

            case 'getDashboard':
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
                $kw = $args['keyword'] ?? '';
                $rows = Db::name('crm_product')
                    ->where('store_id', $storeId)->where('is_delete', 0)->where('status', 1)
                    ->where(function ($q) use ($kw) {
                        $q->where('product_name', 'like', "%{$kw}%")
                          ->whereOr('category', 'like', "%{$kw}%");
                    })
                    ->field('id,product_name,specification,unit,reference_price,category')
                    ->limit(10)->select()->toArray();
                return ['count' => count($rows), 'products' => $rows];

            default:
                // 技能包工具：格式 skill_{技能名}_{工具名}
                if (strpos($name, 'skill_') === 0) {
                    $rest = substr($name, 6); // remove 'skill_' prefix
                    foreach (\cores\SkillLoader::getSkills() as $sn => $info) {
                        $prefix = $sn . '_';
                        if (strpos($rest, $prefix) === 0) {
                            $toolName = substr($rest, strlen($prefix));
                            $result = SkillPipeline::executeTool($sn, $toolName, $args);
                            return $result;
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
