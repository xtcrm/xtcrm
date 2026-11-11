<?php

/**
 * CRM 菜单声明（开源版）
 *
 * 由 sync/opensource/overrides 维护，不要直接改开源仓库里的这份文件。
 * 与私有版的差异：
 *   - 应用里只保留票无忧（invoice，已开源）；去掉获客账本、官网管理
 *   - 去掉 AI 助手 / OpenClaw 菜单（对应前端路由不在开源范围内）
 *
 * SQL 迁移是运行时的唯一真相源；本文件仅供阅读参考。
 * ID 分配: 20000-20099 工作台 | 20100-20199 客户 | 20200-20299 销售
 *          20300-20399 知识库 | 20500-20599 应用 | 20600-20799 系统设置
 */
return [
    // ==========================================================
    // 20000 工作台
    // ==========================================================
    [
        'name' => '工作台',
        'path' => '/index',
        'icon' => 'dashboard',
        'sort' => 10,
    ],

    // ==========================================================
    // 20100 客户管理
    // ==========================================================
    [
        'name'     => '客户管理',
        'path'     => '/crm/customer',
        'icon'     => 'team',
        'sort'     => 20,
        'children' => [
            ['name' => '客户列表',   'path' => '/crm/customer/index'],
            ['name' => '公海池',     'path' => '/crm/customer/pool'],
            ['name' => '协作客户',   'path' => '/crm/customer/collab'],
            ['name' => '新增客户',   'path' => '/crm/customer/create'],
            ['name' => '今日拜访',   'path' => '/crm/customer/today-visit'],
            ['name' => '线索管理',   'path' => '/crm/lead/index'],
            ['name' => '联系人管理', 'path' => '/crm/contact/index'],
        ],
    ],

    // ==========================================================
    // 20150 项目管理
    // ==========================================================
    [
        'name'     => '项目管理',
        'path'     => '/crm/project',
        'icon'     => 'project',
        'sort'     => 25,
        'children' => [
            ['name' => '项目列表', 'path' => '/crm/project/index'],
            ['name' => '需求池',   'path' => '/crm/intent/index'],
            ['name' => '服务目录', 'path' => '/crm/project/catalog'],
        ],
    ],

    // ==========================================================
    // 20200 销售管理
    // ==========================================================
    [
        'name'     => '销售管理',
        'path'     => '/crm/quotation',
        'icon'     => 'dollar-circle',
        'sort'     => 30,
        'children' => [
            ['name' => '报价管理', 'path' => '/crm/quotation/index'],
            ['name' => '订单管理', 'path' => '/crm/order/index'],
            ['name' => '合同管理', 'path' => '/crm/contract/index'],
        ],
    ],

    // ==========================================================
    // 20300 知识库
    // ==========================================================
    [
        'name'     => '知识库',
        'path'     => '/crm/knowledge',
        'icon'     => 'book',
        'sort'     => 40,
        'children' => [
            ['name' => '知识列表', 'path' => '/crm/knowledge/index'],
            [
                'name'     => '内容创作',
                'path'     => '/crm/content',
                'children' => [
                    ['name' => '创作工坊',   'path' => '/crm/content/create'],
                    ['name' => '创作配置',   'path' => '/crm/content/config'],
                    ['name' => '公司档案',   'path' => '/crm/content/profile'],
                    ['name' => '关键词管理', 'path' => '/crm/content/keywords'],
                    ['name' => '解决方案库', 'path' => '/crm/content/solutions'],
                    ['name' => '写作风格',   'path' => '/crm/content/styles'],
                    ['name' => '创作Agent',  'path' => '/crm/content/agents'],
                    ['name' => '内容日历',   'path' => '/crm/content/calendar'],
                    ['name' => '内容效果',   'path' => '/crm/content/analytics'],
                ],
            ],
        ],
    ],

    // ==========================================================
    // 20500 应用（开源版只保留票无忧）
    // ==========================================================
    [
        'name'     => '应用',
        'path'     => '/app',
        'icon'     => 'appstore',
        'sort'     => 60,
        'children' => [
            [
                'name'     => '票无忧',
                'path'     => '/app/invoice',
                'children' => [
                    ['name' => '税负仪表盘', 'path' => '/app/invoice/dashboard'],
                    ['name' => '发票管理',   'path' => '/app/invoice/list'],
                    ['name' => '开票公司',   'path' => '/app/invoice/company'],
                    ['name' => '短信配置',   'path' => '/app/invoice/sms'],
                    ['name' => '预警配置',   'path' => '/app/invoice/alert'],
                    ['name' => 'OCR 配置',   'path' => '/app/invoice/ocr'],
                ],
            ],
        ],
    ],

    // ==========================================================
    // 20600 系统设置
    // ==========================================================
    [
        'name'     => '系统设置',
        'path'     => '/crm/product',
        'icon'     => 'setting',
        'sort'     => 70,
        'children' => [
            ['name' => '产品管理',   'path' => '/crm/product/index'],
            ['name' => '管理员列表', 'path' => '/manage/user/index'],
            ['name' => '角色管理',   'path' => '/manage/role/index'],
            ['name' => '部门管理',   'path' => '/manage/department/index'],
            ['name' => '菜单管理',   'path' => '/manage/menu/index'],
            [
                'name'     => 'CRM 设置',
                'path'     => '/crm/setting',
                'children' => [
                    ['name' => '公海规则',   'path' => '/crm/setting?tab=pool'],
                    ['name' => 'AI配置',     'path' => '/crm/setting?tab=ai'],
                    ['name' => 'AI洞察',     'path' => '/crm/setting?tab=insight'],
                    ['name' => '字典配置',   'path' => '/crm/setting?tab=dict'],
                    ['name' => '自定义字段', 'path' => '/crm/setting/customField'],
                    ['name' => '需求规则',   'path' => '/crm/setting/demandRule'],
                    ['name' => 'API 密钥',   'path' => '/crm/setting/apiKey'],
                ],
            ],
        ],
    ],
];
