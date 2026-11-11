-- =============================================
-- CRM v2.6.0 — 自定义字段基建
-- =============================================

-- 自定义字段定义表
CREATE TABLE IF NOT EXISTS `yoshop_crm_custom_field` (
  `id` int NOT NULL AUTO_INCREMENT,
  `store_id` int NOT NULL DEFAULT 0 COMMENT '租户ID',
  `entity` varchar(20) NOT NULL DEFAULT 'customer' COMMENT '实体：customer/lead/contact',
  `field_key` varchar(50) NOT NULL COMMENT '字段标识（英文）',
  `field_label` varchar(50) NOT NULL COMMENT '显示名称',
  `field_type` varchar(20) NOT NULL DEFAULT 'text' COMMENT 'text/number/date/select/switch',
  `options` text COMMENT 'select选项JSON',
  `placeholder` varchar(100) DEFAULT '' COMMENT '输入提示',
  `is_required` tinyint NOT NULL DEFAULT 0 COMMENT '是否必填',
  `show_in_list` tinyint NOT NULL DEFAULT 0 COMMENT '是否列表显示',
  `sort` int NOT NULL DEFAULT 100 COMMENT '排序',
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '1启用 0停用',
  `create_time` int DEFAULT NULL,
  `update_time` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_store_entity_key` (`store_id`, `entity`, `field_key`),
  KEY `idx_store_entity` (`store_id`, `entity`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='CRM 自定义字段定义';

-- 客户表加扩展列（幂等：已存在则跳过）
SET @exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='yoshop_crm_customer' AND COLUMN_NAME='extra');
SET @stmt = IF(@exists = 0, 'ALTER TABLE `yoshop_crm_customer` ADD COLUMN `extra` TEXT COMMENT ''自定义字段值JSON''', 'SELECT 1');
PREPARE s FROM @stmt;
EXECUTE s;
DEALLOCATE PREPARE s;

-- ===== 菜单层级：系统设置 → CRM设置 → 自定义字段 =====

-- 1. CRM设置（module=10 可见菜单，挂在系统设置 20080 下）
INSERT IGNORE INTO `yoshop_store_menu` (`menu_id`, `module`, `fun_type`, `name`, `path`, `action_mark`, `parent_id`, `sort`, `create_time`, `update_time`)
SELECT MAX(menu_id)+1, 10, 'crm', 'CRM设置', '/crm/setting', '', 20080, 150, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM yoshop_store_menu;

-- 2. 自定义字段（module=10 可见菜单，挂在 CRM设置 下）
SET @crm_setting_id = (SELECT menu_id FROM yoshop_store_menu WHERE path='/crm/setting' AND module=10 AND fun_type='crm' LIMIT 1);
INSERT IGNORE INTO `yoshop_store_menu` (`menu_id`, `module`, `fun_type`, `name`, `path`, `action_mark`, `parent_id`, `sort`, `create_time`, `update_time`)
SELECT MAX(menu_id)+1, 10, 'crm', '自定义字段', '/crm/setting/customField', '', @crm_setting_id, 10, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM yoshop_store_menu;

-- 3. 操作权限（module=20）挂在 自定义字段 下
SET @cf_menu_id = (SELECT menu_id FROM yoshop_store_menu WHERE path='/crm/setting/customField' AND module=10 LIMIT 1);

INSERT IGNORE INTO `yoshop_store_menu` (`menu_id`, `module`, `fun_type`, `name`, `path`, `action_mark`, `parent_id`, `sort`, `create_time`, `update_time`)
SELECT MAX(menu_id)+1, 20, 'crm', '查询自定义字段', '/crm/customField/lists', 'lists', @cf_menu_id, 10, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM yoshop_store_menu;

INSERT IGNORE INTO `yoshop_store_menu` (`menu_id`, `module`, `fun_type`, `name`, `path`, `action_mark`, `parent_id`, `sort`, `create_time`, `update_time`)
SELECT MAX(menu_id)+1, 20, 'crm', '新增自定义字段', '/crm/customField/add', 'add', @cf_menu_id, 20, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM yoshop_store_menu;

INSERT IGNORE INTO `yoshop_store_menu` (`menu_id`, `module`, `fun_type`, `name`, `path`, `action_mark`, `parent_id`, `sort`, `create_time`, `update_time`)
SELECT MAX(menu_id)+1, 20, 'crm', '编辑自定义字段', '/crm/customField/edit', 'edit', @cf_menu_id, 30, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM yoshop_store_menu;

INSERT IGNORE INTO `yoshop_store_menu` (`menu_id`, `module`, `fun_type`, `name`, `path`, `action_mark`, `parent_id`, `sort`, `create_time`, `update_time`)
SELECT MAX(menu_id)+1, 20, 'crm', '删除自定义字段', '/crm/customField/delete', 'delete', @cf_menu_id, 40, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM yoshop_store_menu;

INSERT IGNORE INTO `yoshop_store_menu` (`menu_id`, `module`, `fun_type`, `name`, `path`, `action_mark`, `parent_id`, `sort`, `create_time`, `update_time`)
SELECT MAX(menu_id)+1, 20, 'crm', '启用/停用字段', '/crm/customField/setStatus', 'setStatus', @cf_menu_id, 50, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM yoshop_store_menu;

-- 4. 绑定管理员角色
INSERT IGNORE INTO `yoshop_store_role_menu` (`role_id`, `menu_id`, `store_id`)
SELECT 1, menu_id, 0 FROM `yoshop_store_menu` WHERE fun_type='crm' AND path LIKE '/crm/setting%' AND module=10;
INSERT IGNORE INTO `yoshop_store_role_menu` (`role_id`, `menu_id`, `store_id`)
SELECT 1, menu_id, 0 FROM `yoshop_store_menu` WHERE fun_type='crm' AND path LIKE '/crm/customField%';
