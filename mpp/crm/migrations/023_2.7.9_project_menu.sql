-- =============================================
-- CRM v2.7.9 — 项目 / 需求菜单与权限
--
-- 菜单 ID 段：20900–20949（本库空闲段，20800 为 AI 影音，21000 起为应用中心）
-- 显示菜单 module=10；操作权限 module=20。
-- 幂等：INSERT IGNORE，重复执行不产生重复行。
-- =============================================

INSERT IGNORE INTO `yoshop_store_menu`
(`menu_id`, `module`, `fun_type`, `name`, `path`, `action_mark`, `parent_id`, `sort`, `create_time`, `update_time`) VALUES
(20900, 10, 'crm', '项目管理', '/crm/project', '', 0, 25, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20910, 10, 'crm', '项目列表', '/crm/project/index', '', 20900, 10, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20911, 10, 'crm', '需求池', '/crm/intent/index', '', 20900, 20, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

INSERT IGNORE INTO `yoshop_store_menu`
(`menu_id`, `module`, `fun_type`, `name`, `path`, `action_mark`, `parent_id`, `sort`, `create_time`, `update_time`) VALUES
(20920, 20, 'crm', '查询项目', '/crm/project/lists', 'lists', 20910, 10, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20921, 20, 'crm', '项目详情', '/crm/project/detail', 'detail', 20910, 20, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20922, 20, 'crm', '新增项目', '/crm/project/add', 'add', 20910, 30, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20923, 20, 'crm', '编辑项目', '/crm/project/edit', 'edit', 20910, 40, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20924, 20, 'crm', '取消项目', '/crm/project/cancel', 'cancel', 20910, 50, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20925, 20, 'crm', '归档项目', '/crm/project/archive', 'archive', 20910, 60, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20926, 20, 'crm', '管理参与人', '/crm/project/participants', 'participants', 20910, 70, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20930, 20, 'crm', '查询需求', '/crm/intent/lists', 'lists', 20911, 10, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20931, 20, 'crm', '需求详情', '/crm/intent/detail', 'detail', 20911, 20, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20932, 20, 'crm', '新增需求', '/crm/intent/add', 'add', 20911, 30, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20933, 20, 'crm', '编辑需求', '/crm/intent/edit', 'edit', 20911, 40, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20934, 20, 'crm', '指派承接人', '/crm/intent/assign', 'assign', 20911, 50, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20935, 20, 'crm', '关闭/搁置需求', '/crm/intent/close', 'close', 20911, 60, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20936, 20, 'crm', '确认成交', '/crm/intent/confirm', 'confirm', 20911, 70, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 绑定管理员角色（与其他 CRM 菜单一致：role_id=1, store_id=0）
-- 先删后插，保证重复执行不会产生重复绑定（role_menu 没有唯一键）
DELETE FROM `yoshop_store_role_menu`
WHERE `role_id` = 1 AND `store_id` = 0
  AND `menu_id` IN (
      SELECT `menu_id` FROM `yoshop_store_menu`
      WHERE module IN (10, 20)
        AND (path LIKE '/crm/project%' OR path LIKE '/crm/intent%')
  );

INSERT INTO `yoshop_store_role_menu` (`role_id`, `menu_id`, `store_id`)
SELECT 1, menu_id, 0 FROM `yoshop_store_menu`
WHERE module IN (10, 20)
  AND (path LIKE '/crm/project%' OR path LIKE '/crm/intent%');
