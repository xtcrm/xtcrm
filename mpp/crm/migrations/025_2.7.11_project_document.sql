-- =============================================
-- CRM v2.7.11 — 项目文件
--
-- 文档表补 project_id：项目工作台里的合同扫描件、材料、沟通附件按项目归档。
-- 幂等：列/索引先查 information_schema。
-- =============================================

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='yoshop_crm_document' AND COLUMN_NAME='project_id')=0,
  'ALTER TABLE `yoshop_crm_document` ADD COLUMN `project_id` int(11) NOT NULL DEFAULT 0 COMMENT ''所属项目ID'' AFTER `customer_id`',
  'SET @noop = 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='yoshop_crm_document' AND INDEX_NAME='idx_project')=0,
  'ALTER TABLE `yoshop_crm_document` ADD KEY `idx_project` (`store_id`,`project_id`)',
  'SET @noop = 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
