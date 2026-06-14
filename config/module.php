<?php

/**
 * 模块系统配置
 *
 * ModuleLoader 扫描 modules/ 目录，自动注册 autoloader。
 * 机制与 skills/ 的 SkillLoader 一致，用于大型业务模块（CRM、商城、财税等）。
 */
return [
    // 模块根目录（modules/ 已删除，全部迁入 mpp/）
    'base_dir' => root_path('mpp'),

    // 是否自动注册 autoloader
    'auto_register' => true,

    // 禁用的模块列表
    'disabled' => [],
];
