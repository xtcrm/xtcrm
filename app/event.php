<?php
/**
 * 事件定义文件
 * 注：此处定义的定时任务, 执行句柄在 app/timer/controller/Store.php
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
    ],
];
