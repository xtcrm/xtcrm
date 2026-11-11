-- =============================================
-- CRM v2.10.3 — 一级菜单命名
--
-- 一级菜单去掉「管理」二字：客户 / 项目 / 销售 / 业绩。
-- 只改展示名，path 与 menu_id 不动，权限绑定不受影响。
-- 侧边栏结构由前端 asyncRoutes 决定，这里同步名称是为了后台角色/权限页显示一致。
--
-- 幂等：按 menu_id + 旧名称匹配，重复执行不会误改其他菜单。
-- =============================================

UPDATE `yoshop_store_menu` SET `name` = '客户', `update_time` = UNIX_TIMESTAMP()
WHERE `menu_id` = 20100 AND `name` = '客户管理';

UPDATE `yoshop_store_menu` SET `name` = '项目', `update_time` = UNIX_TIMESTAMP()
WHERE `menu_id` = 20900 AND `name` = '项目管理';

UPDATE `yoshop_store_menu` SET `name` = '销售', `update_time` = UNIX_TIMESTAMP()
WHERE `menu_id` = 20200 AND `name` = '销售管理';

UPDATE `yoshop_store_menu` SET `name` = '业绩', `update_time` = UNIX_TIMESTAMP()
WHERE `menu_id` = 21129 AND `name` = '业绩管理';
