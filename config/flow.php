<?php

/**
 * 平台流程引擎 · 业务注册表（开源版）
 *
 * 由 sync/opensource/overrides 维护。
 * 与私有版的差异：去掉财税（fiscal）和工单（workorder）两个闭源业务类型，
 * 只保留 CRM 客服单；这两个 skill 不在开源范围内，Hook 类不存在。
 *
 * 新增业务只在这里加一段，不需要改引擎、表结构、设计器。
 */

return [
    'biz_types' => [
        'crm_service_ticket' => [
            'name' => '客服单',
            'hook' => '\\mpp\\crm\\service\\ServiceTicketFlowHook',
            'template_seeder' => '\\mpp\\crm\\service\\ServiceTicketFlowTemplate',
            'template_presets' => '\\mpp\\crm\\service\\ServiceTicketFlowTemplate',
            'list_route' => '/crm/service/ticket',
            'detail_route' => '/crm/service/ticket',
            'title_field' => 'ticket_no',
            'subtitle_field' => 'title',
            'default_template' => 'crm_service_ticket_default',
            'variables' => [
                'priority' => '优先级',
                'category' => '类型',
                'channel' => '渠道',
                'status' => '状态',
                'assignee_user_id' => '处理人',
                'created_by' => '创建人',
                'customer_id' => '客户ID',
                'dept_id' => '处理部门',
            ],
            'status_map' => [
                1 => '新建',
                2 => '处理中',
                3 => '待确认',
                4 => '已解决',
                5 => '已关闭',
            ],
        ],
    ],

    // 流程默认参数
    'defaults' => [
        'sla_hours' => [
            'task' => 24,
            'approval' => 24,
            'sign' => 48,
            'payment' => 24,
        ],
        'remind_before_hours' => 4,
        'allow_transfer' => true,
        'allow_add_sign' => true,
    ],
];
