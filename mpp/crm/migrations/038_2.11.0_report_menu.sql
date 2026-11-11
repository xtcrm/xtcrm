-- =============================================
-- CRM v2.11.0 — 报表菜单
--
-- 一级菜单「报表」，位置在「业绩」与「知识库」之间。
-- 报表全部只读，统一一个权限点「查看报表」。
-- 菜单 ID 实测基线：MAX(menu_id)=21137。
-- =============================================

INSERT IGNORE INTO `yoshop_store_menu`
(`menu_id`, `module`, `fun_type`, `name`, `path`, `action_mark`, `parent_id`, `sort`, `create_time`, `update_time`) VALUES
(21138, 10, 'crm', '报表', '/crm/report/index', '', 0, 37, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(21139, 20, 'crm', '查看报表', '/crm/report/view', 'view', 21138, 10, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

DELETE FROM `yoshop_store_role_menu`
WHERE `menu_id` IN (21138, 21139) AND `role_id` IN (10004, 10008);

INSERT INTO `yoshop_store_role_menu` (`role_id`, `menu_id`, `store_id`)
SELECT r.`role_id`, m.`menu_id`, r.`store_id`
FROM `yoshop_store_role` r
JOIN `yoshop_store_menu` m ON m.`menu_id` IN (21138, 21139)
WHERE r.`role_id` IN (10004, 10008);
