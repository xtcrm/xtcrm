-- ========================================
-- v2.3.1 客户管理 - 添加客户独立页面菜单
-- ========================================

-- 客户管理下新增"添加客户"侧边栏菜单项（module=10 显示菜单）
INSERT INTO `yoshop_store_menu` (`menu_id`, `name`, `path`, `parent_id`, `module`, `action_mark`, `sort`, `fun_type`)
VALUES (10743, '添加客户', '/crm/customer/create', 10307, 10, '', 105, 'crm');

-- 绑定到管理员角色（role_id=1）
INSERT INTO `yoshop_store_role_menu` (`role_id`, `menu_id`)
VALUES (1, 10743);
