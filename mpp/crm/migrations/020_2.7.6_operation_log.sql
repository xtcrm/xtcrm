-- =============================================
-- CRM v2.7.6 — 操作日志
-- =============================================

CREATE TABLE IF NOT EXISTS `yoshop_operation_log` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `store_id` int unsigned NOT NULL DEFAULT 0 COMMENT '租户ID',
  `user_id` int unsigned NOT NULL DEFAULT 0 COMMENT '操作人 store_user_id',
  `user_name` varchar(50) NOT NULL DEFAULT '' COMMENT '操作人姓名',
  `action` varchar(30) NOT NULL DEFAULT '' COMMENT 'login/logout/add/edit/delete/view',
  `module` varchar(30) NOT NULL DEFAULT 'crm' COMMENT 'crm/shop/admin',
  `target` varchar(50) NOT NULL DEFAULT '' COMMENT '操作对象 customer/order/...',
  `target_id` int unsigned NOT NULL DEFAULT 0 COMMENT '对象ID',
  `summary` varchar(500) NOT NULL DEFAULT '' COMMENT '摘要',
  `detail` text COMMENT 'JSON：变更数据',
  `ip` varchar(50) NOT NULL DEFAULT '' COMMENT 'IP',
  `user_agent` varchar(500) NOT NULL DEFAULT '' COMMENT 'UA',
  `create_time` int unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`store_id`,`user_id`,`create_time`),
  KEY `idx_target` (`store_id`,`target`,`target_id`),
  KEY `idx_action` (`store_id`,`action`,`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='操作日志';

-- 菜单：挂在 CRM设置 → 标签管理 下面
SET @crm_setting_id = (SELECT menu_id FROM yoshop_store_menu WHERE path='/crm/setting' AND module=10 LIMIT 1);
INSERT IGNORE INTO `yoshop_store_menu` (`menu_id`, `module`, `fun_type`, `name`, `path`, `action_mark`, `parent_id`, `sort`, `create_time`, `update_time`)
SELECT MAX(menu_id)+1, 10, 'crm', '操作日志', '/crm/setting/oplog', '', @crm_setting_id, 70, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM yoshop_store_menu;

SET @oplog_menu_id = (SELECT menu_id FROM yoshop_store_menu WHERE path='/crm/setting/oplog' AND module=10 LIMIT 1);

INSERT IGNORE INTO `yoshop_store_menu` (`menu_id`, `module`, `fun_type`, `name`, `path`, `action_mark`, `parent_id`, `sort`, `create_time`, `update_time`)
SELECT MAX(menu_id)+1, 20, 'crm', '查询操作日志', '/crm/oplog/lists', 'lists', @oplog_menu_id, 10, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM yoshop_store_menu;

-- 绑定管理员角色
INSERT IGNORE INTO `yoshop_store_role_menu` (`role_id`, `menu_id`, `store_id`)
SELECT 1, menu_id, 0 FROM `yoshop_store_menu` WHERE fun_type='crm' AND path LIKE '/crm/setting/oplog%' AND module=10;

INSERT IGNORE INTO `yoshop_store_role_menu` (`role_id`, `menu_id`, `store_id`)
SELECT 1, menu_id, 0 FROM `yoshop_store_menu` WHERE fun_type='crm' AND path LIKE '/crm/oplog/%';
