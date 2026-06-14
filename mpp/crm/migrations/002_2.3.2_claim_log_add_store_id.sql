-- ========================================
-- v2.3.2 公海客户认领日志 - 添加 store_id 列
-- ========================================

ALTER TABLE `yoshop_crm_customer_claim_log`
ADD COLUMN `store_id` int(11) NOT NULL DEFAULT 0 AFTER `id`;
