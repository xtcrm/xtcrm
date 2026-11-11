-- =============================================
-- CRM v2.7.5 — 标签体系基建
-- =============================================

-- 1. 标签分组表
CREATE TABLE IF NOT EXISTS `yoshop_crm_tag_group` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `store_id` int unsigned NOT NULL DEFAULT 0 COMMENT '租户ID',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '分组名称',
  `target_type` enum('customer','contact') NOT NULL DEFAULT 'customer' COMMENT '适用对象:customer=客户,contact=联系人',
  `sort_order` int unsigned NOT NULL DEFAULT 100 COMMENT '排序',
  `create_time` int unsigned NOT NULL DEFAULT 0,
  `update_time` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_store_target` (`store_id`, `target_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='CRM标签分组';

-- 2. 标签定义表
CREATE TABLE IF NOT EXISTS `yoshop_crm_tag` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `store_id` int unsigned NOT NULL DEFAULT 0 COMMENT '租户ID',
  `group_id` int unsigned NOT NULL DEFAULT 0 COMMENT '分组ID(0=未分组)',
  `target_type` enum('customer','contact') NOT NULL DEFAULT 'customer' COMMENT '适用对象',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '标签名称',
  `color` varchar(20) NOT NULL DEFAULT 'blue' COMMENT '标签颜色',
  `sort_order` int unsigned NOT NULL DEFAULT 100 COMMENT '排序',
  `create_time` int unsigned NOT NULL DEFAULT 0,
  `update_time` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_store_name_type` (`store_id`, `name`, `target_type`),
  KEY `idx_store_target` (`store_id`, `target_type`),
  KEY `idx_group` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='CRM标签定义';

-- 3. 客户标签关联表
CREATE TABLE IF NOT EXISTS `yoshop_crm_customer_tag` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int unsigned NOT NULL DEFAULT 0 COMMENT '客户ID',
  `tag_id` int unsigned NOT NULL DEFAULT 0 COMMENT '标签ID',
  `create_time` int unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_customer_tag` (`customer_id`, `tag_id`),
  KEY `idx_tag` (`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='客户-标签关联';

-- 4. 联系人标签关联表
CREATE TABLE IF NOT EXISTS `yoshop_crm_contact_tag` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `contact_id` int unsigned NOT NULL DEFAULT 0 COMMENT '联系人ID',
  `tag_id` int unsigned NOT NULL DEFAULT 0 COMMENT '标签ID',
  `create_time` int unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_contact_tag` (`contact_id`, `tag_id`),
  KEY `idx_tag` (`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='联系人-标签关联';

-- ===== 菜单 =====

-- 页面菜单：挂在 CRM设置 下
SET @crm_setting_id = (SELECT menu_id FROM yoshop_store_menu WHERE path='/crm/setting' AND module=10 LIMIT 1);

INSERT IGNORE INTO `yoshop_store_menu` (`menu_id`, `module`, `fun_type`, `name`, `path`, `action_mark`, `parent_id`, `sort`, `create_time`, `update_time`)
SELECT MAX(menu_id)+1, 10, 'crm', '标签管理', '/crm/setting/tag', '', @crm_setting_id, 60, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM yoshop_store_menu;

-- 操作权限：挂在 标签管理 页面下
SET @tag_menu_id = (SELECT menu_id FROM yoshop_store_menu WHERE path='/crm/setting/tag' AND module=10 LIMIT 1);

-- 分组操作权限
INSERT IGNORE INTO `yoshop_store_menu` (`menu_id`, `module`, `fun_type`, `name`, `path`, `action_mark`, `parent_id`, `sort`, `create_time`, `update_time`)
SELECT MAX(menu_id)+1, 20, 'crm', '查询标签分组', '/crm/tag/groupLists', 'groupLists', @tag_menu_id, 10, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM yoshop_store_menu;

INSERT IGNORE INTO `yoshop_store_menu` (`menu_id`, `module`, `fun_type`, `name`, `path`, `action_mark`, `parent_id`, `sort`, `create_time`, `update_time`)
SELECT MAX(menu_id)+1, 20, 'crm', '新增标签分组', '/crm/tag/groupAdd', 'groupAdd', @tag_menu_id, 20, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM yoshop_store_menu;

INSERT IGNORE INTO `yoshop_store_menu` (`menu_id`, `module`, `fun_type`, `name`, `path`, `action_mark`, `parent_id`, `sort`, `create_time`, `update_time`)
SELECT MAX(menu_id)+1, 20, 'crm', '编辑标签分组', '/crm/tag/groupEdit', 'groupEdit', @tag_menu_id, 30, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM yoshop_store_menu;

INSERT IGNORE INTO `yoshop_store_menu` (`menu_id`, `module`, `fun_type`, `name`, `path`, `action_mark`, `parent_id`, `sort`, `create_time`, `update_time`)
SELECT MAX(menu_id)+1, 20, 'crm', '删除标签分组', '/crm/tag/groupDelete', 'groupDelete', @tag_menu_id, 40, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM yoshop_store_menu;

-- 标签操作权限
INSERT IGNORE INTO `yoshop_store_menu` (`menu_id`, `module`, `fun_type`, `name`, `path`, `action_mark`, `parent_id`, `sort`, `create_time`, `update_time`)
SELECT MAX(menu_id)+1, 20, 'crm', '查询标签', '/crm/tag/lists', 'lists', @tag_menu_id, 50, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM yoshop_store_menu;

INSERT IGNORE INTO `yoshop_store_menu` (`menu_id`, `module`, `fun_type`, `name`, `path`, `action_mark`, `parent_id`, `sort`, `create_time`, `update_time`)
SELECT MAX(menu_id)+1, 20, 'crm', '新增标签', '/crm/tag/add', 'add', @tag_menu_id, 60, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM yoshop_store_menu;

INSERT IGNORE INTO `yoshop_store_menu` (`menu_id`, `module`, `fun_type`, `name`, `path`, `action_mark`, `parent_id`, `sort`, `create_time`, `update_time`)
SELECT MAX(menu_id)+1, 20, 'crm', '编辑标签', '/crm/tag/edit', 'edit', @tag_menu_id, 70, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM yoshop_store_menu;

INSERT IGNORE INTO `yoshop_store_menu` (`menu_id`, `module`, `fun_type`, `name`, `path`, `action_mark`, `parent_id`, `sort`, `create_time`, `update_time`)
SELECT MAX(menu_id)+1, 20, 'crm', '删除标签', '/crm/tag/delete', 'delete', @tag_menu_id, 80, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM yoshop_store_menu;

-- 标签分配操作权限
INSERT IGNORE INTO `yoshop_store_menu` (`menu_id`, `module`, `fun_type`, `name`, `path`, `action_mark`, `parent_id`, `sort`, `create_time`, `update_time`)
SELECT MAX(menu_id)+1, 20, 'crm', '客户标签分配', '/crm/tag/setCustomerTags', 'setCustomerTags', @tag_menu_id, 90, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM yoshop_store_menu;

INSERT IGNORE INTO `yoshop_store_menu` (`menu_id`, `module`, `fun_type`, `name`, `path`, `action_mark`, `parent_id`, `sort`, `create_time`, `update_time`)
SELECT MAX(menu_id)+1, 20, 'crm', '联系人标签分配', '/crm/tag/setContactTags', 'setContactTags', @tag_menu_id, 100, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM yoshop_store_menu;

-- ===== 角色绑定 =====

INSERT IGNORE INTO `yoshop_store_role_menu` (`role_id`, `menu_id`, `store_id`)
SELECT 1, menu_id, 0 FROM `yoshop_store_menu` WHERE fun_type='crm' AND path LIKE '/crm/setting/tag%' AND module=10;

INSERT IGNORE INTO `yoshop_store_role_menu` (`role_id`, `menu_id`, `store_id`)
SELECT 1, menu_id, 0 FROM `yoshop_store_menu` WHERE fun_type='crm' AND path LIKE '/crm/tag/%';
