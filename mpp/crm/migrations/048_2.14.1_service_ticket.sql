-- =============================================
-- CRM v2.14.0 — 客服单（试点）
--
-- 1) 建 crm_service_ticket
-- 2) 客户菜单下增加「客服单」页面与操作权限点
-- 3) 初始授权：销售经理(10004)、总经办(10008)；其余角色按需勾选
--
-- 菜单 ID 基线：MAX(menu_id)=21305（2026-11-18 实测）
-- =============================================

CREATE TABLE IF NOT EXISTS `yoshop_crm_service_ticket` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `store_id` int(11) unsigned NOT NULL DEFAULT 0,
  `ticket_no` varchar(30) NOT NULL DEFAULT '' COMMENT 'ST-YYYYMMDD-NNN',
  `customer_id` int(11) unsigned NOT NULL DEFAULT 0,
  `contact_id` int(11) unsigned NOT NULL DEFAULT 0,
  `channel` varchar(20) NOT NULL DEFAULT 'other' COMMENT 'phone/wechat/email/visit/other',
  `category` varchar(20) NOT NULL DEFAULT 'consult' COMMENT 'consult/complaint/suggestion/visit',
  `priority` tinyint(3) unsigned NOT NULL DEFAULT 2 COMMENT '1低 2中 3高 4紧急',
  `title` varchar(200) NOT NULL DEFAULT '',
  `content` text,
  `expect_at` int(11) unsigned NOT NULL DEFAULT 0,
  `assignee_user_id` int(11) unsigned NOT NULL DEFAULT 0,
  `dept_id` int(11) unsigned NOT NULL DEFAULT 0,
  `status` tinyint(3) unsigned NOT NULL DEFAULT 1 COMMENT '1新建 2处理中 3待确认 4已解决 5已关闭',
  `resolution` text,
  `root_cause` varchar(50) NOT NULL DEFAULT '',
  `csat` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `created_by` int(11) unsigned NOT NULL DEFAULT 0,
  `resolved_at` int(11) unsigned NOT NULL DEFAULT 0,
  `closed_at` int(11) unsigned NOT NULL DEFAULT 0,
  `is_delete` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `create_time` int(11) unsigned NOT NULL DEFAULT 0,
  `update_time` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_no` (`store_id`,`ticket_no`),
  KEY `idx_status` (`store_id`,`status`),
  KEY `idx_customer` (`store_id`,`customer_id`),
  KEY `idx_assignee` (`store_id`,`assignee_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='客服单';

INSERT IGNORE INTO `yoshop_store_menu`
(`menu_id`, `module`, `fun_type`, `name`, `path`, `action_mark`, `parent_id`, `sort`, `create_time`, `update_time`) VALUES
(21306, 10, 'crm', '客服单',       '/crm/service/ticket',                    '',             20100, 16, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(21307, 20, 'crm', '客服单列表',   '/crm/serviceTicket/lists',               'lists',        21306, 10, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(21308, 20, 'crm', '新增客服单',   '/crm/serviceTicket/add',                 'add',          21306, 20, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(21309, 20, 'crm', '编辑客服单',   '/crm/serviceTicket/edit',                'edit',         21306, 30, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(21310, 20, 'crm', '指派客服单',   '/crm/serviceTicket/assign',              'assign',       21306, 40, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(21311, 20, 'crm', '客服单状态',   '/crm/serviceTicket/changeStatus',        'changeStatus', 21306, 50, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(21312, 20, 'crm', '删除客服单',   '/crm/serviceTicket/delete',              'delete',       21306, 60, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

DELETE FROM `yoshop_store_role_menu`
WHERE `menu_id` BETWEEN 21306 AND 21312 AND `role_id` IN (10004, 10008);

INSERT INTO `yoshop_store_role_menu` (`role_id`, `menu_id`, `store_id`)
SELECT r.`role_id`, m.`menu_id`, r.`store_id`
FROM `yoshop_store_role` r
JOIN `yoshop_store_menu` m ON m.`menu_id` BETWEEN 21306 AND 21312
WHERE r.`role_id` IN (10004, 10008);
