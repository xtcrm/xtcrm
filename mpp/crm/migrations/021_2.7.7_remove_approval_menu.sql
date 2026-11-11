-- =============================================
-- CRM v2.7.7 — 下掉「审批规则」菜单
--
-- 审批工作流一直没有实现：approval_trigger_amount / approval_levels 两个配置
-- 只被设置页读写，没有任何业务代码消费。设置页里的 tab 已移除，这里清掉历史上
-- 插入的菜单行，避免新环境装出来还挂着一个点进去没有任何效果的入口。
--
-- 配置字段本身保留（crm_setting 里已有的 ai 行不动），将来真做审批流可以直接用。
-- 按 path 定位、不写死 menu_id；重复执行无副作用。
-- =============================================

-- 先删角色绑定，再删菜单
DELETE rm FROM `yoshop_store_role_menu` rm
INNER JOIN `yoshop_store_menu` m ON m.menu_id = rm.menu_id
WHERE m.fun_type = 'crm' AND m.path = '/crm/setting?tab=approval';

DELETE FROM `yoshop_store_menu`
WHERE fun_type = 'crm' AND path = '/crm/setting?tab=approval';
