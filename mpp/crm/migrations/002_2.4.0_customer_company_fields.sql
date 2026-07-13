-- =============================================
-- CRM v2.6.0 — 客户表增加工商信息字段
-- =============================================

ALTER TABLE `yoshop_crm_customer`
  ADD COLUMN `telephone` varchar(30) DEFAULT '' COMMENT '公司电话' AFTER `website`,
  ADD COLUMN `email` varchar(100) DEFAULT '' COMMENT '公司邮箱' AFTER `telephone`,
  ADD COLUMN `legal_representative` varchar(50) DEFAULT '' COMMENT '法定代表人' AFTER `email`,
  ADD COLUMN `registered_capital` varchar(50) DEFAULT '' COMMENT '注册资本' AFTER `legal_representative`,
  ADD COLUMN `paid_in_capital` varchar(50) DEFAULT '' COMMENT '实缴资本' AFTER `registered_capital`,
  ADD COLUMN `established_date` varchar(20) DEFAULT '' COMMENT '成立日期' AFTER `paid_in_capital`,
  ADD COLUMN `registration_status` varchar(30) DEFAULT '' COMMENT '登记状态' AFTER `established_date`,
  ADD COLUMN `business_registration_no` varchar(50) DEFAULT '' COMMENT '工商注册号' AFTER `registration_status`,
  ADD COLUMN `organization_code` varchar(50) DEFAULT '' COMMENT '组织机构代码' AFTER `business_registration_no`,
  ADD COLUMN `business_term` varchar(100) DEFAULT '' COMMENT '营业期限' AFTER `organization_code`,
  ADD COLUMN `taxpayer_qualification` varchar(30) DEFAULT '' COMMENT '纳税人资质' AFTER `business_term`,
  ADD COLUMN `insured_count` varchar(20) DEFAULT '' COMMENT '参保人数' AFTER `taxpayer_qualification`,
  ADD COLUMN `approval_date` varchar(20) DEFAULT '' COMMENT '核准日期' AFTER `insured_count`,
  ADD COLUMN `registration_authority` varchar(100) DEFAULT '' COMMENT '登记机关' AFTER `approval_date`,
  ADD COLUMN `national_industry` varchar(100) DEFAULT '' COMMENT '国标行业' AFTER `registration_authority`,
  ADD COLUMN `registered_address` varchar(500) DEFAULT '' COMMENT '注册地址' AFTER `national_industry`,
  ADD COLUMN `business_scope` text COMMENT '经营范围' AFTER `registered_address`,
  ADD COLUMN `introduction` text COMMENT '公司简介' AFTER `business_scope`;
