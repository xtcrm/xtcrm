-- =============================================
-- CRM v2.7.10 — 服务目录菜单
--
-- 服务品类不是固定枚举：每个租户在「项目管理 → 服务目录」自己维护。
-- 数据仍存 workorder_type（与工单类型同一份），本迁移只加 CRM 入口与权限。
-- 幂等：菜单 INSERT IGNORE；角色绑定先删后插。
-- =============================================

INSERT IGNORE INTO `yoshop_store_menu`
(`menu_id`, `module`, `fun_type`, `name`, `path`, `action_mark`, `parent_id`, `sort`, `create_time`, `update_time`) VALUES
(20912, 10, 'crm', '服务目录', '/crm/project/catalog', '', 20900, 30, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

INSERT IGNORE INTO `yoshop_store_menu`
(`menu_id`, `module`, `fun_type`, `name`, `path`, `action_mark`, `parent_id`, `sort`, `create_time`, `update_time`) VALUES
(20940, 20, 'crm', '查询服务品类', '/crm/catalog/lists', 'lists', 20912, 10, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20941, 20, 'crm', '新增服务品类', '/crm/catalog/add', 'add', 20912, 20, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20942, 20, 'crm', '编辑服务品类', '/crm/catalog/edit', 'edit', 20912, 30, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20943, 20, 'crm', '删除服务品类', '/crm/catalog/delete', 'delete', 20912, 40, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20944, 20, 'crm', '启停服务品类', '/crm/catalog/setStatus', 'setStatus', 20912, 50, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

DELETE FROM `yoshop_store_role_menu`
WHERE `role_id` = 1 AND `store_id` = 0 AND `menu_id` IN (20912, 20940, 20941, 20942, 20943, 20944);

INSERT INTO `yoshop_store_role_menu` (`role_id`, `menu_id`, `store_id`)
SELECT 1, `menu_id`, 0 FROM `yoshop_store_menu` WHERE `menu_id` IN (20912, 20940, 20941, 20942, 20943, 20944);
