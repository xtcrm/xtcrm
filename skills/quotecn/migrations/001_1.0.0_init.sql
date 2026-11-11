-- ==========================================
-- quote-cn v1.0.0 初始安装
-- ==========================================

-- 1. 扩展报价表
-- ⚠️ 幂等守卫（2026-10 补）：迁移器**每个请求**重跑未记录的迁移，裸 ADD COLUMN 撞"列已存在"
-- 会中断整个文件 ⇒ 版本号落不了库 ⇒ 永远重跑（日志从 9 月刷到现在）。
-- 三列各写一段：某段失败时其余已生效，重跑时守卫判存 → SELECT 1 空转。
SET @exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='yoshop_crm_quotation' AND COLUMN_NAME='tax_rate');
SET @stmt = IF(@exists = 0, 'ALTER TABLE `yoshop_crm_quotation` ADD COLUMN `tax_rate` decimal(5,2) DEFAULT 0.00 COMMENT ''增值税率(%)'' AFTER `final_amount`', 'SELECT 1');
PREPARE s FROM @stmt;
EXECUTE s;
DEALLOCATE PREPARE s;

SET @exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='yoshop_crm_quotation' AND COLUMN_NAME='tax_amount');
SET @stmt = IF(@exists = 0, 'ALTER TABLE `yoshop_crm_quotation` ADD COLUMN `tax_amount` decimal(12,2) DEFAULT 0.00 COMMENT ''增值税额'' AFTER `tax_rate`', 'SELECT 1');
PREPARE s FROM @stmt;
EXECUTE s;
DEALLOCATE PREPARE s;

SET @exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='yoshop_crm_quotation' AND COLUMN_NAME='cn_amount_text');
SET @stmt = IF(@exists = 0, 'ALTER TABLE `yoshop_crm_quotation` ADD COLUMN `cn_amount_text` varchar(100) DEFAULT '''' COMMENT ''大写金额'' AFTER `tax_amount`', 'SELECT 1');
PREPARE s FROM @stmt;
EXECUTE s;
DEALLOCATE PREPARE s;

-- 2. 菜单
-- ⚠️ 改成 INSERT IGNORE（2026-10）：menu_id 是显式写的，重跑会撞主键 1062 → 同样中断整个文件。
-- 上面守卫化之后本文件才可能走到这里，所以这行必须也幂等，否则仍然永远重跑。
INSERT IGNORE INTO `yoshop_store_menu` (`menu_id`, `name`, `path`, `parent_id`, `module`, `fun_type`, `sort`, `create_time`, `update_time`) VALUES
(10565, '中文报价单', '/crm/quotation/cn-preview', 10311, 10, 'crm', 200, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(10566, '中文报价预览', '/crm/quotation_cn/preview', 10565, 20, 'crm', 100, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(10567, '导出中文报价', '/crm/quotation_cn/exportPdf', 10565, 20, 'crm', 110, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(10568, '保存报价模板', '/crm/quotation_cn/saveTemplate', 10565, 20, 'crm', 120, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 4. 角色绑定
INSERT IGNORE INTO `yoshop_store_role_menu` (`role_id`, `menu_id`) VALUES
(1, 10565), (1, 10566), (1, 10567), (1, 10568);
