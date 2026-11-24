-- =============================================
-- 平台流程引擎 · 基础表
-- 版本：1.0.0
-- 说明：引擎与业务解耦，只存 biz_type / biz_id，不存任何业务字段
-- =============================================

CREATE TABLE IF NOT EXISTS `yoshop_flow_template` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `store_id` int(11) unsigned NOT NULL DEFAULT 0,
  `code` varchar(50) NOT NULL DEFAULT '' COMMENT '模板编码',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '模板名称',
  `biz_type` varchar(50) NOT NULL DEFAULT '' COMMENT '业务类型，对应配置注册表',
  `version` int(11) unsigned NOT NULL DEFAULT 1 COMMENT '版本号',
  `status` varchar(20) NOT NULL DEFAULT 'draft' COMMENT 'draft/published/archived',
  `is_default` tinyint(3) unsigned NOT NULL DEFAULT 0 COMMENT '同业务类型默认模板',
  `graph` longtext COMMENT '设计器画布 JSON',
  `description` varchar(500) NOT NULL DEFAULT '',
  `created_by` int(11) unsigned NOT NULL DEFAULT 0,
  `published_by` int(11) unsigned NOT NULL DEFAULT 0,
  `published_at` int(11) unsigned NOT NULL DEFAULT 0,
  `create_time` int(11) unsigned NOT NULL DEFAULT 0,
  `update_time` int(11) unsigned NOT NULL DEFAULT 0,
  `is_delete` tinyint(3) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_store_code_version` (`store_id`,`code`,`version`),
  KEY `idx_store_biz` (`store_id`,`biz_type`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='流程模板';

CREATE TABLE IF NOT EXISTS `yoshop_flow_node` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `store_id` int(11) unsigned NOT NULL DEFAULT 0,
  `template_id` int(11) unsigned NOT NULL DEFAULT 0,
  `node_key` varchar(50) NOT NULL DEFAULT '',
  `name` varchar(100) NOT NULL DEFAULT '',
  `type` varchar(20) NOT NULL DEFAULT 'task' COMMENT 'start/task/approval/sign/payment/gateway/end',
  `gateway_type` varchar(20) NOT NULL DEFAULT '' COMMENT 'exclusive/parallel/inclusive',
  `join_mode` varchar(20) NOT NULL DEFAULT 'all' COMMENT 'all/any',
  `assignee_mode` varchar(20) NOT NULL DEFAULT 'any' COMMENT 'all/any/ratio/sequential',
  `assignee_rules` text COMMENT '指派规则 JSON',
  `complete_mode` varchar(20) NOT NULL DEFAULT 'any' COMMENT 'all/any/ratio/sequential',
  `complete_ratio` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `actions` text COMMENT '允许动作 JSON',
  `form_schema` text COMMENT '节点表单 JSON',
  `condition_json` text COMMENT '进入条件 JSON',
  `reject_policy` text COMMENT '驳回策略 JSON',
  `sync_status` varchar(20) NOT NULL DEFAULT '' COMMENT '完成后同步的业务状态',
  `sync_fields` text COMMENT '字段回写 JSON',
  `sla_hours` int(11) unsigned NOT NULL DEFAULT 0,
  `remind_before_hours` int(11) unsigned NOT NULL DEFAULT 0,
  `escalate_to` text COMMENT '超时升级对象 JSON',
  `position_x` int(11) NOT NULL DEFAULT 0,
  `position_y` int(11) NOT NULL DEFAULT 0,
  `config` text COMMENT '扩展配置 JSON',
  `sort` int(11) unsigned NOT NULL DEFAULT 100,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_template_key` (`template_id`,`node_key`),
  KEY `idx_store_template` (`store_id`,`template_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='流程节点';

CREATE TABLE IF NOT EXISTS `yoshop_flow_edge` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `store_id` int(11) unsigned NOT NULL DEFAULT 0,
  `template_id` int(11) unsigned NOT NULL DEFAULT 0,
  `from_key` varchar(50) NOT NULL DEFAULT '',
  `to_key` varchar(50) NOT NULL DEFAULT '',
  `condition_json` text,
  `label` varchar(100) NOT NULL DEFAULT '',
  `is_default` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `sort` int(11) unsigned NOT NULL DEFAULT 100,
  PRIMARY KEY (`id`),
  KEY `idx_template_from` (`template_id`,`from_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='流程连线';

CREATE TABLE IF NOT EXISTS `yoshop_flow_instance` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `store_id` int(11) unsigned NOT NULL DEFAULT 0,
  `template_id` int(11) unsigned NOT NULL DEFAULT 0,
  `template_version` int(11) unsigned NOT NULL DEFAULT 1,
  `biz_type` varchar(50) NOT NULL DEFAULT '',
  `biz_id` int(11) unsigned NOT NULL DEFAULT 0,
  `status` varchar(20) NOT NULL DEFAULT 'running' COMMENT 'running/completed/rejected/cancelled/terminated',
  `result` varchar(20) NOT NULL DEFAULT '' COMMENT 'approved/rejected/cancelled',
  `variables` longtext COMMENT '流程变量 JSON',
  `business_snapshot` longtext COMMENT '业务摘要 JSON',
  `started_by` int(11) unsigned NOT NULL DEFAULT 0,
  `started_at` int(11) unsigned NOT NULL DEFAULT 0,
  `finished_at` int(11) unsigned NOT NULL DEFAULT 0,
  `create_time` int(11) unsigned NOT NULL DEFAULT 0,
  `update_time` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_store_biz` (`store_id`,`biz_type`,`biz_id`,`status`),
  KEY `idx_store_status` (`store_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='流程实例';

CREATE TABLE IF NOT EXISTS `yoshop_flow_token` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `store_id` int(11) unsigned NOT NULL DEFAULT 0,
  `instance_id` int(11) unsigned NOT NULL DEFAULT 0,
  `node_key` varchar(50) NOT NULL DEFAULT '',
  `status` varchar(20) NOT NULL DEFAULT 'active' COMMENT 'active/waiting/completed/cancelled',
  `parent_token_id` int(11) unsigned NOT NULL DEFAULT 0,
  `branch_key` varchar(50) NOT NULL DEFAULT '',
  `meta` longtext COMMENT '令牌私有数据 JSON，如顺序指派队列',
  `create_time` int(11) unsigned NOT NULL DEFAULT 0,
  `completed_at` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_instance_status` (`instance_id`,`status`),
  KEY `idx_instance_node` (`instance_id`,`node_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='流程令牌';

CREATE TABLE IF NOT EXISTS `yoshop_flow_task` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `store_id` int(11) unsigned NOT NULL DEFAULT 0,
  `instance_id` int(11) unsigned NOT NULL DEFAULT 0,
  `token_id` int(11) unsigned NOT NULL DEFAULT 0,
  `node_key` varchar(50) NOT NULL DEFAULT '',
  `node_name` varchar(100) NOT NULL DEFAULT '',
  `assignee_type` varchar(20) NOT NULL DEFAULT 'user',
  `assignee_id` int(11) unsigned NOT NULL DEFAULT 0,
  `assignee_name` varchar(50) NOT NULL DEFAULT '',
  `role_id` int(11) unsigned NOT NULL DEFAULT 0,
  `dept_id` int(11) unsigned NOT NULL DEFAULT 0,
  `status` varchar(20) NOT NULL DEFAULT 'pending' COMMENT 'pending/claimed/done/rejected/transferred/cancelled',
  `action` varchar(20) NOT NULL DEFAULT '',
  `comment` varchar(1000) NOT NULL DEFAULT '',
  `form_data` longtext,
  `claimed_by` int(11) unsigned NOT NULL DEFAULT 0,
  `claimed_at` int(11) unsigned NOT NULL DEFAULT 0,
  `completed_by` int(11) unsigned NOT NULL DEFAULT 0,
  `completed_at` int(11) unsigned NOT NULL DEFAULT 0,
  `due_at` int(11) unsigned NOT NULL DEFAULT 0,
  `remind_at` int(11) unsigned NOT NULL DEFAULT 0,
  `escalated_at` int(11) unsigned NOT NULL DEFAULT 0,
  `create_time` int(11) unsigned NOT NULL DEFAULT 0,
  `update_time` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_store_assignee` (`store_id`,`assignee_id`,`status`),
  KEY `idx_store_role` (`store_id`,`role_id`,`status`),
  KEY `idx_instance_status` (`instance_id`,`status`),
  KEY `idx_due` (`due_at`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='流程待办';

CREATE TABLE IF NOT EXISTS `yoshop_flow_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `store_id` int(11) unsigned NOT NULL DEFAULT 0,
  `instance_id` int(11) unsigned NOT NULL DEFAULT 0,
  `task_id` int(11) unsigned NOT NULL DEFAULT 0,
  `node_key` varchar(50) NOT NULL DEFAULT '',
  `action` varchar(30) NOT NULL DEFAULT '',
  `operator_id` int(11) unsigned NOT NULL DEFAULT 0,
  `operator_name` varchar(50) NOT NULL DEFAULT '',
  `comment` varchar(1000) NOT NULL DEFAULT '',
  `detail` longtext,
  `create_time` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_instance_time` (`instance_id`,`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='流程日志';

-- =============================================
-- 菜单
-- =============================================

SET @flow_app := (SELECT `menu_id` FROM `yoshop_store_menu` WHERE `path` = '/app' LIMIT 1);
SET @flow_app := IFNULL(@flow_app, 0);

INSERT INTO `yoshop_store_menu`
(`menu_id`,`module`,`fun_type`,`name`,`path`,`action_mark`,`parent_id`,`sort`,`create_time`,`update_time`)
SELECT (SELECT IFNULL(MAX(`menu_id`), 0) + 1 FROM `yoshop_store_menu`),
       10, 'crm', '流程中心', '/app/flow', '', @flow_app, 90, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `yoshop_store_menu` WHERE `path` = '/app/flow');

SET @flow_page := (SELECT `menu_id` FROM `yoshop_store_menu` WHERE `path` = '/app/flow' LIMIT 1);

INSERT INTO `yoshop_store_menu`
(`menu_id`,`module`,`fun_type`,`name`,`path`,`action_mark`,`parent_id`,`sort`,`create_time`,`update_time`)
SELECT (SELECT IFNULL(MAX(`menu_id`), 0) + 1 FROM `yoshop_store_menu`),
       10, 'crm', '我的待办', '/app/flow/task', '', @flow_page, 10, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `yoshop_store_menu` WHERE `path` = '/app/flow/task');

INSERT INTO `yoshop_store_menu`
(`menu_id`,`module`,`fun_type`,`name`,`path`,`action_mark`,`parent_id`,`sort`,`create_time`,`update_time`)
SELECT (SELECT IFNULL(MAX(`menu_id`), 0) + 1 FROM `yoshop_store_menu`),
       10, 'crm', '流程模板', '/app/flow/template', '', @flow_page, 20, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `yoshop_store_menu` WHERE `path` = '/app/flow/template');

INSERT INTO `yoshop_store_menu`
(`menu_id`,`module`,`fun_type`,`name`,`path`,`action_mark`,`parent_id`,`sort`,`create_time`,`update_time`)
SELECT (SELECT IFNULL(MAX(`menu_id`), 0) + 1 FROM `yoshop_store_menu`),
       10, 'crm', '流程设计器', '/app/flow/designer', 'hidden', @flow_page, 30, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `yoshop_store_menu` WHERE `path` = '/app/flow/designer');

INSERT INTO `yoshop_store_menu`
(`menu_id`,`module`,`fun_type`,`name`,`path`,`action_mark`,`parent_id`,`sort`,`create_time`,`update_time`)
SELECT (SELECT IFNULL(MAX(`menu_id`), 0) + 1 FROM `yoshop_store_menu`),
       10, 'crm', '流程实例', '/app/flow/instance', '', @flow_page, 40, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `yoshop_store_menu` WHERE `path` = '/app/flow/instance');

-- ---- 操作权限 ----

INSERT INTO `yoshop_store_menu`
(`menu_id`,`module`,`fun_type`,`name`,`path`,`action_mark`,`parent_id`,`sort`,`create_time`,`update_time`)
SELECT (SELECT IFNULL(MAX(`menu_id`), 0) + 1 FROM `yoshop_store_menu`),
       20, 'crm', '模板列表', '/flow.template/lists', 'lists', (SELECT `menu_id` FROM `yoshop_store_menu` WHERE `path` = '/app/flow/template' LIMIT 1), 10, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `yoshop_store_menu` WHERE `path` = '/flow.template/lists');

INSERT INTO `yoshop_store_menu`
(`menu_id`,`module`,`fun_type`,`name`,`path`,`action_mark`,`parent_id`,`sort`,`create_time`,`update_time`)
SELECT (SELECT IFNULL(MAX(`menu_id`), 0) + 1 FROM `yoshop_store_menu`),
       20, 'crm', '模板详情', '/flow.template/detail', 'detail', (SELECT `menu_id` FROM `yoshop_store_menu` WHERE `path` = '/app/flow/template' LIMIT 1), 20, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `yoshop_store_menu` WHERE `path` = '/flow.template/detail');

INSERT INTO `yoshop_store_menu`
(`menu_id`,`module`,`fun_type`,`name`,`path`,`action_mark`,`parent_id`,`sort`,`create_time`,`update_time`)
SELECT (SELECT IFNULL(MAX(`menu_id`), 0) + 1 FROM `yoshop_store_menu`),
       20, 'crm', '新建模板', '/flow.template/create', 'create', (SELECT `menu_id` FROM `yoshop_store_menu` WHERE `path` = '/app/flow/template' LIMIT 1), 30, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `yoshop_store_menu` WHERE `path` = '/flow.template/create');

INSERT INTO `yoshop_store_menu`
(`menu_id`,`module`,`fun_type`,`name`,`path`,`action_mark`,`parent_id`,`sort`,`create_time`,`update_time`)
SELECT (SELECT IFNULL(MAX(`menu_id`), 0) + 1 FROM `yoshop_store_menu`),
       20, 'crm', '保存草稿', '/flow.template/saveDraft', 'saveDraft', (SELECT `menu_id` FROM `yoshop_store_menu` WHERE `path` = '/app/flow/template' LIMIT 1), 40, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `yoshop_store_menu` WHERE `path` = '/flow.template/saveDraft');

INSERT INTO `yoshop_store_menu`
(`menu_id`,`module`,`fun_type`,`name`,`path`,`action_mark`,`parent_id`,`sort`,`create_time`,`update_time`)
SELECT (SELECT IFNULL(MAX(`menu_id`), 0) + 1 FROM `yoshop_store_menu`),
       20, 'crm', '校验模板', '/flow.template/check', 'validate', (SELECT `menu_id` FROM `yoshop_store_menu` WHERE `path` = '/app/flow/template' LIMIT 1), 50, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `yoshop_store_menu` WHERE `path` = '/flow.template/check');

INSERT INTO `yoshop_store_menu`
(`menu_id`,`module`,`fun_type`,`name`,`path`,`action_mark`,`parent_id`,`sort`,`create_time`,`update_time`)
SELECT (SELECT IFNULL(MAX(`menu_id`), 0) + 1 FROM `yoshop_store_menu`),
       20, 'crm', '发布模板', '/flow.template/publish', 'publish', (SELECT `menu_id` FROM `yoshop_store_menu` WHERE `path` = '/app/flow/template' LIMIT 1), 60, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `yoshop_store_menu` WHERE `path` = '/flow.template/publish');

INSERT INTO `yoshop_store_menu`
(`menu_id`,`module`,`fun_type`,`name`,`path`,`action_mark`,`parent_id`,`sort`,`create_time`,`update_time`)
SELECT (SELECT IFNULL(MAX(`menu_id`), 0) + 1 FROM `yoshop_store_menu`),
       20, 'crm', '归档模板', '/flow.template/archive', 'archive', (SELECT `menu_id` FROM `yoshop_store_menu` WHERE `path` = '/app/flow/template' LIMIT 1), 70, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `yoshop_store_menu` WHERE `path` = '/flow.template/archive');

INSERT INTO `yoshop_store_menu`
(`menu_id`,`module`,`fun_type`,`name`,`path`,`action_mark`,`parent_id`,`sort`,`create_time`,`update_time`)
SELECT (SELECT IFNULL(MAX(`menu_id`), 0) + 1 FROM `yoshop_store_menu`),
       20, 'crm', '设为默认', '/flow.template/setDefault', 'setDefault', (SELECT `menu_id` FROM `yoshop_store_menu` WHERE `path` = '/app/flow/template' LIMIT 1), 80, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `yoshop_store_menu` WHERE `path` = '/flow.template/setDefault');

INSERT INTO `yoshop_store_menu`
(`menu_id`,`module`,`fun_type`,`name`,`path`,`action_mark`,`parent_id`,`sort`,`create_time`,`update_time`)
SELECT (SELECT IFNULL(MAX(`menu_id`), 0) + 1 FROM `yoshop_store_menu`),
       20, 'crm', '复制版本', '/flow.template/copyVersion', 'copyVersion', (SELECT `menu_id` FROM `yoshop_store_menu` WHERE `path` = '/app/flow/template' LIMIT 1), 90, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `yoshop_store_menu` WHERE `path` = '/flow.template/copyVersion');

INSERT INTO `yoshop_store_menu`
(`menu_id`,`module`,`fun_type`,`name`,`path`,`action_mark`,`parent_id`,`sort`,`create_time`,`update_time`)
SELECT (SELECT IFNULL(MAX(`menu_id`), 0) + 1 FROM `yoshop_store_menu`),
       20, 'crm', '删除模板', '/flow.template/remove', 'remove', (SELECT `menu_id` FROM `yoshop_store_menu` WHERE `path` = '/app/flow/template' LIMIT 1), 100, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `yoshop_store_menu` WHERE `path` = '/flow.template/remove');

INSERT INTO `yoshop_store_menu`
(`menu_id`,`module`,`fun_type`,`name`,`path`,`action_mark`,`parent_id`,`sort`,`create_time`,`update_time`)
SELECT (SELECT IFNULL(MAX(`menu_id`), 0) + 1 FROM `yoshop_store_menu`),
       20, 'crm', '待办列表', '/flow.task/lists', 'lists', (SELECT `menu_id` FROM `yoshop_store_menu` WHERE `path` = '/app/flow/task' LIMIT 1), 10, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `yoshop_store_menu` WHERE `path` = '/flow.task/lists');

INSERT INTO `yoshop_store_menu`
(`menu_id`,`module`,`fun_type`,`name`,`path`,`action_mark`,`parent_id`,`sort`,`create_time`,`update_time`)
SELECT (SELECT IFNULL(MAX(`menu_id`), 0) + 1 FROM `yoshop_store_menu`),
       20, 'crm', '待办数量', '/flow.task/count', 'count', (SELECT `menu_id` FROM `yoshop_store_menu` WHERE `path` = '/app/flow/task' LIMIT 1), 20, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `yoshop_store_menu` WHERE `path` = '/flow.task/count');

INSERT INTO `yoshop_store_menu`
(`menu_id`,`module`,`fun_type`,`name`,`path`,`action_mark`,`parent_id`,`sort`,`create_time`,`update_time`)
SELECT (SELECT IFNULL(MAX(`menu_id`), 0) + 1 FROM `yoshop_store_menu`),
       20, 'crm', '待办详情', '/flow.task/detail', 'detail', (SELECT `menu_id` FROM `yoshop_store_menu` WHERE `path` = '/app/flow/task' LIMIT 1), 30, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `yoshop_store_menu` WHERE `path` = '/flow.task/detail');

INSERT INTO `yoshop_store_menu`
(`menu_id`,`module`,`fun_type`,`name`,`path`,`action_mark`,`parent_id`,`sort`,`create_time`,`update_time`)
SELECT (SELECT IFNULL(MAX(`menu_id`), 0) + 1 FROM `yoshop_store_menu`),
       20, 'crm', '处理待办', '/flow.task/complete', 'complete', (SELECT `menu_id` FROM `yoshop_store_menu` WHERE `path` = '/app/flow/task' LIMIT 1), 40, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `yoshop_store_menu` WHERE `path` = '/flow.task/complete');

INSERT INTO `yoshop_store_menu`
(`menu_id`,`module`,`fun_type`,`name`,`path`,`action_mark`,`parent_id`,`sort`,`create_time`,`update_time`)
SELECT (SELECT IFNULL(MAX(`menu_id`), 0) + 1 FROM `yoshop_store_menu`),
       20, 'crm', '转交待办', '/flow.task/transfer', 'transfer', (SELECT `menu_id` FROM `yoshop_store_menu` WHERE `path` = '/app/flow/task' LIMIT 1), 50, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `yoshop_store_menu` WHERE `path` = '/flow.task/transfer');

INSERT INTO `yoshop_store_menu`
(`menu_id`,`module`,`fun_type`,`name`,`path`,`action_mark`,`parent_id`,`sort`,`create_time`,`update_time`)
SELECT (SELECT IFNULL(MAX(`menu_id`), 0) + 1 FROM `yoshop_store_menu`),
       20, 'crm', '加签', '/flow.task/addSign', 'addSign', (SELECT `menu_id` FROM `yoshop_store_menu` WHERE `path` = '/app/flow/task' LIMIT 1), 60, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `yoshop_store_menu` WHERE `path` = '/flow.task/addSign');

INSERT INTO `yoshop_store_menu`
(`menu_id`,`module`,`fun_type`,`name`,`path`,`action_mark`,`parent_id`,`sort`,`create_time`,`update_time`)
SELECT (SELECT IFNULL(MAX(`menu_id`), 0) + 1 FROM `yoshop_store_menu`),
       20, 'crm', '实例列表', '/flow.instance/lists', 'lists', (SELECT `menu_id` FROM `yoshop_store_menu` WHERE `path` = '/app/flow/instance' LIMIT 1), 10, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `yoshop_store_menu` WHERE `path` = '/flow.instance/lists');

INSERT INTO `yoshop_store_menu`
(`menu_id`,`module`,`fun_type`,`name`,`path`,`action_mark`,`parent_id`,`sort`,`create_time`,`update_time`)
SELECT (SELECT IFNULL(MAX(`menu_id`), 0) + 1 FROM `yoshop_store_menu`),
       20, 'crm', '实例详情', '/flow.instance/detail', 'detail', (SELECT `menu_id` FROM `yoshop_store_menu` WHERE `path` = '/app/flow/instance' LIMIT 1), 20, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `yoshop_store_menu` WHERE `path` = '/flow.instance/detail');

INSERT INTO `yoshop_store_menu`
(`menu_id`,`module`,`fun_type`,`name`,`path`,`action_mark`,`parent_id`,`sort`,`create_time`,`update_time`)
SELECT (SELECT IFNULL(MAX(`menu_id`), 0) + 1 FROM `yoshop_store_menu`),
       20, 'crm', '流程步骤', '/flow.instance/steps', 'steps', (SELECT `menu_id` FROM `yoshop_store_menu` WHERE `path` = '/app/flow/instance' LIMIT 1), 30, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `yoshop_store_menu` WHERE `path` = '/flow.instance/steps');

INSERT INTO `yoshop_store_menu`
(`menu_id`,`module`,`fun_type`,`name`,`path`,`action_mark`,`parent_id`,`sort`,`create_time`,`update_time`)
SELECT (SELECT IFNULL(MAX(`menu_id`), 0) + 1 FROM `yoshop_store_menu`),
       20, 'crm', '启动流程', '/flow.instance/start', 'start', (SELECT `menu_id` FROM `yoshop_store_menu` WHERE `path` = '/app/flow/instance' LIMIT 1), 40, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `yoshop_store_menu` WHERE `path` = '/flow.instance/start');

INSERT INTO `yoshop_store_menu`
(`menu_id`,`module`,`fun_type`,`name`,`path`,`action_mark`,`parent_id`,`sort`,`create_time`,`update_time`)
SELECT (SELECT IFNULL(MAX(`menu_id`), 0) + 1 FROM `yoshop_store_menu`),
       20, 'crm', '终止流程', '/flow.instance/terminate', 'terminate', (SELECT `menu_id` FROM `yoshop_store_menu` WHERE `path` = '/app/flow/instance' LIMIT 1), 50, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `yoshop_store_menu` WHERE `path` = '/flow.instance/terminate');

-- 超管绑定
INSERT INTO `yoshop_store_role_menu` (`role_id`,`menu_id`,`store_id`,`create_time`)
SELECT 1, m.`menu_id`, 0, UNIX_TIMESTAMP()
FROM `yoshop_store_menu` m
WHERE (m.`path` = '/app/flow' OR m.`path` LIKE '/app/flow/%' OR m.`path` LIKE '/flow.%')
  AND NOT EXISTS (
    SELECT 1 FROM `yoshop_store_role_menu` rm
    WHERE rm.`role_id` = 1 AND rm.`menu_id` = m.`menu_id` AND rm.`store_id` = 0
  );
