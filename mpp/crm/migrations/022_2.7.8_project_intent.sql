-- =============================================
-- CRM v2.7.8 — 项目 / 需求 / 参与人
--
-- 设计见 docs/projects/CRM/服务事项模型-表结构.md
-- 幂等：列与索引先查 information_schema，已存在则跳过（防止迁移中断后重跑失败）。
-- 只新增、不改旧字段语义；报价/订单/合同/工单仅补可空关联列。
-- =============================================

-- ─────────────────────────────────────────────
-- 新表：项目 / 项目参与人 / 需求
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `yoshop_crm_project` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '项目ID',
  `store_id` int(11) NOT NULL DEFAULT 0 COMMENT '租户ID',
  `project_no` varchar(50) NOT NULL DEFAULT '' COMMENT '项目号 PJ-YYYYMMDD-NNN',
  `customer_id` int(11) NOT NULL DEFAULT 0 COMMENT '客户ID',
  `title` varchar(200) NOT NULL DEFAULT '' COMMENT '项目名称',
  `owner_user_id` int(11) NOT NULL DEFAULT 0 COMMENT '项目负责人 store_user_id',
  `sales_user_id` int(11) NOT NULL DEFAULT 0 COMMENT '销售负责人（客户负责人快照）',
  `status` tinyint(3) NOT NULL DEFAULT 1 COMMENT '1潜在 2进行中 3已完成 4已取消',
  `service_start_time` int(11) DEFAULT NULL COMMENT '服务期开始（周期服务）',
  `service_end_time` int(11) DEFAULT NULL COMMENT '服务期结束（周期服务）',
  `started_at` int(11) DEFAULT NULL COMMENT '进入进行中时间',
  `completed_at` int(11) DEFAULT NULL COMMENT '完成时间',
  `archived_at` int(11) DEFAULT NULL COMMENT '归档时间（标记，不是状态）',
  `remark` varchar(500) NOT NULL DEFAULT '' COMMENT '备注',
  `legacy` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1=迁移回填',
  `is_delete` tinyint(1) NOT NULL DEFAULT 0 COMMENT '软删除',
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_store_no` (`store_id`,`project_no`),
  KEY `idx_customer` (`store_id`,`customer_id`,`status`,`is_delete`),
  KEY `idx_owner` (`store_id`,`owner_user_id`,`status`,`is_delete`),
  KEY `idx_status` (`store_id`,`status`,`is_delete`),
  KEY `idx_archive` (`store_id`,`archived_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='CRM服务项目';

CREATE TABLE IF NOT EXISTS `yoshop_crm_project_participant` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `store_id` int(11) NOT NULL DEFAULT 0 COMMENT '租户ID',
  `project_id` int(11) NOT NULL DEFAULT 0 COMMENT '项目ID',
  `user_id` int(11) NOT NULL DEFAULT 0 COMMENT '参与人 store_user_id',
  `permission` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1只读 2可编辑',
  `source` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1手动 2品类默认 3@提及 4导入',
  `invited_by_user_id` int(11) NOT NULL DEFAULT 0 COMMENT '邀请人',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1在任 0已移出',
  `remark` varchar(200) NOT NULL DEFAULT '' COMMENT '备注/邀请原因',
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_project_user` (`store_id`,`project_id`,`user_id`),
  KEY `idx_user` (`store_id`,`user_id`,`status`),
  KEY `idx_project` (`store_id`,`project_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='CRM项目参与人';

CREATE TABLE IF NOT EXISTS `yoshop_crm_intent` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '需求ID',
  `store_id` int(11) NOT NULL DEFAULT 0 COMMENT '租户ID',
  `intent_no` varchar(50) NOT NULL DEFAULT '' COMMENT '需求号 RQ-YYYYMMDD-NNN',
  `customer_id` int(11) NOT NULL DEFAULT 0 COMMENT '客户ID',
  `project_id` int(11) NOT NULL DEFAULT 0 COMMENT '所属项目ID，0=未立项',
  `title` varchar(200) NOT NULL DEFAULT '' COMMENT '需求标题',
  `description` text COMMENT '客户要求/期望',
  `catalog_id` int(11) NOT NULL DEFAULT 0 COMMENT '服务品类 workorder_type.id',
  `service_name` varchar(100) NOT NULL DEFAULT '' COMMENT '品类名快照',
  `quantity` decimal(10,2) NOT NULL DEFAULT '1.00' COMMENT '数量',
  `unit` varchar(20) NOT NULL DEFAULT '' COMMENT '单位',
  `expected_date` int(11) DEFAULT NULL COMMENT '客户期望时间',
  `assignee_user_id` int(11) NOT NULL DEFAULT 0 COMMENT '承接人 store_user_id',
  `sales_user_id` int(11) NOT NULL DEFAULT 0 COMMENT '销售负责人',
  `status` tinyint(3) NOT NULL DEFAULT 1 COMMENT '1草稿 2待报价 3已报价 4已成交 5搁置 6已取消',
  `held_until` int(11) DEFAULT NULL COMMENT '搁置唤醒时间',
  `closed_reason` varchar(200) NOT NULL DEFAULT '' COMMENT '取消/关闭原因',
  `source` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1拜访 2手工 3导入 4客户自助 5规则需求转',
  `source_id` int(11) NOT NULL DEFAULT 0 COMMENT '来源对象ID（如拜访记录ID）',
  `legacy` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1=历史数据',
  `is_delete` tinyint(1) NOT NULL DEFAULT 0 COMMENT '软删除',
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_store_no` (`store_id`,`intent_no`),
  KEY `idx_customer` (`store_id`,`customer_id`,`status`,`is_delete`),
  KEY `idx_project` (`store_id`,`project_id`,`status`,`is_delete`),
  KEY `idx_assignee` (`store_id`,`assignee_user_id`,`status`,`is_delete`),
  KEY `idx_status` (`store_id`,`status`,`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='CRM客户需求';

-- ─────────────────────────────────────────────
-- 存量表补列（幂等）
-- ─────────────────────────────────────────────
SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='yoshop_crm_quotation' AND COLUMN_NAME='project_id')=0,
  'ALTER TABLE `yoshop_crm_quotation` ADD COLUMN `project_id` int(11) NOT NULL DEFAULT 0 COMMENT ''所属项目ID'' AFTER `customer_id`', 'SET @noop = 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='yoshop_crm_quotation' AND COLUMN_NAME='pricing_mode')=0,
  'ALTER TABLE `yoshop_crm_quotation` ADD COLUMN `pricing_mode` tinyint(1) NOT NULL DEFAULT 1 COMMENT ''1明细型 2总额型'' AFTER `final_amount`', 'SET @noop = 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='yoshop_crm_quotation' AND COLUMN_NAME='legacy')=0,
  'ALTER TABLE `yoshop_crm_quotation` ADD COLUMN `legacy` tinyint(1) NOT NULL DEFAULT 0 COMMENT ''1=历史数据'' AFTER `owner_user_id`', 'SET @noop = 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='yoshop_crm_quotation_item' AND COLUMN_NAME='intent_id')=0,
  'ALTER TABLE `yoshop_crm_quotation_item` ADD COLUMN `intent_id` int(11) NOT NULL DEFAULT 0 COMMENT ''对应需求ID'' AFTER `quotation_id`', 'SET @noop = 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='yoshop_crm_quotation_item' AND COLUMN_NAME='catalog_id')=0,
  'ALTER TABLE `yoshop_crm_quotation_item` ADD COLUMN `catalog_id` int(11) NOT NULL DEFAULT 0 COMMENT ''服务品类ID'' AFTER `product_id`', 'SET @noop = 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='yoshop_crm_order' AND COLUMN_NAME='project_id')=0,
  'ALTER TABLE `yoshop_crm_order` ADD COLUMN `project_id` int(11) NOT NULL DEFAULT 0 COMMENT ''所属项目ID'' AFTER `customer_id`', 'SET @noop = 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='yoshop_crm_order' AND COLUMN_NAME='pricing_mode')=0,
  'ALTER TABLE `yoshop_crm_order` ADD COLUMN `pricing_mode` tinyint(1) NOT NULL DEFAULT 1 COMMENT ''1明细型 2总额型'' AFTER `final_amount`', 'SET @noop = 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='yoshop_crm_order' AND COLUMN_NAME='service_start_time')=0,
  'ALTER TABLE `yoshop_crm_order` ADD COLUMN `service_start_time` int(11) DEFAULT NULL COMMENT ''服务期开始（周期服务）''', 'SET @noop = 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='yoshop_crm_order' AND COLUMN_NAME='service_end_time')=0,
  'ALTER TABLE `yoshop_crm_order` ADD COLUMN `service_end_time` int(11) DEFAULT NULL COMMENT ''服务期结束（周期服务）''', 'SET @noop = 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='yoshop_crm_order' AND COLUMN_NAME='legacy')=0,
  'ALTER TABLE `yoshop_crm_order` ADD COLUMN `legacy` tinyint(1) NOT NULL DEFAULT 0 COMMENT ''1=历史数据''', 'SET @noop = 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='yoshop_crm_order_item' AND COLUMN_NAME='intent_id')=0,
  'ALTER TABLE `yoshop_crm_order_item` ADD COLUMN `intent_id` int(11) NOT NULL DEFAULT 0 COMMENT ''对应需求ID'' AFTER `order_id`', 'SET @noop = 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='yoshop_crm_order_item' AND COLUMN_NAME='catalog_id')=0,
  'ALTER TABLE `yoshop_crm_order_item` ADD COLUMN `catalog_id` int(11) NOT NULL DEFAULT 0 COMMENT ''服务品类ID'' AFTER `product_id`', 'SET @noop = 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='yoshop_crm_contract' AND COLUMN_NAME='project_id')=0,
  'ALTER TABLE `yoshop_crm_contract` ADD COLUMN `project_id` int(11) NOT NULL DEFAULT 0 COMMENT ''所属项目ID'' AFTER `customer_id`', 'SET @noop = 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='yoshop_crm_contract' AND COLUMN_NAME='legacy')=0,
  'ALTER TABLE `yoshop_crm_contract` ADD COLUMN `legacy` tinyint(1) NOT NULL DEFAULT 0 COMMENT ''1=历史数据''', 'SET @noop = 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='yoshop_workorder_ticket' AND COLUMN_NAME='kind')=0,
  'ALTER TABLE `yoshop_workorder_ticket` ADD COLUMN `kind` tinyint(3) NOT NULL DEFAULT 1 COMMENT ''1一次性工单 2服务期次'' AFTER `type_name`', 'SET @noop = 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='yoshop_workorder_ticket' AND COLUMN_NAME='order_id')=0,
  'ALTER TABLE `yoshop_workorder_ticket` ADD COLUMN `order_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT ''来源订单（期次必填）'' AFTER `customer_id`', 'SET @noop = 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='yoshop_workorder_ticket' AND COLUMN_NAME='period_no')=0,
  'ALTER TABLE `yoshop_workorder_ticket` ADD COLUMN `period_no` int(11) unsigned NOT NULL DEFAULT 0 COMMENT ''期次序号，从1开始''', 'SET @noop = 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='yoshop_workorder_ticket' AND COLUMN_NAME='project_id')=0,
  'ALTER TABLE `yoshop_workorder_ticket` ADD COLUMN `project_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT ''所属项目ID（CRM）''', 'SET @noop = 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='yoshop_workorder_ticket' AND COLUMN_NAME='intent_id')=0,
  'ALTER TABLE `yoshop_workorder_ticket` ADD COLUMN `intent_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT ''对应需求ID（CRM）''', 'SET @noop = 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='yoshop_workorder_ticket' AND COLUMN_NAME='legacy')=0,
  'ALTER TABLE `yoshop_workorder_ticket` ADD COLUMN `legacy` tinyint(3) unsigned NOT NULL DEFAULT 0 COMMENT ''1=历史数据''', 'SET @noop = 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='yoshop_workorder_type' AND COLUMN_NAME='price')=0,
  'ALTER TABLE `yoshop_workorder_type` ADD COLUMN `price` decimal(12,2) NOT NULL DEFAULT ''0.00'' COMMENT ''标准价（0=面议）'' AFTER `remark`', 'SET @noop = 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='yoshop_workorder_type' AND COLUMN_NAME='billing_cycle')=0,
  'ALTER TABLE `yoshop_workorder_type` ADD COLUMN `billing_cycle` tinyint(1) NOT NULL DEFAULT 1 COMMENT ''1一次性 2按月 3按季 4按年 5按次''', 'SET @noop = 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='yoshop_workorder_type' AND COLUMN_NAME='default_delivery_days')=0,
  'ALTER TABLE `yoshop_workorder_type` ADD COLUMN `default_delivery_days` int(11) NOT NULL DEFAULT 0 COMMENT ''默认交付天数，0=不限''', 'SET @noop = 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='yoshop_workorder_type' AND COLUMN_NAME='default_assignee_user_id')=0,
  'ALTER TABLE `yoshop_workorder_type` ADD COLUMN `default_assignee_user_id` int(11) NOT NULL DEFAULT 0 COMMENT ''默认交付负责人''', 'SET @noop = 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='yoshop_workorder_type' AND COLUMN_NAME='default_department_id')=0,
  'ALTER TABLE `yoshop_workorder_type` ADD COLUMN `default_department_id` int(11) NOT NULL DEFAULT 0 COMMENT ''默认交付部门''', 'SET @noop = 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='yoshop_crm_followup' AND COLUMN_NAME='project_id')=0,
  'ALTER TABLE `yoshop_crm_followup` ADD COLUMN `project_id` int(11) NOT NULL DEFAULT 0 COMMENT ''关联项目ID'' AFTER `customer_id`', 'SET @noop = 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='yoshop_crm_followup' AND COLUMN_NAME='intent_id')=0,
  'ALTER TABLE `yoshop_crm_followup` ADD COLUMN `intent_id` int(11) NOT NULL DEFAULT 0 COMMENT ''关联需求ID''', 'SET @noop = 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='yoshop_crm_event_log' AND COLUMN_NAME='project_id')=0,
  'ALTER TABLE `yoshop_crm_event_log` ADD COLUMN `project_id` int(11) NOT NULL DEFAULT 0 COMMENT ''所属项目ID'' AFTER `target_id`', 'SET @noop = 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='yoshop_crm_quotation' AND INDEX_NAME='idx_project')=0,
  'ALTER TABLE `yoshop_crm_quotation` ADD KEY `idx_project` (`store_id`,`project_id`)', 'SET @noop = 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='yoshop_crm_quotation_item' AND INDEX_NAME='idx_intent')=0,
  'ALTER TABLE `yoshop_crm_quotation_item` ADD KEY `idx_intent` (`store_id`,`intent_id`)', 'SET @noop = 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='yoshop_crm_order' AND INDEX_NAME='idx_project')=0,
  'ALTER TABLE `yoshop_crm_order` ADD KEY `idx_project` (`store_id`,`project_id`)', 'SET @noop = 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='yoshop_crm_order_item' AND INDEX_NAME='idx_intent')=0,
  'ALTER TABLE `yoshop_crm_order_item` ADD KEY `idx_intent` (`store_id`,`intent_id`)', 'SET @noop = 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='yoshop_crm_contract' AND INDEX_NAME='idx_project')=0,
  'ALTER TABLE `yoshop_crm_contract` ADD KEY `idx_project` (`store_id`,`project_id`)', 'SET @noop = 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='yoshop_workorder_ticket' AND INDEX_NAME='idx_project')=0,
  'ALTER TABLE `yoshop_workorder_ticket` ADD KEY `idx_project` (`store_id`,`project_id`,`is_delete`)', 'SET @noop = 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='yoshop_workorder_ticket' AND INDEX_NAME='idx_order_period')=0,
  'ALTER TABLE `yoshop_workorder_ticket` ADD KEY `idx_order_period` (`store_id`,`order_id`,`period_no`)', 'SET @noop = 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='yoshop_workorder_ticket' AND INDEX_NAME='idx_kind_status')=0,
  'ALTER TABLE `yoshop_workorder_ticket` ADD KEY `idx_kind_status` (`store_id`,`kind`,`status`,`is_delete`)', 'SET @noop = 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='yoshop_crm_followup' AND INDEX_NAME='idx_project')=0,
  'ALTER TABLE `yoshop_crm_followup` ADD KEY `idx_project` (`store_id`,`project_id`,`follow_date`)', 'SET @noop = 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='yoshop_crm_followup' AND INDEX_NAME='idx_intent')=0,
  'ALTER TABLE `yoshop_crm_followup` ADD KEY `idx_intent` (`store_id`,`intent_id`)', 'SET @noop = 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='yoshop_crm_event_log' AND INDEX_NAME='idx_project_time')=0,
  'ALTER TABLE `yoshop_crm_event_log` ADD KEY `idx_project_time` (`store_id`,`project_id`,`create_time`)', 'SET @noop = 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ─────────────────────────────────────────────
-- 状态枚举扩展（只改注释，不改旧值）
-- ─────────────────────────────────────────────
ALTER TABLE `yoshop_crm_quotation`
  MODIFY COLUMN `status` tinyint(1) DEFAULT 1 COMMENT '1草稿 2已发送 3已确认 4已拒绝 5已转订单 6已过期 7已取消';

ALTER TABLE `yoshop_crm_order`
  MODIFY COLUMN `status` tinyint(1) DEFAULT 1 COMMENT '1待确认 2-4进行中（商品：生产中/待发货/已发货） 5已完成 6已取消';

ALTER TABLE `yoshop_crm_contract`
  MODIFY COLUMN `status` tinyint(1) DEFAULT 1 COMMENT '1草稿 2已签订 3进行中 4已完成 5已终止（展示为已取消）';

ALTER TABLE `yoshop_workorder_item`
  MODIFY COLUMN `status` tinyint(3) unsigned NOT NULL DEFAULT 0 COMMENT '0待提交 1已提交 2已通过 3已驳回 4免交';

