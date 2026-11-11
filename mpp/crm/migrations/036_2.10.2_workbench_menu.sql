-- =============================================
-- CRM v2.10.2 — 岗位工作台菜单
--
-- 工作台（20000）下新增三个岗位入口：客服 / 交付 / 审核。
-- 这些是页面菜单（module=10），数据可见性由后端按责任位判定，
-- 菜单只决定能不能进页面。
--
-- 幂等：INSERT IGNORE 菜单；role_menu 先删后插。
-- =============================================

INSERT IGNORE INTO `yoshop_store_menu`
(`menu_id`, `module`, `fun_type`, `name`, `path`, `action_mark`, `parent_id`, `sort`, `create_time`, `update_time`) VALUES
(20002, 10, 'crm', '我的服务客户', '/crm/workbench/cs', '', 20000, 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20003, 10, 'crm', '我的交付', '/crm/workbench/delivery', '', 20000, 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20004, 10, 'crm', '待审队列', '/crm/workbench/review', '', 20000, 4, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

DELETE FROM `yoshop_store_role_menu`
WHERE `menu_id` IN (20002, 20003, 20004) AND `role_id` IN (10004, 10008);

INSERT INTO `yoshop_store_role_menu` (`role_id`, `menu_id`, `store_id`)
SELECT r.`role_id`, m.`menu_id`, r.`store_id`
FROM `yoshop_store_role` r
JOIN `yoshop_store_menu` m ON m.`menu_id` IN (20002, 20003, 20004)
WHERE r.`role_id` IN (10004, 10008);
