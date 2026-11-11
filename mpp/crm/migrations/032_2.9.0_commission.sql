-- =============================================
-- CRM v2.9.0 — 业绩分账
--
-- 核心：签约锁比例，回款出业绩，退款冲销，历史永不重算。
--   commission_share    签订时生成，ratio / base_amount 快照
--   commission_record   每次回款按 share.ratio 生成，归属月 = paid_at 所在月
--   commission_override 单笔比例覆盖申请（D10），审批通过才生效
--
-- 比例 0 的一方不生成 share 行（不是生成 0 元记录）。
-- 幂等：CREATE TABLE IF NOT EXISTS + information_schema 判存。
-- =============================================

CREATE TABLE IF NOT EXISTS `yoshop_crm_commission_share` (
  `id`               int(11)        NOT NULL AUTO_INCREMENT COMMENT '分账ID',
  `store_id`         int(11)        NOT NULL DEFAULT 0  COMMENT '租户ID',
  `biz_type`         tinyint(3)     NOT NULL DEFAULT 1  COMMENT '1新签 2续费 3增购',
  `source_type`      tinyint(3)     NOT NULL DEFAULT 1  COMMENT '1订单 2合同',
  `source_id`        int(11)        NOT NULL DEFAULT 0  COMMENT '业务单据ID',
  `source_no`        varchar(50)    NOT NULL DEFAULT '' COMMENT '业务单号',
  `customer_id`      int(11)        NOT NULL DEFAULT 0  COMMENT '客户ID',
  `project_id`       int(11)        NOT NULL DEFAULT 0  COMMENT '项目ID',
  `user_id`          int(11)        NOT NULL DEFAULT 0  COMMENT '业绩归属人 store_user_id',
  `post_code`        varchar(30)    NOT NULL DEFAULT '' COMMENT '岗位快照 sales/cs',
  `role`             tinyint(3)     NOT NULL DEFAULT 1  COMMENT '1主 2协',
  `ratio`            decimal(5,2)   NOT NULL DEFAULT 0.00 COMMENT '分成比例%',
  `base_amount`      decimal(12,2)  NOT NULL DEFAULT 0.00 COMMENT '业务总额快照',
  `share_amount`     decimal(12,2)  NOT NULL DEFAULT 0.00 COMMENT '应分业绩 = base*ratio',
  `confirmed_amount` decimal(12,2)  NOT NULL DEFAULT 0.00 COMMENT '已确认业绩',
  `status`           tinyint(3)     NOT NULL DEFAULT 1  COMMENT '1生效 2已结束 3已调整',
  `group_no`         tinyint(3)     NOT NULL DEFAULT 0  COMMENT '分账版本 0=原始 1..=金额调整',
  `adjust_of_id`     int(11)        NOT NULL DEFAULT 0  COMMENT '调整来源 share_id',
  `lock_time`        int(11)        NOT NULL DEFAULT 0  COMMENT '比例锁定时间',
  `override_id`      int(11)        NOT NULL DEFAULT 0  COMMENT '分账覆盖申请ID，0=按规则比例',
  `legacy`           tinyint(1)     NOT NULL DEFAULT 0  COMMENT '1=历史回填',
  `remark`           varchar(255)   NOT NULL DEFAULT '' COMMENT '备注',
  `create_time`      int(11)        DEFAULT NULL,
  `update_time`      int(11)        DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_source_user` (`store_id`,`source_type`,`source_id`,`user_id`,`group_no`),
  KEY `idx_user`     (`store_id`,`user_id`,`status`),
  KEY `idx_source`   (`store_id`,`source_type`,`source_id`),
  KEY `idx_customer` (`store_id`,`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='CRM业绩分账计划';

CREATE TABLE IF NOT EXISTS `yoshop_crm_commission_record` (
  `id`             int(11)       NOT NULL AUTO_INCREMENT COMMENT '业绩记录ID',
  `store_id`       int(11)       NOT NULL DEFAULT 0  COMMENT '租户ID',
  `share_id`       int(11)       NOT NULL DEFAULT 0  COMMENT '分账计划ID',
  `payment_id`     int(11)       NOT NULL DEFAULT 0  COMMENT '收款单ID',
  `customer_id`    int(11)       NOT NULL DEFAULT 0  COMMENT '客户ID',
  `project_id`     int(11)       NOT NULL DEFAULT 0  COMMENT '项目ID',
  `user_id`        int(11)       NOT NULL DEFAULT 0  COMMENT '业绩归属人',
  `post_code`      varchar(30)   NOT NULL DEFAULT '' COMMENT '岗位快照',
  `biz_type`       tinyint(3)    NOT NULL DEFAULT 1  COMMENT '业务类型快照',
  `ratio`          decimal(5,2)  NOT NULL DEFAULT 0.00 COMMENT '比例快照',
  `base_amount`    decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '本次回款金额',
  `amount`         decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '本次确认业绩，负数=冲销',
  `confirm_month`  char(7)       NOT NULL DEFAULT '' COMMENT '归属月 YYYY-MM',
  `confirmed_at`   int(11)       NOT NULL DEFAULT 0  COMMENT '按回款到账时间',
  `reversal_of_id` int(11)       NOT NULL DEFAULT 0  COMMENT '冲销来源 record_id',
  `legacy`         tinyint(1)    NOT NULL DEFAULT 0  COMMENT '1=历史回填',
  `remark`         varchar(255)  NOT NULL DEFAULT '',
  `create_time`    int(11)       DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_month` (`store_id`,`user_id`,`confirm_month`),
  KEY `idx_biz_month`  (`store_id`,`biz_type`,`confirm_month`),
  KEY `idx_share`      (`share_id`),
  KEY `idx_payment`    (`payment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='CRM业绩确认记录';

CREATE TABLE IF NOT EXISTS `yoshop_crm_commission_override` (
  `id`                  int(11)       NOT NULL AUTO_INCREMENT COMMENT '覆盖申请ID',
  `store_id`            int(11)       NOT NULL DEFAULT 0  COMMENT '租户ID',
  `source_type`         tinyint(3)    NOT NULL DEFAULT 1  COMMENT '1订单 2合同',
  `source_id`           int(11)       NOT NULL DEFAULT 0  COMMENT '业务单据ID',
  `source_no`           varchar(50)   NOT NULL DEFAULT '' COMMENT '业务单号',
  `customer_id`         int(11)       NOT NULL DEFAULT 0  COMMENT '客户ID',
  `biz_type`            tinyint(3)    NOT NULL DEFAULT 1  COMMENT '1新签 2续费 3增购',
  `default_sales_ratio` decimal(5,2)  NOT NULL DEFAULT 0.00 COMMENT '规则比例快照-销售',
  `default_cs_ratio`    decimal(5,2)  NOT NULL DEFAULT 0.00 COMMENT '规则比例快照-客服',
  `sales_ratio`         decimal(5,2)  NOT NULL DEFAULT 0.00 COMMENT '申请比例-销售',
  `cs_ratio`            decimal(5,2)  NOT NULL DEFAULT 0.00 COMMENT '申请比例-客服',
  `reason`              varchar(500)  NOT NULL DEFAULT '' COMMENT '申请理由（必填）',
  `apply_user_id`       int(11)       NOT NULL DEFAULT 0  COMMENT '申请人',
  `apply_time`          int(11)       NOT NULL DEFAULT 0  COMMENT '申请时间',
  `approve_user_id`     int(11)       NOT NULL DEFAULT 0  COMMENT '审批人',
  `approve_time`        int(11)       NOT NULL DEFAULT 0  COMMENT '审批时间',
  `approve_remark`      varchar(500)  NOT NULL DEFAULT '' COMMENT '审批意见',
  `status`              tinyint(3)    NOT NULL DEFAULT 0  COMMENT '0待审 1通过 2驳回 3已失效',
  `create_time`         int(11)       DEFAULT NULL,
  `update_time`         int(11)       DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_source`  (`store_id`,`source_type`,`source_id`,`status`),
  KEY `idx_pending` (`store_id`,`status`,`apply_time`),
  KEY `idx_apply`   (`store_id`,`apply_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='CRM分账比例覆盖申请';

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='yoshop_crm_order' AND COLUMN_NAME='biz_type')=0,
  'ALTER TABLE `yoshop_crm_order` ADD COLUMN `biz_type` tinyint(3) NOT NULL DEFAULT 1 COMMENT ''1新签 2续费 3增购'' AFTER `status`',
  'SET @noop = 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='yoshop_crm_contract' AND COLUMN_NAME='biz_type')=0,
  'ALTER TABLE `yoshop_crm_contract` ADD COLUMN `biz_type` tinyint(3) NOT NULL DEFAULT 1 COMMENT ''1新签 2续费 3增购'' AFTER `status`',
  'SET @noop = 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='yoshop_crm_payment' AND COLUMN_NAME='contract_id')=0,
  'ALTER TABLE `yoshop_crm_payment` ADD COLUMN `contract_id` int(11) NOT NULL DEFAULT 0 COMMENT ''关联合同ID（无订单的回款可用）'' AFTER `order_id`',
  'SET @noop = 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
