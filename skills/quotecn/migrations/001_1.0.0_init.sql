-- ==========================================
-- quote-cn v1.0.0 初始安装
-- ==========================================

-- 1. 扩展报价表
ALTER TABLE `yoshop_crm_quotation`
  ADD COLUMN `tax_rate` decimal(5,2) DEFAULT 0.00 COMMENT '增值税率(%)' AFTER `final_amount`,
  ADD COLUMN `tax_amount` decimal(12,2) DEFAULT 0.00 COMMENT '增值税额' AFTER `tax_rate`,
  ADD COLUMN `cn_amount_text` varchar(100) DEFAULT '' COMMENT '大写金额' AFTER `tax_amount`;

-- 2. 菜单
INSERT INTO `yoshop_store_menu` (`menu_id`, `name`, `path`, `parent_id`, `module`, `fun_type`, `sort`, `create_time`, `update_time`) VALUES
(10565, '中文报价单', '/crm/quotation/cn-preview', 10311, 10, 'crm', 200, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(10566, '中文报价预览', '/crm/quotation_cn/preview', 10565, 20, 'crm', 100, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(10567, '导出中文报价', '/crm/quotation_cn/exportPdf', 10565, 20, 'crm', 110, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(10568, '保存报价模板', '/crm/quotation_cn/saveTemplate', 10565, 20, 'crm', 120, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 4. 角色绑定
INSERT IGNORE INTO `yoshop_store_role_menu` (`role_id`, `menu_id`) VALUES
(1, 10565), (1, 10566), (1, 10567), (1, 10568);
