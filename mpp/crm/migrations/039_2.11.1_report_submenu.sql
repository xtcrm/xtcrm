-- =============================================
-- CRM v2.11.1 — 报表子菜单
--
-- 报表改为二级结构：经营总览 / 销售 / 客服 / 交付 / 财务。
-- 21138 由页面菜单改为分组父节点，子菜单 21140–21144。
-- =============================================

UPDATE `yoshop_store_menu` SET `path` = '/crm/report', `action_mark` = '', `update_time` = UNIX_TIMESTAMP()
WHERE `menu_id` = 21138;

INSERT IGNORE INTO `yoshop_store_menu`
(`menu_id`, `module`, `fun_type`, `name`, `path`, `action_mark`, `parent_id`, `sort`, `create_time`, `update_time`) VALUES
(21140, 10, 'crm', '经营总览', '/crm/report/overview', '', 21138, 10, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(21141, 10, 'crm', '销售报表', '/crm/report/sales', '', 21138, 20, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(21142, 10, 'crm', '客服报表', '/crm/report/cs', '', 21138, 30, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(21143, 10, 'crm', '交付报表', '/crm/report/delivery', '', 21138, 40, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(21144, 10, 'crm', '财务报表', '/crm/report/finance', '', 21138, 50, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

DELETE FROM `yoshop_store_role_menu`
WHERE `menu_id` IN (21140,21141,21142,21143,21144) AND `role_id` IN (10004, 10008);

INSERT INTO `yoshop_store_role_menu` (`role_id`, `menu_id`, `store_id`)
SELECT r.`role_id`, m.`menu_id`, r.`store_id`
FROM `yoshop_store_role` r
JOIN `yoshop_store_menu` m ON m.`menu_id` IN (21140,21141,21142,21143,21144)
WHERE r.`role_id` IN (10004, 10008);
