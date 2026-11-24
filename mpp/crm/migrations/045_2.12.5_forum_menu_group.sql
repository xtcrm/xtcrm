-- =============================================
-- 论坛菜单改为独立分组
-- 工作台 → 论坛
--   我的积分（隐藏）
--   审核与举报（隐藏）
--   发布公告（操作）
--   审核论坛内容（操作）
-- 版本：2.12.5
-- =============================================

-- 21201 原本是「全部话题」，提升为论坛父菜单
UPDATE `yoshop_store_menu`
SET `name` = '论坛',
    `path` = '/forum/index',
    `parent_id` = 20000,
    `module` = 10,
    `sort` = 40,
    `action_mark` = '',
    `update_time` = UNIX_TIMESTAMP()
WHERE `menu_id` = 21201;

UPDATE `yoshop_store_menu`
SET `parent_id` = 21201, `sort` = 10, `update_time` = UNIX_TIMESTAMP()
WHERE `menu_id` = 21202;

UPDATE `yoshop_store_menu`
SET `parent_id` = 21201, `sort` = 20, `update_time` = UNIX_TIMESTAMP()
WHERE `menu_id` = 21203;

UPDATE `yoshop_store_menu`
SET `parent_id` = 21201, `sort` = 100, `update_time` = UNIX_TIMESTAMP()
WHERE `menu_id` = 21204;

UPDATE `yoshop_store_menu`
SET `parent_id` = 21201, `sort` = 110, `update_time` = UNIX_TIMESTAMP()
WHERE `menu_id` = 21205;
