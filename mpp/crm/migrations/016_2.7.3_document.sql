-- =============================================
-- CRM v2.7.3 — 客户文件袋
-- =============================================

CREATE TABLE IF NOT EXISTS `yoshop_crm_document` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `store_id` int unsigned NOT NULL DEFAULT 0 COMMENT '租户ID',
  `customer_id` int unsigned NOT NULL DEFAULT 0 COMMENT '客户ID',
  `quotation_id` int unsigned NOT NULL DEFAULT 0 COMMENT '报价单ID(可选)',
  `order_id` int unsigned NOT NULL DEFAULT 0 COMMENT '订单ID(可选)',
  `contract_id` int unsigned NOT NULL DEFAULT 0 COMMENT '合同ID(可选)',
  `file_id` int unsigned NOT NULL DEFAULT 0 COMMENT 'upload_file.file_id',
  `category` varchar(50) NOT NULL DEFAULT '其他' COMMENT '分类:合同/发票/方案/资质/其他',
  `title` varchar(200) NOT NULL DEFAULT '' COMMENT '文件标题',
  `remark` text COMMENT '备注',
  `owner_user_id` int unsigned NOT NULL DEFAULT 0 COMMENT '上传者',
  `is_delete` tinyint unsigned NOT NULL DEFAULT 0 COMMENT '软删除',
  `create_time` int unsigned NOT NULL DEFAULT 0,
  `update_time` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_customer` (`store_id`, `customer_id`, `is_delete`),
  KEY `idx_quotation` (`store_id`, `quotation_id`),
  KEY `idx_order` (`store_id`, `order_id`),
  KEY `idx_contract` (`store_id`, `contract_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='客户文件袋';
