-- =============================================
-- 代开发票 Skill v2.5.0 — 建表
-- =============================================

-- 迁移追踪表
CREATE TABLE IF NOT EXISTS `tao_skill_migration` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `skill_name` varchar(50) NOT NULL DEFAULT '',
  `version` varchar(50) NOT NULL DEFAULT '',
  `filename` varchar(200) NOT NULL DEFAULT '',
  `executed_at` int(11) DEFAULT NULL,
  `store_id` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_skill_version` (`skill_name`, `version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Skill迁移记录';

DROP TABLE IF EXISTS `tao_invoice_company`;
CREATE TABLE `tao_invoice_company` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `store_id` int(11) NOT NULL DEFAULT '0' COMMENT '租户ID',
  `name` varchar(200) NOT NULL DEFAULT '' COMMENT '销方公司名称',
  `tax_number` varchar(50) NOT NULL DEFAULT '' COMMENT '销方税号',
  `address` varchar(500) DEFAULT '' COMMENT '注册地址',
  `phone` varchar(20) DEFAULT '' COMMENT '联系电话',
  `bank_name` varchar(100) DEFAULT '' COMMENT '开户银行',
  `bank_account` varchar(50) DEFAULT '' COMMENT '银行账号',
  `status` tinyint(1) DEFAULT '1' COMMENT '1=正常 0=禁用',
  `source` tinyint(1) DEFAULT '1' COMMENT '1=小程序自助 2=后台建档',
  `is_delete` tinyint(1) NOT NULL DEFAULT '0',
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_store_id` (`store_id`),
  KEY `idx_tax_number` (`tax_number`),
  UNIQUE KEY `uniq_store_tax` (`store_id`, `tax_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='销方公司档案';

DROP TABLE IF EXISTS `tao_invoice_company_member`;
CREATE TABLE `tao_invoice_company_member` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `store_id` int(11) NOT NULL DEFAULT '0' COMMENT '租户ID',
  `company_id` int(11) NOT NULL DEFAULT '0' COMMENT '公司ID',
  `member_id` int(11) NOT NULL DEFAULT '0' COMMENT '会员ID',
  `status` tinyint(1) DEFAULT '1' COMMENT '1=已绑定 0=已解绑',
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_company_id` (`company_id`),
  KEY `idx_member_id` (`member_id`),
  UNIQUE KEY `uniq_company_member` (`company_id`, `member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='公司会员绑定';

DROP TABLE IF EXISTS `tao_invoice_record`;
CREATE TABLE `tao_invoice_record` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `store_id` int(11) NOT NULL DEFAULT '0' COMMENT '租户ID',
  `member_id` int(11) NOT NULL DEFAULT '0' COMMENT '销方会员ID(申请人)',
  `company_id` int(11) NOT NULL DEFAULT '0' COMMENT '销方公司ID',

  `seller_name` varchar(200) NOT NULL DEFAULT '' COMMENT '销方名称',
  `seller_tax_number` varchar(50) NOT NULL DEFAULT '' COMMENT '销方税号',
  `seller_address` varchar(500) DEFAULT '' COMMENT '销方地址电话',
  `seller_bank` varchar(200) DEFAULT '' COMMENT '销方开户行及账号',

  `buyer_name` varchar(200) NOT NULL DEFAULT '' COMMENT '购方名称',
  `buyer_tax_number` varchar(50) NOT NULL DEFAULT '' COMMENT '购方税号',
  `buyer_address` varchar(500) DEFAULT '' COMMENT '购方地址电话',
  `buyer_bank` varchar(200) DEFAULT '' COMMENT '购方开户行及账号',
  `buyer_email` varchar(100) DEFAULT '' COMMENT '购方邮箱',
  `buyer_phone` varchar(20) DEFAULT '' COMMENT '购方手机号',

  `invoice_type` tinyint(1) DEFAULT '2' COMMENT '1=增值税专票 2=普票 3=电子发票 4=数电票',
  `amount` decimal(12,2) DEFAULT '0.00' COMMENT '不含税金额',
  `tax_rate` decimal(10,4) DEFAULT '0.00' COMMENT '税率（小数，如0.06表示6%）',
  `tax_amount` decimal(12,2) DEFAULT '0.00' COMMENT '税额',
  `total_amount` decimal(12,2) DEFAULT '0.00' COMMENT '价税合计',
  `goods_info` text COMMENT '商品明细JSON [{name,model,unit,quantity,price,amount}]',
  `remark` varchar(500) DEFAULT '' COMMENT '备注',

  `attach_urls` text COMMENT '附件图片JSON数组',
  `ocr_raw_text` text COMMENT 'OCR原始文字/AI输入文本',
  `ocr_images` text COMMENT '原始上传图片JSON数组',

  `status` tinyint(1) DEFAULT '0' COMMENT '0=待审核 1=审核通过(待开票) 2=已开票(已完成) 3=已作废 4=已驳回',
  `audit_remark` varchar(500) DEFAULT '' COMMENT '审核备注/驳回原因',
  `audit_time` int(11) DEFAULT NULL,
  `audit_user_id` int(11) DEFAULT '0',

  `invoice_code` varchar(50) DEFAULT '' COMMENT '发票代码',
  `invoice_number` varchar(50) DEFAULT '' COMMENT '发票号码',
  `invoice_date` date DEFAULT NULL COMMENT '开票日期',
  `invoice_image_url` varchar(500) DEFAULT '' COMMENT '收据/发票图片URL',
  `invoice_text` text COMMENT '开票文字描述(票面关键信息)',
  `invoice_pdf_url` varchar(500) DEFAULT '' COMMENT '发票PDF URL',

  `notify_status` tinyint(1) DEFAULT '0' COMMENT '0=未通知 1=已短信通知 2=通知失败',
  `notify_time` int(11) DEFAULT NULL,

  `is_delete` tinyint(1) NOT NULL DEFAULT '0',
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_store_id` (`store_id`),
  KEY `idx_member_id` (`member_id`),
  KEY `idx_company_id` (`company_id`),
  KEY `idx_status` (`status`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='代开票申请记录';
