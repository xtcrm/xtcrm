-- =============================================
-- 票无忧 Phase 1 — 表结构调整
-- =============================================

-- 1. invoice_record 加进项/销项方向
-- ⚠️ 幂等守卫（2026-10 补）：迁移器**每个请求**都会重跑未记录过的迁移，裸 ADD COLUMN 撞 1060
-- 会中断整个文件 ⇒ 版本号落不了库 ⇒ 永远重跑（日志里从 6 月刷到现在）。判存为空转。
SET @exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='yoshop_invoice_record' AND COLUMN_NAME='direction');
SET @stmt = IF(@exists = 0, 'ALTER TABLE `yoshop_invoice_record` ADD COLUMN `direction` tinyint(1) DEFAULT ''2'' COMMENT ''1=进项 2=销项'' AFTER `invoice_type`, ADD COLUMN `qr_verified` tinyint(1) DEFAULT ''0'' COMMENT ''二维码已验证 0=否 1=是'' AFTER `direction`, ADD COLUMN `tax_verified` tinyint(1) DEFAULT ''0'' COMMENT ''国税已验真 0=否 1=是'' AFTER `qr_verified`, ADD KEY `idx_direction` (`direction`)', 'SELECT 1');
PREPARE s FROM @stmt;
EXECUTE s;
DEALLOCATE PREPARE s;

-- 2. 预警配置默认值（票无忧 Phase 1）
-- 税负率红线（百分比，整数，如 1 表示 1%）
INSERT IGNORE INTO `yoshop_config` (`store_id`, `config_type`, `config_name`, `config_value`, `create_time`, `update_time`)
VALUES (0, 'invoice_alert', 'tax_burden_min', '1', UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 预警开关
INSERT IGNORE INTO `yoshop_config` (`store_id`, `config_type`, `config_name`, `config_value`, `create_time`, `update_time`)
VALUES (0, 'invoice_alert', 'alert_enabled', '1', UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
