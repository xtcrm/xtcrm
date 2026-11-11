-- =============================================
-- CRM v2.7.0 — AI 侧边面板基础设施
-- =============================================

-- 1. AI 对话历史（替代现有 crm_agent_log）
CREATE TABLE IF NOT EXISTS `yoshop_crm_ai_conversation` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `store_id` int unsigned NOT NULL DEFAULT 0 COMMENT '租户ID',
  `user_id` int unsigned NOT NULL DEFAULT 0 COMMENT '用户ID',
  `session_id` varchar(64) NOT NULL COMMENT '会话标识',
  `role_key` varchar(50) DEFAULT NULL COMMENT 'assistant消息的角色来源',
  `role` enum('system','user','assistant','tool') NOT NULL COMMENT '消息角色',
  `content` text NOT NULL COMMENT '消息内容',
  `tool_calls` json DEFAULT NULL COMMENT '工具调用记录 {name,args,result}',
  `context_type` varchar(50) DEFAULT NULL COMMENT '上下文类型: customer/lead/calendar/list/general',
  `context_id` int unsigned DEFAULT NULL COMMENT '上下文实体ID',
  `token_count` int unsigned DEFAULT NULL COMMENT '该条消息token用量',
  `status` tinyint unsigned DEFAULT 1 COMMENT '1=active, 0=archived',
  `create_time` int unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_session` (`store_id`, `user_id`, `session_id`),
  KEY `idx_context` (`store_id`, `context_type`, `context_id`),
  KEY `idx_archive` (`store_id`, `user_id`, `status`, `create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI对话历史';

-- 2. AI 数字员工角色定义（系统级，无 store_id）
CREATE TABLE IF NOT EXISTS `yoshop_crm_ai_role` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `role_key` varchar(50) NOT NULL COMMENT '角色标识: sales/creator/coach',
  `role_name` varchar(100) NOT NULL COMMENT '角色名称',
  `description` text NOT NULL COMMENT '角色描述',
  `system_prompt` text NOT NULL COMMENT 'System Prompt',
  `tools` json NOT NULL COMMENT '工具名数组，引用AiToolRegistry',
  `triggers` json NOT NULL COMMENT '触发词配置 {"keywords":[...],"priority":1}',
  `icon` varchar(50) DEFAULT NULL COMMENT '图标',
  `sort_order` int unsigned DEFAULT 0 COMMENT '排序',
  `is_active` tinyint unsigned DEFAULT 1 COMMENT '是否启用',
  `create_time` int unsigned NOT NULL DEFAULT 0,
  `update_time` int unsigned NOT NULL DEFAULT 0,
  UNIQUE KEY `uk_role_key` (`role_key`),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI数字员工角色定义';

-- 3. 工具调用审计
CREATE TABLE IF NOT EXISTS `yoshop_crm_ai_tool_log` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `store_id` int unsigned NOT NULL DEFAULT 0 COMMENT '租户ID',
  `user_id` int unsigned NOT NULL DEFAULT 0 COMMENT '用户ID',
  `tool_name` varchar(100) NOT NULL COMMENT '工具名称',
  `args` json DEFAULT NULL COMMENT '调用参数',
  `result` json DEFAULT NULL COMMENT '执行结果摘要',
  `success` tinyint unsigned DEFAULT 1 COMMENT '是否成功',
  `duration_ms` int unsigned DEFAULT NULL COMMENT '执行耗时',
  `create_time` int unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`store_id`, `user_id`, `create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI工具调用审计';

-- 4. 异步任务（内容生成等耗时操作）
CREATE TABLE IF NOT EXISTS `yoshop_crm_ai_async_task` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `task_id` varchar(64) NOT NULL COMMENT '任务ID',
  `store_id` int unsigned NOT NULL DEFAULT 0 COMMENT '租户ID',
  `user_id` int unsigned NOT NULL DEFAULT 0 COMMENT '用户ID',
  `session_id` varchar(64) NOT NULL COMMENT '关联会话',
  `tool_name` varchar(100) NOT NULL COMMENT '工具名称',
  `args` json DEFAULT NULL COMMENT '调用参数',
  `result` text DEFAULT NULL COMMENT '执行结果',
  `conversation_id` int unsigned DEFAULT NULL COMMENT '完成后关联的conversation记录ID',
  `status` enum('pending','running','completed','failed') DEFAULT 'pending' COMMENT '状态',
  `error` text DEFAULT NULL COMMENT '错误信息',
  `duration_ms` int unsigned DEFAULT NULL COMMENT '执行耗时',
  `create_time` int unsigned NOT NULL DEFAULT 0,
  `update_time` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_task_id` (`task_id`),
  KEY `idx_status` (`store_id`, `user_id`, `status`),
  KEY `idx_session` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI异步任务';

-- 5. 租户级角色开关（覆盖全局 is_active）
CREATE TABLE IF NOT EXISTS `yoshop_crm_ai_store_role` (
  `store_id` int unsigned NOT NULL DEFAULT 0 COMMENT '租户ID',
  `role_key` varchar(50) NOT NULL COMMENT '角色标识',
  `enabled` tinyint unsigned DEFAULT 1 COMMENT '是否启用',
  PRIMARY KEY (`store_id`, `role_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='租户AI角色开关';

-- 6. 用户级 AI 记忆
CREATE TABLE IF NOT EXISTS `yoshop_crm_ai_user_memory` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `store_id` int unsigned NOT NULL DEFAULT 0 COMMENT '租户ID',
  `user_id` int unsigned NOT NULL DEFAULT 0 COMMENT '用户ID',
  `memory_key` varchar(100) NOT NULL COMMENT '记忆键: preferred_style/common_actions/frequent_customers',
  `memory_value` text NOT NULL COMMENT 'JSON记忆值',
  `update_time` int unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_key` (`store_id`, `user_id`, `memory_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户级AI记忆';

-- 7. AI 回复质量反馈
CREATE TABLE IF NOT EXISTS `yoshop_crm_ai_feedback` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `store_id` int unsigned NOT NULL DEFAULT 0 COMMENT '租户ID',
  `user_id` int unsigned NOT NULL DEFAULT 0 COMMENT '用户ID',
  `conversation_id` int unsigned NOT NULL COMMENT '对话记录ID',
  `rating` tinyint NOT NULL COMMENT '1=好评 0=差评',
  `reason` varchar(500) DEFAULT NULL COMMENT '差评原因',
  `create_time` int unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_conv` (`conversation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI回复质量反馈';

-- =============================================
-- 预置 3 个数字员工角色
-- =============================================
INSERT IGNORE INTO `yoshop_crm_ai_role` (`role_key`, `role_name`, `description`, `system_prompt`, `tools`, `triggers`, `icon`, `sort_order`, `is_active`, `create_time`, `update_time`) VALUES

('sales', '销售助手', '一线销售的万能搭档。管客户、补资料、记跟进、催续约。',
'你是CRM销售助手。简洁务实，用工具查数据。

可用工具：
- query(table,filters)：查任何业务数据
- calendar(start,end)：查日历事件（含重复事件展开+需求）。查今天/某天有什么事件、日程安排，必须用这个
- add_followup/create_contact 等：写操作
- fetch_webpage+parse_company：网页抓取+自动填表

规则：基于实际数据回答，写操作必须调工具。',
'["search_customers","get_customer","get_orders","get_quotations","get_followups","add_followup","create_contact","add_customer_note","search_products","create_lead","get_my_stats","search_leads","parse_company","fetch_webpage","query","calendar"]',
'{"keywords":["查","看","找","联系","电话","跟进","拜访","工商","补全","填充","企查查","天眼查","合同","续约","复购","回访","满意度","到期","怎么样","最近","客户"],"priority":1}',
'smile', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),

('creator', '创作与方案专家', '负责所有"写"的工作：推文、方案、话术、脚本。',
'你是创作与方案专家，帮助销售团队产出高质量的文本内容。

能力范围：
- 写推文/文章：公众号、官网、知乎、LinkedIn
- 做方案：技术方案、投标文件、产品对比表
- 写话术：电话邀约、异议处理、跟进邮件模板
- 输出格式：长文、短视频脚本、朋友圈短文、SEO描述

原则：
1. 基于客户信息和产品数据撰写，不说空话
2. 先了解目标受众和渠道，再动笔
3. 内容生成较耗时，会异步生成后通知
4. 可以先生成大纲让用户确认方向',
'["search_customers","get_customer","search_products","search_knowledge","generate_content"]',
'{"keywords":["写","文章","推文","公众号","朋友圈","文案","方案","提案","投标","标书","话术","脚本","产品介绍","对比","大纲"],"priority":1}',
'edit', 2, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),

('coach', '业绩教练', '面向管理层和进阶销售：看数据、做分析、练话术。',
'你是业绩教练，帮助销售团队和管理层分析数据、发现问题、提升能力。

能力范围：
- 看数据：销售漏斗、转化率、周报月报
- 做分析：丢单原因、行业趋势、KPI预警
- 练话术：模拟电话/拜访场景，给出改进建议

原则：
1. 用数据说话，不凭感觉判断
2. 分析问题给具体建议，不只讲大道理
3. 主动发现异常（KPI偏离、转化率骤降）
4. 回答保持专业但易懂',
'["get_dashboard","get_my_stats","search_customers","search_leads","batch_update"]',
'{"keywords":["报表","数据","统计","分析","周报","月报","趋势","KPI","演练","模拟","排名","怎么说","怎么提升","原因","对比","转化率","漏斗"],"priority":1}',
'bar-chart', 3, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
