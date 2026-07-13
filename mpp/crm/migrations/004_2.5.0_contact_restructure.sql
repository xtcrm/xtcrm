-- =============================================
-- CRM v2.6.0 — 联系人重构：手机号去重 + 多对多 + 个人信息
-- 注意：步骤1-2仅新库执行，已迁移的库跳过
-- =============================================

-- 1. 新增个人信息字段（仅新库执行，已有则忽略报错）
-- ALTER TABLE `yoshop_crm_contact` ADD COLUMN `birthday` varchar(10) DEFAULT '' COMMENT '生日';
-- ALTER TABLE `yoshop_crm_contact` ADD COLUMN `id_card` varchar(18) DEFAULT '' COMMENT '身份证号';
-- ALTER TABLE `yoshop_crm_contact` ADD COLUMN `hometown` varchar(50) DEFAULT '' COMMENT '籍贯';
-- ALTER TABLE `yoshop_crm_contact` ADD COLUMN `address` varchar(255) DEFAULT '' COMMENT '家庭住址';

-- 2. 手机号唯一索引（仅新库执行）
-- ALTER TABLE `yoshop_crm_contact` ADD UNIQUE KEY `uniq_store_mobile` (`store_id`, `mobile`);

-- 3. 联系人-客户关联表
CREATE TABLE IF NOT EXISTS `yoshop_crm_contact_customer` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `store_id` int(11) NOT NULL DEFAULT '0' COMMENT '租户ID',
  `contact_id` int(11) NOT NULL DEFAULT '0' COMMENT '联系人ID',
  `customer_id` int(11) NOT NULL DEFAULT '0' COMMENT '客户ID',
  `position` varchar(100) DEFAULT '' COMMENT '职位',
  `department` varchar(100) DEFAULT '' COMMENT '部门',
  `is_primary` tinyint(1) DEFAULT '0' COMMENT '是否首要联系人',
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_contact_customer` (`contact_id`, `customer_id`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_store_id` (`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='联系人-客户关联表';

-- 4. 迁移现有数据（幂等，重复执行不会重复插入）
INSERT IGNORE INTO `yoshop_crm_contact_customer` (`store_id`, `contact_id`, `customer_id`, `position`, `department`, `is_primary`, `create_time`, `update_time`)
SELECT `store_id`, `id`, `customer_id`, COALESCE(`position`, ''), COALESCE(`department`, ''), COALESCE(`is_primary`, 0), UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `yoshop_crm_contact`
WHERE `customer_id` > 0 AND `is_delete` = 0;

-- 5. 删除旧列（仅新库执行，旧列已删的跳过）
-- ALTER TABLE `yoshop_crm_contact`
--   DROP COLUMN `customer_id`,
--   DROP COLUMN `position`,
--   DROP COLUMN `department`,
--   DROP COLUMN `is_primary`;
