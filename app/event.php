<?php
/**
 * 事件定义文件（开源版）
 *
 * 由 sync/opensource/overrides 维护。
 * 与私有版的差异：去掉商城 timer 与闭源 skill 的 timer，只保留 CRM / 平台流程。
 */
return [
    'bind' => [],
    'listen' => [
        'AppInit' => [
            [\cores\SkillAutoload::class, 'handle'],   // skill 零文件路由（最先注册，在上层）
            [\cores\MppAutoload::class, 'handle'],    // mpp 零文件路由
            [\app\platform\migration\Runner::class, 'boot'], // Platform 迁移（Module/Skill 之前）
            \app\platform\event\SkillBoot::class,         // ModuleLoader + SkillLoader
        ],
        'HttpRun' => [],
        'HttpEnd' => [],
        'LogLevel' => [],
        'LogWrite' => [],
        // 定时任务：CRM 洞察引擎
        'CrmInsight' => [\mpp\crm\timer\CrmInsight::class],
        // 定时任务：CRM 公海自动掉入
        'CrmPool' => [\mpp\crm\timer\CrmPool::class],
        // 定时任务：客服单到期提醒
        'ServiceTicketRemind' => [\mpp\crm\timer\ServiceTicketRemind::class],
        // 定时任务：平台流程 SLA 提醒/升级
        'FlowSla' => [\app\platform\flow\timer\Sla::class],
    ],
];
