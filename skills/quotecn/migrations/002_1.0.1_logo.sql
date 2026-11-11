-- ==========================================
-- quote-cn v1.0.1 Logo 功能
-- ==========================================

-- ⚠️ 加 IGNORE（2026-10）：config 表在 (store_id, config_type, config_name) 上有唯一键，
-- 重跑撞 1062 会中断整个文件 ⇒ 版本号落不了库 ⇒ 永远重跑。IGNORE 让重跑成为空转。
INSERT IGNORE INTO `yoshop_config` (`config_type`, `config_name`, `config_value`, `store_id`, `create_time`, `update_time`) VALUES
('quotation_cn_template', 'company_logo', '', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('quotation_cn_template', 'logo_file_id', '0', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
