-- =============================================
-- CRM v2.6.1 — 需求规则引擎
-- =============================================

-- 需求类型定义表
CREATE TABLE IF NOT EXISTS `yoshop_crm_demand_rule` (
  `id` int NOT NULL AUTO_INCREMENT,
  `store_id` int NOT NULL DEFAULT 0 COMMENT '租户ID',
  `rule_key` varchar(50) NOT NULL COMMENT '规则标识',
  `rule_name` varchar(100) NOT NULL COMMENT '显示名',
  `conditions` text COMMENT '条件JSON：[{field,op,value}]',
  `logic` varchar(10) DEFAULT 'all' COMMENT 'all/any',
  `priority` tinyint NOT NULL DEFAULT 2 COMMENT '1高 2中 3低',
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '1启用 0停用',
  `min_confidence` decimal(3,2) DEFAULT '0.50',
  `is_builtin` tinyint NOT NULL DEFAULT 0,
  `create_time` int DEFAULT NULL,
  `update_time` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_store_rule` (`store_id`, `rule_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='CRM 需求类型定义';

-- 需求实例表
CREATE TABLE IF NOT EXISTS `yoshop_crm_demand` (
  `id` int NOT NULL AUTO_INCREMENT,
  `store_id` int NOT NULL DEFAULT 0 COMMENT '租户ID',
  `customer_id` int NOT NULL COMMENT '客户ID',
  `rule_key` varchar(50) NOT NULL COMMENT '需求类型',
  `source` varchar(20) NOT NULL DEFAULT 'rule' COMMENT 'rule/ai/manual',
  `confidence` decimal(3,2) DEFAULT NULL,
  `reason` varchar(500) DEFAULT '' COMMENT '命中原因',
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '1活跃 2已跟进 3已忽略 4已转化 5已过期',
  `owner_user_id` int DEFAULT NULL COMMENT '跟进负责人',
  `create_time` int DEFAULT NULL,
  `update_time` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `uk_customer_rule` (`store_id`, `customer_id`, `rule_key`),
  KEY `idx_owner` (`store_id`, `owner_user_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='CRM 需求实例';

-- 客户表预埋 AI 信号列（Phase 3 启用，幂等：已存在则跳过）
SET @exists1 = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='yoshop_crm_customer' AND COLUMN_NAME='ai_signals');
SET @stmt1 = IF(@exists1 = 0, 'ALTER TABLE `yoshop_crm_customer` ADD COLUMN `ai_signals` TEXT COMMENT ''AI信号提取结果JSON''', 'SELECT 1');
PREPARE s1 FROM @stmt1;
EXECUTE s1;
DEALLOCATE PREPARE s1;

SET @exists2 = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='yoshop_crm_customer' AND COLUMN_NAME='ai_signals_time');
SET @stmt2 = IF(@exists2 = 0, 'ALTER TABLE `yoshop_crm_customer` ADD COLUMN `ai_signals_time` INT DEFAULT NULL COMMENT ''信号提取时间戳''', 'SELECT 1');
PREPARE s2 FROM @stmt2;
EXECUTE s2;
DEALLOCATE PREPARE s2;

-- ===== 菜单层级：系统设置 → CRM设置 → 需求规则 =====

-- 1. CRM设置（module=10，如果 007 已建则跳过）
INSERT IGNORE INTO `yoshop_store_menu` (`menu_id`, `module`, `fun_type`, `name`, `path`, `action_mark`, `parent_id`, `sort`, `create_time`, `update_time`)
SELECT MAX(menu_id)+1, 10, 'crm', 'CRM设置', '/crm/setting', '', 20080, 150, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM yoshop_store_menu;

-- 2. 需求规则（module=10 可见菜单，挂在 CRM设置 下）
SET @crm_setting_id = (SELECT menu_id FROM yoshop_store_menu WHERE path='/crm/setting' AND module=10 AND fun_type='crm' LIMIT 1);
INSERT IGNORE INTO `yoshop_store_menu` (`menu_id`, `module`, `fun_type`, `name`, `path`, `action_mark`, `parent_id`, `sort`, `create_time`, `update_time`)
SELECT MAX(menu_id)+1, 10, 'crm', '需求规则', '/crm/setting/demandRule', '', @crm_setting_id, 20, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM yoshop_store_menu;

-- 3. 操作权限（module=20）挂在 需求规则 下
SET @dr_menu_id = (SELECT menu_id FROM yoshop_store_menu WHERE path='/crm/setting/demandRule' AND module=10 LIMIT 1);

INSERT IGNORE INTO `yoshop_store_menu` (`menu_id`, `module`, `fun_type`, `name`, `path`, `action_mark`, `parent_id`, `sort`, `create_time`, `update_time`)
SELECT MAX(menu_id)+1, 20, 'crm', '查询需求规则', '/crm/demandRule/lists', 'lists', @dr_menu_id, 10, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM yoshop_store_menu;

INSERT IGNORE INTO `yoshop_store_menu` (`menu_id`, `module`, `fun_type`, `name`, `path`, `action_mark`, `parent_id`, `sort`, `create_time`, `update_time`)
SELECT MAX(menu_id)+1, 20, 'crm', '启用停用需求规则', '/crm/demandRule/setStatus', 'setStatus', @dr_menu_id, 20, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM yoshop_store_menu;

INSERT IGNORE INTO `yoshop_store_menu` (`menu_id`, `module`, `fun_type`, `name`, `path`, `action_mark`, `parent_id`, `sort`, `create_time`, `update_time`)
SELECT MAX(menu_id)+1, 20, 'crm', '查询客户需求', '/crm/demand/customerDemands', 'lists', @dr_menu_id, 30, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM yoshop_store_menu;

INSERT IGNORE INTO `yoshop_store_menu` (`menu_id`, `module`, `fun_type`, `name`, `path`, `action_mark`, `parent_id`, `sort`, `create_time`, `update_time`)
SELECT MAX(menu_id)+1, 20, 'crm', '忽略需求', '/crm/demand/dismiss', 'dismiss', @dr_menu_id, 40, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM yoshop_store_menu;

INSERT IGNORE INTO `yoshop_store_menu` (`menu_id`, `module`, `fun_type`, `name`, `path`, `action_mark`, `parent_id`, `sort`, `create_time`, `update_time`)
SELECT MAX(menu_id)+1, 20, 'crm', '更新需求状态', '/crm/demand/setStatus', 'setStatus', @dr_menu_id, 50, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM yoshop_store_menu;

INSERT IGNORE INTO `yoshop_store_menu` (`menu_id`, `module`, `fun_type`, `name`, `path`, `action_mark`, `parent_id`, `sort`, `create_time`, `update_time`)
SELECT MAX(menu_id)+1, 20, 'crm', '编辑需求规则', '/crm/demandRule/edit', 'edit', @dr_menu_id, 25, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM yoshop_store_menu;

INSERT IGNORE INTO `yoshop_store_menu` (`menu_id`, `module`, `fun_type`, `name`, `path`, `action_mark`, `parent_id`, `sort`, `create_time`, `update_time`)
SELECT MAX(menu_id)+1, 20, 'crm', '删除需求规则', '/crm/demandRule/delete', 'delete', @dr_menu_id, 26, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM yoshop_store_menu;

-- 4. 绑定管理员角色
INSERT IGNORE INTO `yoshop_store_role_menu` (`role_id`, `menu_id`, `store_id`)
SELECT 1, menu_id, 0 FROM `yoshop_store_menu` WHERE fun_type='crm' AND path LIKE '/crm/setting%' AND module=10;
INSERT IGNORE INTO `yoshop_store_role_menu` (`role_id`, `menu_id`, `store_id`)
SELECT 1, menu_id, 0 FROM `yoshop_store_menu` WHERE fun_type='crm' AND path LIKE '/crm/demand%';
