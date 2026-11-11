-- =============================================
-- CRM v2.7.13 — 项目收款
--
-- 记录每一笔实际到账，并回写订单已收/未收金额。
-- 金额是业务事实，不能只靠一个 payment_status 概括。
-- =============================================

CREATE TABLE IF NOT EXISTS `yoshop_crm_payment` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '收款ID',
  `store_id` int(11) NOT NULL DEFAULT 0 COMMENT '租户ID',
  `payment_no` varchar(50) NOT NULL DEFAULT '' COMMENT '收款单号 SK-YYYYMMDD-NNN',
  `project_id` int(11) NOT NULL DEFAULT 0 COMMENT '所属项目ID',
  `order_id` int(11) NOT NULL DEFAULT 0 COMMENT '关联订单ID',
  `customer_id` int(11) NOT NULL DEFAULT 0 COMMENT '客户ID',
  `amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '收款金额',
  `method` varchar(20) NOT NULL DEFAULT '' COMMENT '收款方式',
  `paid_at` int(11) DEFAULT NULL COMMENT '到账时间',
  `remark` varchar(500) NOT NULL DEFAULT '' COMMENT '备注',
  `actor_user_id` int(11) NOT NULL DEFAULT 0 COMMENT '登记人',
  `is_delete` tinyint(1) NOT NULL DEFAULT 0 COMMENT '软删除',
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_store_no` (`store_id`,`payment_no`),
  KEY `idx_project` (`store_id`,`project_id`,`is_delete`),
  KEY `idx_order` (`store_id`,`order_id`,`is_delete`),
  KEY `idx_customer` (`store_id`,`customer_id`,`is_delete`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='CRM收款记录';

INSERT IGNORE INTO `yoshop_store_menu`
(`menu_id`, `module`, `fun_type`, `name`, `path`, `action_mark`, `parent_id`, `sort`, `create_time`, `update_time`) VALUES
(20945, 20, 'crm', '查询收款', '/crm/payment/lists', 'lists', 20910, 110, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20946, 20, 'crm', '登记收款', '/crm/payment/add', 'add', 20910, 120, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20947, 20, 'crm', '删除收款', '/crm/payment/delete', 'delete', 20910, 130, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

DELETE FROM `yoshop_store_role_menu`
WHERE `role_id` = 1 AND `store_id` = 0 AND `menu_id` IN (20945, 20946, 20947);

INSERT INTO `yoshop_store_role_menu` (`role_id`, `menu_id`, `store_id`)
SELECT 1, `menu_id`, 0 FROM `yoshop_store_menu` WHERE `menu_id` IN (20945, 20946, 20947);
