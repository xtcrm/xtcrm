-- =============================================
-- CRM v2.8.0 — 部门岗位码
--
-- 组织是四中心（营收 / 交付 / 质量 / 支撑），但系统里只有部门树。
-- 按 D1「一个人只挂一个部门」，岗位可由部门唯一确定，因此岗位码放部门上：
--   store_department.post_code 为空时向上继承父级
--   用户岗位 = store_user.department_id 对应部门（或其最近祖先）的 post_code
-- 好处：不动 store_user，组织调整只改部门树，人员自动跟随。
--
-- 注意：store_department 是平台表，需同步 app/platform/model/Department.php 与部门管理页。
-- 幂等：列/索引先查 information_schema。
-- =============================================

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='yoshop_store_department' AND COLUMN_NAME='post_code')=0,
  'ALTER TABLE `yoshop_store_department` ADD COLUMN `post_code` varchar(30) NOT NULL DEFAULT '''' COMMENT ''岗位码，空则继承父级'' AFTER `department_code`',
  'SET @noop = 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='yoshop_store_department' AND INDEX_NAME='idx_post')=0,
  'ALTER TABLE `yoshop_store_department` ADD KEY `idx_post` (`store_id`,`post_code`)',
  'SET @noop = 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
