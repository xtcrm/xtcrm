-- =============================================
-- 流程日常入口移到「工作台」菜单下
-- 我的待办： /app/flow/task      → /index/flow/task
-- 我的流程： /app/flow/instance  → /index/flow/instance
-- 流程模板/设计器保留在「应用 → 流程中心」
-- 版本：2.12.1
-- =============================================

SET @wb_menu := (SELECT `menu_id` FROM `yoshop_store_menu` WHERE `path` = '/index' LIMIT 1);

UPDATE `yoshop_store_menu`
SET `path` = '/index/flow/task',
    `name` = '我的待办',
    `parent_id` = IFNULL(@wb_menu, `parent_id`),
    `sort` = 20,
    `update_time` = UNIX_TIMESTAMP()
WHERE `path` = '/app/flow/task';

UPDATE `yoshop_store_menu`
SET `path` = '/index/flow/instance',
    `name` = '我的流程',
    `parent_id` = IFNULL(@wb_menu, `parent_id`),
    `sort` = 21,
    `update_time` = UNIX_TIMESTAMP()
WHERE `path` = '/app/flow/instance';
