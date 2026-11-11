-- ============================================================
-- 开源版：清理闭源 skill 的菜单残留
--
-- CRM 的历史迁移里写了获客账本、官网管理、工单、OpenClaw 等菜单，
-- 这些 skill 不在开源范围内。本迁移在所有 CRM 迁移之后执行，把它们删掉，
-- 避免开源版后台出现点了 404 的死菜单。
--
-- 只删 fun_type='crm' 的菜单；发票（invoice）不在删除范围内。
-- ============================================================

DELETE FROM yoshop_store_role_menu WHERE menu_id IN (
    SELECT menu_id FROM yoshop_store_menu
    WHERE fun_type = 'crm' AND (
        path LIKE '/app/leadledger%'
        OR path LIKE '/app/website%'
        OR path LIKE '/app/workorder%'
        OR path LIKE '/app/studio%'
        OR path LIKE '/app/entquery%'
        OR path = '/ai'
        OR path LIKE '/ai/%'
    )
);

DELETE FROM yoshop_store_menu
WHERE fun_type = 'crm' AND (
    path LIKE '/app/leadledger%'
    OR path LIKE '/app/website%'
    OR path LIKE '/app/workorder%'
    OR path LIKE '/app/studio%'
    OR path LIKE '/app/entquery%'
    OR path = '/ai'
    OR path LIKE '/ai/%'
);
