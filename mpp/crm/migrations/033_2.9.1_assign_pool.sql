-- =============================================
-- CRM v2.9.1 — 待分配槽位
--
-- 「待分配」是查询条件，不是状态字段。三个槽位：
--   1 销售归属（owner_user_id = 0 且已成交）→ 销售组长
--   2 客服归属（cs_user_id = 0 且已有成交/项目）→ 客服组长
--   3 交付（项目 owner_user_id = 0 且进行中）→ 交付组长
--
-- action 枚举扩展（字段不变）：5=自动分配 6=超时升级
-- 存量客户 cs_pool_time = 0，只进管理员清单、不计时、不触发升级（D12）。
--
-- 幂等：列/索引先查 information_schema。
-- =============================================

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='yoshop_crm_customer_claim_log' AND COLUMN_NAME='slot')=0,
  'ALTER TABLE `yoshop_crm_customer_claim_log` ADD COLUMN `slot` tinyint(3) NOT NULL DEFAULT 1 COMMENT ''1销售 2客服 3交付'' AFTER `action`',
  'SET @noop = 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='yoshop_crm_customer_claim_log' AND INDEX_NAME='idx_slot')=0,
  'ALTER TABLE `yoshop_crm_customer_claim_log` ADD KEY `idx_slot` (`store_id`,`customer_id`,`slot`)',
  'SET @noop = 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
