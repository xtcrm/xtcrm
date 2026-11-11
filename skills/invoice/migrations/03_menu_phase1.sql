-- =============================================
-- 票无忧 — 完整菜单（父级：应用 menu_id=21000）
-- =============================================

-- 应用一级菜单（如不存在则创建）
INSERT IGNORE INTO `yoshop_store_menu` (`menu_id`, `module`, `fun_type`, `name`, `path`, `action_mark`, `parent_id`, `sort`, `create_time`, `update_time`) VALUES
(21000, 10, 'crm', '应用', '/app', '', 0, 200, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 票无忧二级菜单
INSERT IGNORE INTO `yoshop_store_menu` (`menu_id`, `module`, `fun_type`, `name`, `path`, `action_mark`, `parent_id`, `sort`, `create_time`, `update_time`) VALUES
(21001, 10, 'crm', '票无忧',       '/app/invoice',                  '', 21000, 100, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(21009, 10, 'crm', '税负仪表盘',   '/app/invoice/dashboard',        '', 21001, 100, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(21002, 10, 'crm', '发票管理',     '/app/invoice/list',             '', 21001, 110, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(21003, 10, 'crm', '发票详情',     '/app/invoice/detail',           '', 21001, 115, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(21004, 10, 'crm', '开票公司',     '/app/invoice/company',          '', 21001, 120, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(21005, 10, 'crm', '公司详情',     '/app/invoice/company/detail',   '', 21001, 125, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(21006, 10, 'crm', '短信配置',     '/app/invoice/sms',              '', 21001, 130, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(21010, 10, 'crm', '预警配置',     '/app/invoice/alert',            '', 21001, 140, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(21007, 10, 'crm', 'OCR 配置',     '/app/invoice/ocr',              '', 21001, 150, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
