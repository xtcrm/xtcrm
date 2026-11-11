-- =============================================
-- CRM v2.10.0 — 责任位 / 审核 / 业绩 菜单
--
-- 菜单 ID 实测（2026-11-07）：MAX(menu_id)=21128，21100–21128 已被法务宝占用，
-- 因此业绩管理用 21129–21137。部署到其他租户前必须重新 SELECT MAX(menu_id)+1 核对。
--
-- 只登记已在库里存在的角色：10004 销售经理、10008 总经办。
-- 客服/审核/交付等岗位角色尚未建立，建好后再单独绑定（见设计 §8.2）。
--
-- 幂等：INSERT IGNORE 菜单；role_menu 先删后插。
-- =============================================

INSERT IGNORE INTO `yoshop_store_menu`
(`menu_id`, `module`, `fun_type`, `name`, `path`, `action_mark`, `parent_id`, `sort`, `create_time`, `update_time`) VALUES
-- 客户 · 待分配
(20106, 10, 'crm', '待分配客户', '/crm/customer/assign', 'hidden', 20100, 60, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20141, 20, 'crm', '查询待分配', '/crm/customer/assignLists', 'assignLists', 20106, 10, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20142, 20, 'crm', '分配客服', '/crm/customer/assignCs', 'assignCs', 20106, 20, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20143, 20, 'crm', '认领客户', '/crm/customer/claimCs', 'claimCs', 20106, 30, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20145, 20, 'crm', '变更服务责任人', '/crm/customer/setCs', 'setCs', 20101, 50, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
-- 项目 · 审核
(20950, 10, 'crm', '审核队列', '/crm/project/review', '', 20900, 40, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20951, 20, 'crm', '提交审核', '/crm/project/submitReview', 'submitReview', 20950, 10, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20952, 20, 'crm', '审核通过', '/crm/project/reviewPass', 'reviewPass', 20950, 20, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20953, 20, 'crm', '审核退回', '/crm/project/reviewReject', 'reviewReject', 20950, 30, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20954, 20, 'crm', '审核记录', '/crm/project/reviewLog', 'reviewLog', 20950, 40, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20955, 20, 'crm', '指派审核人', '/crm/project/assignReviewer', 'assignReviewer', 20950, 50, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
-- 业绩管理
(21129, 10, 'crm', '业绩管理', '/crm/commission', '', 0, 35, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(21130, 10, 'crm', '业绩明细', '/crm/commission/index', '', 21129, 10, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(21131, 10, 'crm', '分账规则', '/crm/commission/rule', '', 21129, 20, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(21132, 10, 'crm', '覆盖审批', '/crm/commission/override', '', 21129, 30, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(21133, 20, 'crm', '查询业绩', '/crm/commission/lists', 'lists', 21130, 10, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(21134, 20, 'crm', '导出业绩', '/crm/commission/export', 'export', 21130, 20, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(21135, 20, 'crm', '保存分账规则', '/crm/commission/saveRule', 'saveRule', 21131, 10, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(21136, 20, 'crm', '提交比例覆盖', '/crm/commission/applyOverride', 'applyOverride', 21132, 10, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(21137, 20, 'crm', '审批比例覆盖', '/crm/commission/approveOverride', 'approveOverride', 21132, 20, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

DELETE FROM `yoshop_store_role_menu`
WHERE `menu_id` IN (20106,20141,20142,20143,20145,20950,20951,20952,20953,20954,20955,
                    21129,21130,21131,21132,21133,21134,21135,21136,21137)
  AND `role_id` IN (10004, 10008);

INSERT INTO `yoshop_store_role_menu` (`role_id`, `menu_id`, `store_id`)
SELECT r.`role_id`, m.`menu_id`, r.`store_id`
FROM `yoshop_store_role` r
JOIN `yoshop_store_menu` m
  ON m.`menu_id` IN (20106,20141,20142,20143,20145,20950,20951,20952,20953,20954,20955,
                     21129,21130,21131,21132,21133,21134,21135,21136,21137)
WHERE r.`role_id` IN (10004, 10008);
