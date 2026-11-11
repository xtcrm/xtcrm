-- ============================================================
-- CRM 联系人 + 跟进 菜单完整重建
-- 版本: 2.7.2
-- 原因：014_2.7.1 执行时联系人操作权限(20171-20176)未生效，
--       跟进操作(20191-20194)被错误放到 20171-20174。
--       此处彻底清理并严格按 014 定义的 ID 布局重建。
-- ============================================================

-- Step 1: 清空联系人和跟进相关的所有菜单与角色绑定
DELETE FROM yoshop_store_role_menu WHERE menu_id IN (20120, 20129, 20171, 20172, 20173, 20174, 20175, 20176, 20177, 20178, 20179, 20180);
DELETE FROM yoshop_store_menu WHERE menu_id IN (20120, 20129, 20171, 20172, 20173, 20174, 20175, 20176, 20177, 20178, 20179, 20180);

-- Step 2: 页面菜单 module=10
INSERT INTO yoshop_store_menu (menu_id, module, fun_type, name, path, action_mark, parent_id, sort, create_time, update_time) VALUES
(20120, 10, 'crm', '联系人管理', '/crm/contact/index', '', 20100, 90, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20129, 10, 'crm', '联系人详情', '/crm/contact/detail', 'hidden', 20100, 100, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- Step 3: 联系人操作 module=20 (20171-20176)
INSERT INTO yoshop_store_menu (menu_id, module, fun_type, name, path, action_mark, parent_id, sort, create_time, update_time) VALUES
(20171, 20, 'crm', '联系人列表', '/crm/contact/list', 'list', 20120, 100, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20172, 20, 'crm', '新增联系人', '/crm/contact/add', 'add', 20120, 110, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20173, 20, 'crm', '删除联系人', '/crm/contact/delete', 'delete', 20120, 120, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20174, 20, 'crm', '联系人详情', '/crm/contact/detail', 'detail', 20129, 100, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20175, 20, 'crm', '编辑联系人', '/crm/contact/edit', 'edit', 20129, 110, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20176, 20, 'crm', '解除绑定', '/crm/contact/unbind', 'unbind', 20129, 120, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- Step 4: 跟进操作 module=20 (20191-20194) — 从 20171-20174 迁回正确位置
INSERT INTO yoshop_store_menu (menu_id, module, fun_type, name, path, action_mark, parent_id, sort, create_time, update_time) VALUES
(20191, 20, 'crm', '跟进列表', '/crm/followup/lists', 'lists', 20101, 200, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20192, 20, 'crm', '新增跟进', '/crm/followup/add', 'add', 20101, 210, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20193, 20, 'crm', '编辑跟进', '/crm/followup/edit', 'edit', 20101, 220, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20194, 20, 'crm', '删除跟进', '/crm/followup/delete', 'delete', 20101, 230, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- Step 5: 角色绑定
-- 超管 role_id=1 绑定全部
INSERT INTO yoshop_store_role_menu (role_id, menu_id, store_id, create_time)
SELECT 1, menu_id, 0, UNIX_TIMESTAMP() FROM yoshop_store_menu
WHERE fun_type = 'crm' AND menu_id IN (20120, 20129, 20171, 20172, 20173, 20174, 20175, 20176, 20191, 20192, 20193, 20194);

-- 销售经理 role_id=10004 绑定页面菜单
INSERT INTO yoshop_store_role_menu (role_id, menu_id, store_id, create_time)
SELECT 10004, menu_id, 10001, UNIX_TIMESTAMP() FROM yoshop_store_menu
WHERE fun_type = 'crm' AND module = 10 AND menu_id IN (20120, 20129);
