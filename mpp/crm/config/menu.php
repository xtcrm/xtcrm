<?php

/**
 * CRM 菜单声明
 *
 * ModuleLoader 启动时自动同步到 yoshop_store_menu 表。
 * 格式：parent_id=0 为顶级菜单，children 为子菜单。
 *
 * 路径约定：/crm.{controller}/{action}
 */
return [
    [
        'name'     => '工作台',
        'path'     => '/index',
        'icon'     => 'dashboard',
        'sort'     => 1,
    ],
    [
        'name'     => '线索管理',
        'path'     => '/crm/lead',
        'icon'     => 'bulb',
        'sort'     => 2,
        'children' => [
            ['name' => '线索列表', 'path' => '/crm/lead/lists',   'action' => 'lists'],
            ['name' => '新增线索', 'path' => '/crm/lead/add',     'action' => 'add'],
            ['name' => '编辑线索', 'path' => '/crm/lead/edit',    'action' => 'edit'],
            ['name' => '删除线索', 'path' => '/crm/lead/delete',  'action' => 'delete'],
            ['name' => '线索详情', 'path' => '/crm/lead/detail',  'action' => 'detail'],
            ['name' => '转为客户', 'path' => '/crm/lead/convert', 'action' => 'convert'],
        ],
    ],
    [
        'name'     => '客户管理',
        'path'     => '/crm/customer',
        'icon'     => 'team',
        'sort'     => 3,
        'children' => [
            ['name' => '客户列表',  'path' => '/crm/customer/lists',        'action' => 'lists'],
            ['name' => '客户查询',  'path' => '/crm/customer/select',       'action' => 'select'],
            ['name' => '新增客户',  'path' => '/crm/customer/add',          'action' => 'add'],
            ['name' => '编辑客户',  'path' => '/crm/customer/edit',         'action' => 'edit'],
            ['name' => '删除客户',  'path' => '/crm/customer/delete',       'action' => 'delete'],
            ['name' => '客户详情',  'path' => '/crm/customer/detail',       'action' => 'detail'],
            ['name' => '公海列表',  'path' => '/crm/customer/poolLists',    'action' => 'poolLists'],
            ['name' => '协作列表',  'path' => '/crm/customer/collabLists',  'action' => 'collabLists'],
            ['name' => '认领客户',  'path' => '/crm/customer/claim',        'action' => 'claim'],
            ['name' => '释放客户',  'path' => '/crm/customer/release',      'action' => 'release'],
            ['name' => '变更状态',  'path' => '/crm/customer/changeStatus', 'action' => 'changeStatus'],
            ['name' => 'AI分析',    'path' => '/crm/customer/analyze',      'action' => 'analyze'],
            ['name' => 'AI画像',    'path' => '/crm/customer/portrait',     'action' => 'portrait'],
            ['name' => '智能搜索',  'path' => '/crm/customer/smartSearch',  'action' => 'smartSearch'],
            ['name' => '今日拜访',   'path' => '/crm/customer/today-visit',  'action' => 'todayVisits'],
            ['name' => '联系人管理', 'path' => '/crm/contact/list',        'action' => 'list'],
            ['name' => '联系人详情', 'path' => '/crm/contact/detail',      'action' => 'detail''],
        ],
    ],
    [
        'name'     => '报价管理',
        'path'     => '/crm/quotation',
        'icon'     => 'file-text',
        'sort'     => 4,
    ],
    [
        'name'     => '订单管理',
        'path'     => '/crm/order',
        'icon'     => 'shopping-cart',
        'sort'     => 5,
    ],
    [
        'name'     => '合同管理',
        'path'     => '/crm/contract',
        'icon'     => 'safety-certificate',
        'sort'     => 6,
    ],
    [
        'name'     => '知识库',
        'path'     => '/crm/knowledge',
        'icon'     => 'book',
        'sort'     => 7,
    ],
    [
        'name'     => '获客账本',
        'path'     => '/app/leadledger',
        'icon'     => 'solution',
        'sort'     => 9,
        'children' => [
            ['name' => '驾驶舱',   'path' => '/app/leadledger/dashboard',    'action' => 'lists'],
            ['name' => '分享链接', 'path' => '/app/leadledger/share-link',   'action' => 'lists'],
            ['name' => '页面模板', 'path' => '/app/leadledger/page-template','action' => 'lists'],
            ['name' => '表单设计', 'path' => '/app/leadledger/form-design',  'action' => 'lists'],
            ['name' => '线索列表', 'path' => '/app/leadledger/lead',         'action' => 'lists'],
        ],
    ],
    [
        'name'     => '票无忧',
        'path'     => '/app/invoice',
        'icon'     => 'file-protect',
        'sort'     => 10,
    ],
    [
        'name'     => '系统设置',
        'path'     => '/setting',
        'icon'     => 'setting',
        'sort'     => 99,
        'children' => [
            ['name' => '产品管理', 'path' => '/crm/product/lists', 'action' => 'lists'],
            ['name' => 'CRM设置',  'path' => '/crm/setting/index', 'action' => 'index'],
        ],
    ],
];
