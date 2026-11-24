-- =============================================
-- CRM v2.14.2 — 客服单：流程实例 + 到期提醒
--
-- 1) flow_instance_id：可选流程实例
-- 2) remind_sent：提醒去重（0 未提醒 / 1 到期前已提醒 / 2 到期已提醒）
-- 3) 到期扫描索引
--
-- 幂等：列/索引先查 information_schema。
-- =============================================

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='yoshop_crm_service_ticket' AND COLUMN_NAME='flow_instance_id')=0,
  'ALTER TABLE `yoshop_crm_service_ticket` ADD COLUMN `flow_instance_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT ''平台流程实例ID，0=未走流程'' AFTER `status`',
  'SET @noop = 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='yoshop_crm_service_ticket' AND COLUMN_NAME='remind_sent')=0,
  'ALTER TABLE `yoshop_crm_service_ticket` ADD COLUMN `remind_sent` tinyint(3) unsigned NOT NULL DEFAULT 0 COMMENT ''0未提醒 1到期前已提醒 2到期已提醒'' AFTER `expect_at`',
  'SET @noop = 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='yoshop_crm_service_ticket' AND INDEX_NAME='idx_expect')=0,
  'ALTER TABLE `yoshop_crm_service_ticket` ADD KEY `idx_expect` (`store_id`,`status`,`expect_at`)',
  'SET @noop = 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
