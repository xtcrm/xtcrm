<?php

declare (strict_types=1);

namespace mpp\crm\service;

use think\facade\Db;

/**
 * AI 工具注册表 —— 统一管理所有 AI 可调用能力
 *
 * 职责：
 *  - 定义工具本身（名称、参数、风险等级、是否异步）
 *  - 权限过滤（按用户角色过滤可用工具）
 *  - 不在注册表中管理"哪个角色用哪个工具"——那是 crm_ai_role 表的职责
 *
 * 模式：一处定义，三处使用：
 *   1. AI 侧边面板（浏览器 → PHP → DeepSeek）
 *   2. MCP Server（Claude Code → Node → PHP API）
 *   3. 移动端（未来）
 */
class AiToolRegistry
{
    /** @var array<string, array> 缓存 */
    private static $tools = [];

    /** 角色权限级别 */
    const ROLE_ADMIN   = 'admin';
    const ROLE_MANAGER = 'manager';
    const ROLE_SALES   = 'sales';

    /**
     * 获取当前用户在某角色下可用的工具列表（已过滤权限+角色白名单）
     *
     * @param int    $userId
     * @param int    $storeId
     * @param string $roleKey  角色标识（sales/creator/coach）
     * @return array  [{name, description, parameters, risk, isAsync}]
     */
    public static function getToolsForUser(int $userId, int $storeId, string $roleKey): array
    {
        // 1. 从 DB 读取该角色的工具白名单
        $role = Db::name('crm_ai_role')
            ->where('role_key', $roleKey)
            ->where('is_active', 1)
            ->find();

        if (!$role) {
            return [];
        }

        $allowedNames = json_decode($role['tools'], true) ?: [];
        if (empty($allowedNames)) {
            return [];
        }

        // 2. 获取用户角色
        $userRole = self::getUserRole($userId);

        // 3. 从注册表取完整定义，按权限+白名单过滤
        $all = self::all();
        $result = [];
        foreach ($allowedNames as $name) {
            if (!isset($all[$name])) {
                continue;
            }
            $tool = $all[$name];
            if (!self::isAllowedFor($tool, $userRole)) {
                continue;
            }
            $result[] = self::toSchema($tool);
        }

        return $result;
    }

    /**
     * 获取所有已注册工具的 schema（供 MCP Server 同步用）
     */
    public static function getAllSchemas(): array
    {
        $result = [];
        foreach (self::all() as $name => $tool) {
            $result[] = self::toSchema($tool);
        }
        return $result;
    }

    /**
     * 获取单个工具定义
     */
    public static function get(string $name): ?array
    {
        return self::all()[$name] ?? null;
    }

    /**
     * 工具名称是否已注册
     */
    public static function exists(string $name): bool
    {
        return isset(self::all()[$name]);
    }

    // ============================================================
    //  内部方法
    // ============================================================

    /**
     * 所有工具定义（PHP 代码 → 类型安全，不可运营修改）
     */
    private static function all(): array
    {
        if (self::$tools) {
            return self::$tools;
        }

        self::$tools = [
            // ── 公司信息解析 ──
            'parse_company' => [
                'name'        => 'parse_company',
                'description' => '将公司信息写入结构化数据以触发自动填表。从网页抓取后必须调用此工具！company_name必填，其他字段选填（telephone/address/email/website/industry等）',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => [
                        'company_name' => ['type' => 'string', 'description' => '公司全称（必填）'],
                        'telephone'    => ['type' => 'string', 'description' => '电话'],
                        'address'      => ['type' => 'string', 'description' => '地址'],
                        'email'        => ['type' => 'string', 'description' => '邮箱'],
                        'website'      => ['type' => 'string', 'description' => '网站'],
                        'industry'     => ['type' => 'string', 'description' => '行业'],
                        'introduction' => ['type' => 'string', 'description' => '公司简介'],
                    ],
                    'required' => ['company_name'],
                ],
                'risk'        => 'none',
                'isAsync'     => false,
                'roles'       => [self::ROLE_ADMIN, self::ROLE_MANAGER, self::ROLE_SALES],
            ],

            // ── 企业工商查询（entquery skill） ──
            'lookup_company' => [
                'name'        => 'lookup_company',
                'description' => '按公司名称或统一社会信用代码查询真实工商信息（企业全称/信用代码/法人/注册资本/成立日期/登记状态/注册地址/经营范围）。核查客户主体、补全客户工商信息时用。查到后若要填表，再调用 parse_company 把这些字段传进去',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => [
                        'keyword' => ['type' => 'string', 'description' => '公司名称或 18 位统一社会信用代码'],
                        'force'   => ['type' => 'boolean', 'description' => '是否强制刷新（默认 false，命中已有档案不产生费用）'],
                    ],
                    'required' => ['keyword'],
                ],
                'risk'        => 'none',
                'isAsync'     => false,
                'roles'       => [self::ROLE_ADMIN, self::ROLE_MANAGER, self::ROLE_SALES],
            ],

            // ── 日历查询 ──
            'calendar' => [
                'name'        => 'calendar',
                'description' => '查询日历事件（含重复事件展开）。返回与日历页面完全一致的数据。查某天有什么事件/某个客户的事件列表，用这个最准',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => [
                        'start'       => ['type' => 'string', 'description' => '开始日期 YYYY-MM-DD'],
                        'end'         => ['type' => 'string', 'description' => '结束日期 YYYY-MM-DD'],
                        'customer_id' => ['type' => 'integer', 'description' => '客户ID（可选）'],
                    ],
                    'required' => [],
                ],
                'risk'        => 'none',
                'isAsync'     => false,
                'roles'       => [self::ROLE_ADMIN, self::ROLE_MANAGER, self::ROLE_SALES],
            ],

            // ── 网页抓取 ──
            'fetch_webpage' => [
                'name'        => 'fetch_webpage',
                'description' => '抓取网页内容，提取标题和文本。用于解析公司官网获取工商信息。用户提供URL时自动调用',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => [
                        'url' => ['type' => 'string', 'description' => '网页完整URL（含https://）'],
                    ],
                    'required' => ['url'],
                ],
                'risk'        => 'none',
                'isAsync'     => false,
                'roles'       => [self::ROLE_ADMIN, self::ROLE_MANAGER, self::ROLE_SALES],
            ],

            // ── 通用数据查询（替代所有单表查询工具）──
            'query' => [
                'name'        => 'query',
                'description' => '通用数据查询工具。可查任何CRM表（客户/线索/跟进/报价/订单/合同/产品/知识库/日历事件/AI对话等）。需要提供 table 和可选的 filters/order/limit。这是最常用的工具，几乎任何查数据的需求都用它',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => [
                        'table'   => ['type' => 'string', 'description' => '表名。可用: crm_customer(客户)/crm_contact(联系人)/crm_followup(跟进)/crm_lead(线索)/crm_quotation(报价)/crm_order(订单)/crm_contract(合同)/crm_product(产品)/crm_knowledge(知识库)/crm_calendar_event(日历事件)/crm_ai_conversation(AI对话)'],
                        'filters' => ['type' => 'object', 'description' => '过滤条件。key=字段名, value=值。customer_id=55查某客户的下属数据。值含%为模糊匹配'],
                        'order'   => ['type' => 'string', 'description' => '排序，如 "id desc" 或 "create_time desc"'],
                        'limit'   => ['type' => 'integer', 'description' => '返回条数，默认20，最大50'],
                    ],
                    'required' => ['table'],
                ],
                'risk'        => 'none',
                'isAsync'     => false,
                'roles'       => [self::ROLE_ADMIN, self::ROLE_MANAGER, self::ROLE_SALES],
            ],

            // ── Read 工具 ──
            'search_customers' => [
                'name'        => 'search_customers',
                'description' => '按名称/行业/地区搜索客户，返回匹配的客户列表（最多10条）',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => [
                        'keyword' => ['type' => 'string', 'description' => '搜索关键词（名称/行业/地区）'],
                    ],
                    'required' => ['keyword'],
                ],
                'risk'        => 'none',
                'isAsync'     => false,
                'roles'       => [self::ROLE_ADMIN, self::ROLE_MANAGER, self::ROLE_SALES],
            ],

            'get_customer' => [
                'name'        => 'get_customer',
                'description' => '获取指定客户的详细信息，包含跟进摘要、订单数、报价数',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => [
                        'customer_id' => ['type' => 'integer', 'description' => '客户ID'],
                    ],
                    'required' => ['customer_id'],
                ],
                'risk'        => 'none',
                'isAsync'     => false,
                'roles'       => [self::ROLE_ADMIN, self::ROLE_MANAGER, self::ROLE_SALES],
            ],

            'get_orders' => [
                'name'        => 'get_orders',
                'description' => '查询指定客户的订单列表（最近10条），返回订单号、金额、状态、日期',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => [
                        'customer_id' => ['type' => 'integer', 'description' => '客户ID'],
                    ],
                    'required' => ['customer_id'],
                ],
                'risk'        => 'none',
                'isAsync'     => false,
                'roles'       => [self::ROLE_ADMIN, self::ROLE_MANAGER, self::ROLE_SALES],
            ],

            'get_quotations' => [
                'name'        => 'get_quotations',
                'description' => '查询指定客户的报价列表（最近10条），返回报价单号、金额、状态、日期',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => [
                        'customer_id' => ['type' => 'integer', 'description' => '客户ID'],
                    ],
                    'required' => ['customer_id'],
                ],
                'risk'        => 'none',
                'isAsync'     => false,
                'roles'       => [self::ROLE_ADMIN, self::ROLE_MANAGER, self::ROLE_SALES],
            ],

            'get_followups' => [
                'name'        => 'get_followups',
                'description' => '获取指定客户的近期跟进记录（最近10条）',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => [
                        'customer_id' => ['type' => 'integer', 'description' => '客户ID'],
                    ],
                    'required' => ['customer_id'],
                ],
                'risk'        => 'none',
                'isAsync'     => false,
                'roles'       => [self::ROLE_ADMIN, self::ROLE_MANAGER, self::ROLE_SALES],
            ],

            'search_products' => [
                'name'        => 'search_products',
                'description' => '搜索产品库，返回匹配的产品列表。不传 keyword 则列出最近20个产品',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => [
                        'keyword' => ['type' => 'string', 'description' => '产品名称关键词（可选，不传则列出全部）'],
                    ],
                    'required' => [],
                ],
                'risk'        => 'none',
                'isAsync'     => false,
                'roles'       => [self::ROLE_ADMIN, self::ROLE_MANAGER, self::ROLE_SALES],
            ],

            'search_knowledge' => [
                'name'        => 'search_knowledge',
                'description' => '在CRM知识库中搜索，返回匹配的文档标题和摘要。不传 keyword 则列出最近的知识',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => [
                        'keyword' => ['type' => 'string', 'description' => '搜索关键词（可选）'],
                    ],
                    'required' => [],
                ],
                'risk'        => 'none',
                'isAsync'     => false,
                'roles'       => [self::ROLE_ADMIN, self::ROLE_MANAGER, self::ROLE_SALES],
            ],

            'search_leads' => [
                'name'        => 'search_leads',
                'description' => '在CRM线索库中搜索，返回线索名称、公司、状态、来源。不传 keyword 则列出最近20条线索',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => [
                        'keyword' => ['type' => 'string', 'description' => '搜索关键词（可选）'],
                    ],
                    'required' => [],
                ],
                'risk'        => 'none',
                'isAsync'     => false,
                'roles'       => [self::ROLE_ADMIN, self::ROLE_MANAGER, self::ROLE_SALES],
            ],

            // ── 仪表盘 ──
            'get_dashboard' => [
                'name'        => 'get_dashboard',
                'description' => '获取仪表盘概览：本月新增客户、本月业绩（报价/订单）、待跟进客户列表',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => (object)[],
                ],
                'risk'        => 'none',
                'isAsync'     => false,
                'roles'       => [self::ROLE_ADMIN, self::ROLE_MANAGER],
            ],

            'get_my_stats' => [
                'name'        => 'get_my_stats',
                'description' => '查询当前用户的业绩数据：客户数、本月成交额',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => (object)[],
                ],
                'risk'        => 'none',
                'isAsync'     => false,
                'roles'       => [self::ROLE_ADMIN, self::ROLE_MANAGER, self::ROLE_SALES],
            ],

            // ── Write 工具（低风险）──
            'add_followup' => [
                'name'        => 'add_followup',
                'description' => '为指定客户添加一条跟进记录',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => [
                        'customer_id'   => ['type' => 'integer', 'description' => '客户ID'],
                        'follow_content'=> ['type' => 'string', 'description' => '跟进内容'],
                        'follow_type'   => ['type' => 'string', 'description' => '跟进方式: 电话/拜访/微信/邮件'],
                    ],
                    'required' => ['customer_id', 'follow_content'],
                ],
                'risk'        => 'low',
                'isAsync'     => false,
                'roles'       => [self::ROLE_ADMIN, self::ROLE_MANAGER, self::ROLE_SALES],
            ],

            'create_contact' => [
                'name'        => 'create_contact',
                'description' => '为客户添加联系人',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => [
                        'contact_name' => ['type' => 'string', 'description' => '联系人姓名'],
                        'customer_id'  => ['type' => 'integer', 'description' => '客户ID'],
                        'mobile'       => ['type' => 'string', 'description' => '手机号'],
                        'position'     => ['type' => 'string', 'description' => '职位'],
                        'email'        => ['type' => 'string', 'description' => '邮箱'],
                        'wechat'       => ['type' => 'string', 'description' => '微信号'],
                    ],
                    'required' => ['contact_name'],
                ],
                'risk'        => 'medium',
                'isAsync'     => false,
                'roles'       => [self::ROLE_ADMIN, self::ROLE_MANAGER, self::ROLE_SALES],
            ],

            'create_lead' => [
                'name'        => 'create_lead',
                'description' => '在CRM中创建一条新线索',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => [
                        'lead_name'    => ['type' => 'string', 'description' => '线索名称'],
                        'company_name' => ['type' => 'string', 'description' => '公司名称'],
                        'contact_person' => ['type' => 'string', 'description' => '联系人'],
                        'contact_phone'  => ['type' => 'string', 'description' => '联系电话'],
                        'source'       => ['type' => 'string', 'description' => '来源'],
                    ],
                    'required' => ['lead_name', 'company_name'],
                ],
                'risk'        => 'medium',
                'isAsync'     => false,
                'roles'       => [self::ROLE_ADMIN, self::ROLE_MANAGER, self::ROLE_SALES],
            ],

            'add_customer_note' => [
                'name'        => 'add_customer_note',
                'description' => '为客户追加一条备注（不覆盖现有备注，自动添加时间戳）',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => [
                        'customer_id' => ['type' => 'integer', 'description' => '客户ID'],
                        'note'        => ['type' => 'string', 'description' => '备注内容'],
                    ],
                    'required' => ['customer_id', 'note'],
                ],
                'risk'        => 'low',
                'isAsync'     => false,
                'roles'       => [self::ROLE_ADMIN, self::ROLE_MANAGER, self::ROLE_SALES],
            ],

            // ── Write 工具（高风险 — 需要管理员权限）──
            'create_customer' => [
                'name'        => 'create_customer',
                'description' => '在CRM中创建新客户',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => [
                        'customer_name' => ['type' => 'string', 'description' => '客户名称'],
                        'industry'      => ['type' => 'string', 'description' => '行业'],
                        'telephone'     => ['type' => 'string', 'description' => '联系电话'],
                        'short_name'    => ['type' => 'string', 'description' => '客户简称'],
                        'source'        => ['type' => 'string', 'description' => '客户来源'],
                        'province'      => ['type' => 'string', 'description' => '省份'],
                        'city'          => ['type' => 'string', 'description' => '城市'],
                    ],
                    'required' => ['customer_name'],
                ],
                'risk'        => 'medium',
                'isAsync'     => false,
                'roles'       => [self::ROLE_ADMIN, self::ROLE_MANAGER, self::ROLE_SALES],
            ],

            'update_customer' => [
                'name'        => 'update_customer',
                'description' => '修改客户工商信息和联系方式（只更新传入的字段）',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => [
                        'id'           => ['type' => 'integer', 'description' => '客户ID'],
                        'industry'     => ['type' => 'string', 'description' => '行业'],
                        'telephone'    => ['type' => 'string', 'description' => '联系电话'],
                        'address'      => ['type' => 'string', 'description' => '地址'],
                        'email'        => ['type' => 'string', 'description' => '邮箱'],
                    ],
                    'required' => ['id'],
                ],
                'risk'        => 'medium',
                'isAsync'     => false,
                'roles'       => [self::ROLE_ADMIN, self::ROLE_MANAGER],
            ],

            'batch_update' => [
                'name'        => 'batch_update',
                'description' => '批量修改多个客户（高风险：仅管理员可用）',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => [
                        'ids'   => ['type' => 'array', 'items' => ['type' => 'integer'], 'description' => '客户ID列表'],
                        'field' => ['type' => 'string', 'description' => '需要修改的字段'],
                        'value' => ['type' => 'string', 'description' => '修改后的值'],
                    ],
                    'required' => ['ids', 'field', 'value'],
                ],
                'risk'        => 'high',
                'isAsync'     => false,
                'roles'       => [self::ROLE_ADMIN],
            ],

            // ── 异步工具 ──
            'generate_content' => [
                'name'        => 'generate_content',
                'description' => '使用AI内容引擎生成方案、文章、推文、话术等长文本内容。此操作较耗时（20-60秒），会异步执行后通知。',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => [
                        'topic'        => ['type' => 'string', 'description' => '内容主题/标题'],
                        'content_type' => ['type' => 'string', 'description' => '内容类型: article/social/proposal/script'],
                        'customer_id'  => ['type' => 'integer', 'description' => '关联客户ID（可选，用于个性化）'],
                        'length'       => ['type' => 'string', 'description' => '长度: short/medium/long'],
                        'keywords'     => ['type' => 'string', 'description' => '关键要点（逗号分隔）'],
                    ],
                    'required' => ['topic'],
                ],
                'risk'        => 'medium',
                'isAsync'     => true,
                'asyncPreview'=> '正在基于{{customer_name}}的行业信息生成{{content_type_label}}…',
                'roles'       => [self::ROLE_ADMIN, self::ROLE_MANAGER, self::ROLE_SALES],
            ],
        ];

        return self::$tools;
    }

    /**
     * 工具 → 标准 JSON Schema（DeepSeek Function Calling 格式）
     */
    private static function toSchema(array $tool): array
    {
        return [
            'name'        => $tool['name'],
            'description' => $tool['description'],
            'parameters'  => $tool['parameters'],
        ];
    }

    /**
     * 用户角色是否满足工具的权限要求
     */
    private static function isAllowedFor(array $tool, string $userRole): bool
    {
        return in_array($userRole, $tool['roles'], true);
    }

    /**
     * 获取用户角色
     *
     * TODO: 接入现有权限系统。当前简化实现——从 user 表读取。
     */
    private static function getUserRole(int $userId): string
    {
        // 先查缓存
        static $cache = [];
        if (isset($cache[$userId])) {
            return $cache[$userId];
        }

        // 查用户表
        $user = Db::name('store_user')
            ->where('store_user_id', $userId)
            ->find();

        if (!$user) {
            return self::ROLE_SALES; // 默认最低权限
        }

        // 判断角色
        if (!empty($user['is_admin'])) {
            $cache[$userId] = self::ROLE_ADMIN;
        } elseif (!empty($user['is_manager'])) {
            $cache[$userId] = self::ROLE_MANAGER;
        } else {
            $cache[$userId] = self::ROLE_SALES;
        }

        return $cache[$userId];
    }
}
