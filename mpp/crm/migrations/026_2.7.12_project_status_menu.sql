-- =============================================
-- CRM v2.7.12 — 项目状态操作权限
--
-- 项目手动「开始 / 完成 / 重新打开」需要独立权限点。
-- 幂等：菜单 INSERT IGNORE；角色绑定先删后插。
-- =============================================

INSERT IGNORE INTO `yoshop_store_menu`
(`menu_id`, `module`, `fun_type`, `name`, `path`, `action_mark`, `parent_id`, `sort`, `create_time`, `update_time`) VALUES
(20927, 20, 'crm', '开始项目', '/crm/project/start', 'start', 20910, 80, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20928, 20, 'crm', '完成项目', '/crm/project/complete', 'complete', 20910, 90, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20929, 20, 'crm', '重新打开项目', '/crm/project/reopen', 'reopen', 20910, 100, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

DELETE FROM `yoshop_store_role_menu`
WHERE `role_id` = 1 AND `store_id` = 0 AND `menu_id` IN (20927, 20928, 20929);

INSERT INTO `yoshop_store_role_menu` (`role_id`, `menu_id`, `store_id`)
SELECT 1, `menu_id`, 0 FROM `yoshop_store_menu` WHERE `menu_id` IN (20927, 20928, 20929);
