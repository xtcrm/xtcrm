-- =============================================
-- CRM v2.8.1 — 客户 / 项目责任位
--
-- 单一归属下，责任位就是字段，每类责任位在每个对象上只有一个槽位（0 = 未分配，不存 NULL）。
--   客户：cs_user_id（服务 / 续费归属）、cs_department_id（部门冗余）、cs_pool_time（进池计时）
--   项目：cs_user_id（从客户带入）、auditor_user_id（审核人）、review_status（0未送审 1待审 2通过 3退回）
--
-- cs_pool_time = 0 的语义是「历史遗留，不计时」：
--   存量客户上线时天然是 0/0，只进管理员待分配清单，不触发超时升级（D12）。
--   上线后新成交的客户写 cs_pool_time = now，才走完整的 T+1/T+3/T+7 升级链。
--
-- review_status 独立于 status：status(1潜在 2进行中 3已完成 4已取消) 被统计与进度环使用，不能塞进去。
-- 幂等：列/索引先查 information_schema。
-- =============================================

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='yoshop_crm_customer' AND COLUMN_NAME='cs_user_id')=0,
  'ALTER TABLE `yoshop_crm_customer` ADD COLUMN `cs_user_id` int(11) NOT NULL DEFAULT 0 COMMENT ''服务/续费责任人 store_user_id，0=待分配'' AFTER `owner_department_id`',
  'SET @noop = 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='yoshop_crm_customer' AND COLUMN_NAME='cs_department_id')=0,
  'ALTER TABLE `yoshop_crm_customer` ADD COLUMN `cs_department_id` int(11) NOT NULL DEFAULT 0 COMMENT ''服务责任人所属部门（冗余）'' AFTER `cs_user_id`',
  'SET @noop = 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='yoshop_crm_customer' AND COLUMN_NAME='cs_pool_time')=0,
  'ALTER TABLE `yoshop_crm_customer` ADD COLUMN `cs_pool_time` int(11) NOT NULL DEFAULT 0 COMMENT ''进入客服待分配池时间，0=历史遗留不计时'' AFTER `enter_pool_time`',
  'SET @noop = 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='yoshop_crm_customer' AND INDEX_NAME='idx_cs')=0,
  'ALTER TABLE `yoshop_crm_customer` ADD KEY `idx_cs` (`store_id`,`cs_user_id`,`is_delete`)',
  'SET @noop = 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='yoshop_crm_customer' AND INDEX_NAME='idx_cs_pool')=0,
  'ALTER TABLE `yoshop_crm_customer` ADD KEY `idx_cs_pool` (`store_id`,`cs_user_id`,`cs_pool_time`)',
  'SET @noop = 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='yoshop_crm_project' AND COLUMN_NAME='cs_user_id')=0,
  'ALTER TABLE `yoshop_crm_project` ADD COLUMN `cs_user_id` int(11) NOT NULL DEFAULT 0 COMMENT ''客服快照（从客户带入）'' AFTER `sales_user_id`',
  'SET @noop = 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='yoshop_crm_project' AND COLUMN_NAME='auditor_user_id')=0,
  'ALTER TABLE `yoshop_crm_project` ADD COLUMN `auditor_user_id` int(11) NOT NULL DEFAULT 0 COMMENT ''审核人（指派制，D11）'' AFTER `cs_user_id`',
  'SET @noop = 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='yoshop_crm_project' AND COLUMN_NAME='review_status')=0,
  'ALTER TABLE `yoshop_crm_project` ADD COLUMN `review_status` tinyint(3) NOT NULL DEFAULT 0 COMMENT ''0未送审 1待审核 2已通过 3已退回'' AFTER `auditor_user_id`',
  'SET @noop = 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='yoshop_crm_project' AND INDEX_NAME='idx_audit')=0,
  'ALTER TABLE `yoshop_crm_project` ADD KEY `idx_audit` (`store_id`,`review_status`,`is_delete`)',
  'SET @noop = 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='yoshop_crm_project' AND INDEX_NAME='idx_cs')=0,
  'ALTER TABLE `yoshop_crm_project` ADD KEY `idx_cs` (`store_id`,`cs_user_id`,`status`,`is_delete`)',
  'SET @noop = 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
