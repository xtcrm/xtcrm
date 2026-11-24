-- =============================================
-- CRM v2.13.0 — 客户批量转让
--
-- 1) 客户菜单下增加「客户转让」页面（module=10）
-- 2) 增加操作权限「批量转让客户」（module=20）
-- 3) 初始授权：销售经理(10004)、总经办(10008)
--    其他角色后续在「角色管理」里勾选即可，无需改代码。
--
-- 菜单 ID 基线：MAX(menu_id)=21303（2026-11-18 实测）
-- =============================================

INSERT IGNORE INTO `yoshop_store_menu`
(`menu_id`, `module`, `fun_type`, `name`, `path`, `action_mark`, `parent_id`, `sort`, `create_time`, `update_time`) VALUES
(21304, 10, 'crm', '客户转让', '/crm/customer/transfer', '', 20100, 15, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(21305, 20, 'crm', '批量转让客户', '/crm/customer/batchTransfer', 'batchTransfer', 21304, 10, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

DELETE FROM `yoshop_store_role_menu`
WHERE `menu_id` IN (21304, 21305) AND `role_id` IN (10004, 10008);

INSERT INTO `yoshop_store_role_menu` (`role_id`, `menu_id`, `store_id`)
SELECT r.`role_id`, m.`menu_id`, r.`store_id`
FROM `yoshop_store_role` r
JOIN `yoshop_store_menu` m ON m.`menu_id` IN (21304, 21305)
WHERE r.`role_id` IN (10004, 10008);
