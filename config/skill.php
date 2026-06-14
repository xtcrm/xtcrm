<?php

/**
 * 技能插件系统配置
 */
return [
    // 技能包存放目录
    'base_dir' => root_path('skills'),

    // 是否自动扫描注册
    'auto_register' => true,

    // wkhtmltopdf 二进制路径（留空则禁用 PDF 导出）
    'wkhtmltopdf_bin' => '',

    // 模板引擎缓存目录
    'template_cache_dir' => runtime_path('cache/skills'),

    // 已禁用的技能包列表
    'disabled' => [],
];
