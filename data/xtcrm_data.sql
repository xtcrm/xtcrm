/*
Navicat MySQL Data Transfer

Source Server         : root
Source Server Version : 50738
Source Host           : localhost:3306
Source Database       : crm

Target Server Type    : MYSQL
Target Server Version : 50738
File Encoding         : 65001

Date: 2026-06-14 16:06:24
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for tao_admin_user
-- ----------------------------
DROP TABLE IF EXISTS `tao_admin_user`;
CREATE TABLE `tao_admin_user` (
  `admin_user_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `user_name` varchar(255) NOT NULL DEFAULT '' COMMENT '用户名',
  `password` varchar(255) NOT NULL DEFAULT '' COMMENT '登录密码',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) NOT NULL COMMENT '更新时间',
  PRIMARY KEY (`admin_user_id`),
  KEY `user_name` (`user_name`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=10002 DEFAULT CHARSET=utf8 COMMENT='超管用户记录表';

-- ----------------------------
-- Records of tao_admin_user
-- ----------------------------
INSERT INTO `tao_admin_user` VALUES ('10001', 'admin', '$2y$10$KSKnFNZZ.NRn6OdSEXKyPO9.1nz.Xv86.TgcjDBibN8C3br7pSdoe', '1614556800', '1698206078');

-- ----------------------------
-- Table structure for tao_config
-- ----------------------------
DROP TABLE IF EXISTS `tao_config`;
CREATE TABLE `tao_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `store_id` int(11) NOT NULL DEFAULT '0' COMMENT '0=系统默认(全局), >0=租户自定义',
  `config_type` varchar(50) NOT NULL DEFAULT '' COMMENT '字典类型: industry/bank/service_type/invoice_type/doc_category/opportunity_type/payment_cycle',
  `config_name` varchar(100) NOT NULL DEFAULT '' COMMENT '显示名称',
  `config_value` varchar(500) DEFAULT NULL COMMENT '附加值(JSON)',
  `sort_order` int(11) DEFAULT '100' COMMENT '排序',
  `is_system` tinyint(1) DEFAULT '1' COMMENT '0=租户自定义 1=系统预置(不可删)',
  `status` tinyint(1) DEFAULT '1' COMMENT '1=启用 0=禁用',
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_type_name` (`store_id`,`config_type`,`config_name`),
  KEY `idx_config_type` (`config_type`)
) ENGINE=InnoDB AUTO_INCREMENT=326 DEFAULT CHARSET=utf8mb4 COMMENT='全局字典配置表';

-- ----------------------------
-- Records of tao_config
-- ----------------------------
INSERT INTO `tao_config` VALUES ('263', '10001', 'industry', '科技型', '1', '100', '0', '1', '1781403082', '1781403082');
INSERT INTO `tao_config` VALUES ('264', '10001', 'industry', '餐饮连锁', '2', '110', '0', '1', '1781403082', '1781403082');
INSERT INTO `tao_config` VALUES ('265', '10001', 'industry', '现代服务', '3', '120', '0', '1', '1781403082', '1781403082');
INSERT INTO `tao_config` VALUES ('266', '10001', 'industry', '跨境电商', '4', '130', '0', '1', '1781403082', '1781403082');
INSERT INTO `tao_config` VALUES ('267', '10001', 'industry', '建筑', '5', '140', '0', '1', '1781403082', '1781403082');
INSERT INTO `tao_config` VALUES ('268', '10001', 'industry', '制造', '6', '150', '0', '1', '1781403082', '1781403082');
INSERT INTO `tao_config` VALUES ('269', '10001', 'industry', '贸易', '7', '160', '0', '1', '1781403082', '1781403082');
INSERT INTO `tao_config` VALUES ('270', '10001', 'industry', '教育培训', '8', '170', '0', '1', '1781403082', '1781403082');
INSERT INTO `tao_config` VALUES ('271', '10001', 'industry', '医疗健康', '9', '180', '0', '1', '1781403082', '1781403082');
INSERT INTO `tao_config` VALUES ('272', '10001', 'industry', '物流运输', '10', '190', '0', '1', '1781403082', '1781403082');
INSERT INTO `tao_config` VALUES ('273', '10001', 'industry', '房地产', '11', '200', '0', '1', '1781403082', '1781403082');
INSERT INTO `tao_config` VALUES ('274', '10001', 'industry', '农林牧渔', '12', '210', '0', '1', '1781403082', '1781403082');
INSERT INTO `tao_config` VALUES ('275', '10001', 'industry', '文化传媒', '13', '220', '0', '1', '1781403082', '1781403082');
INSERT INTO `tao_config` VALUES ('276', '10001', 'industry', '其他', '14', '230', '0', '1', '1781403082', '1781403082');
INSERT INTO `tao_config` VALUES ('277', '10001', 'customer_level', 'A级-战略', '1', '100', '0', '1', '1781403082', '1781403082');
INSERT INTO `tao_config` VALUES ('278', '10001', 'customer_level', 'B级-重要', '2', '110', '0', '1', '1781403082', '1781403082');
INSERT INTO `tao_config` VALUES ('279', '10001', 'customer_level', 'C级-普通', '3', '120', '0', '1', '1781403082', '1781403082');
INSERT INTO `tao_config` VALUES ('280', '10001', 'customer_level', 'D级-潜在', '4', '130', '0', '1', '1781403082', '1781403082');
INSERT INTO `tao_config` VALUES ('281', '10001', 'customer_source', '展会', '1', '100', '0', '1', '1781403082', '1781403082');
INSERT INTO `tao_config` VALUES ('282', '10001', 'customer_source', '客户介绍', '2', '110', '0', '1', '1781403082', '1781403082');
INSERT INTO `tao_config` VALUES ('283', '10001', 'customer_source', '网络搜索', '3', '120', '0', '1', '1781403082', '1781403082');
INSERT INTO `tao_config` VALUES ('284', '10001', 'customer_source', '电话来访', '4', '130', '0', '1', '1781403082', '1781403082');
INSERT INTO `tao_config` VALUES ('285', '10001', 'customer_source', '广告投放', '5', '140', '0', '1', '1781403082', '1781403082');
INSERT INTO `tao_config` VALUES ('286', '10001', 'customer_source', '协会推荐', '6', '150', '0', '1', '1781403082', '1781403082');
INSERT INTO `tao_config` VALUES ('287', '10001', 'customer_source', '陌拜', '7', '160', '0', '1', '1781403082', '1781403082');
INSERT INTO `tao_config` VALUES ('288', '10001', 'customer_source', '其他', '8', '170', '0', '1', '1781403082', '1781403082');
INSERT INTO `tao_config` VALUES ('289', '10001', 'customer_group', '塑料油墨', '1', '100', '0', '1', '1781403082', '1781403082');
INSERT INTO `tao_config` VALUES ('290', '10001', 'customer_group', '金属油墨', '2', '110', '0', '1', '1781403082', '1781403082');
INSERT INTO `tao_config` VALUES ('291', '10001', 'customer_group', 'UV油墨', '3', '120', '0', '1', '1781403082', '1781403082');
INSERT INTO `tao_config` VALUES ('292', '10001', 'customer_group', 'PCB油墨', '4', '130', '0', '1', '1781403082', '1781403082');
INSERT INTO `tao_config` VALUES ('293', '10001', 'customer_group', 'FPC油墨', '5', '140', '0', '1', '1781403082', '1781403082');
INSERT INTO `tao_config` VALUES ('294', '10001', 'customer_group', '包装油墨', '6', '150', '0', '1', '1781403082', '1781403082');
INSERT INTO `tao_config` VALUES ('295', '10001', 'customer_group', '户外广告', '7', '160', '0', '1', '1781403082', '1781403082');
INSERT INTO `tao_config` VALUES ('296', '10001', 'customer_group', '玩具油墨', '8', '170', '0', '1', '1781403082', '1781403082');
INSERT INTO `tao_config` VALUES ('297', '10001', 'customer_group', '体育用品', '9', '180', '0', '1', '1781403082', '1781403082');
INSERT INTO `tao_config` VALUES ('298', '10001', 'customer_group', '高遮盖', '10', '190', '0', '1', '1781403082', '1781403082');
INSERT INTO `tao_config` VALUES ('299', '10001', 'customer_group', '其他', '11', '200', '0', '1', '1781403082', '1781403082');
INSERT INTO `tao_config` VALUES ('300', '10001', 'follow_type', '电话', '1', '100', '0', '1', '1781403082', '1781403082');
INSERT INTO `tao_config` VALUES ('301', '10001', 'follow_type', '拜访', '2', '110', '0', '1', '1781403082', '1781403082');
INSERT INTO `tao_config` VALUES ('302', '10001', 'follow_type', '微信', '3', '120', '0', '1', '1781403082', '1781403082');
INSERT INTO `tao_config` VALUES ('303', '10001', 'follow_type', '邮件', '4', '130', '0', '1', '1781403082', '1781403082');
INSERT INTO `tao_config` VALUES ('304', '10001', 'follow_type', '展会见面', '5', '140', '0', '1', '1781403082', '1781403082');
INSERT INTO `tao_config` VALUES ('305', '10001', 'follow_type', '线上会议', '6', '150', '0', '1', '1781403082', '1781403082');
INSERT INTO `tao_config` VALUES ('306', '10001', 'follow_type', '其他', '7', '160', '0', '1', '1781403082', '1781403082');
INSERT INTO `tao_config` VALUES ('307', '10001', 'follow_result', '有效', '1', '100', '0', '1', '1781403082', '1781403082');
INSERT INTO `tao_config` VALUES ('308', '10001', 'follow_result', '无效', '2', '110', '0', '1', '1781403082', '1781403082');
INSERT INTO `tao_config` VALUES ('309', '10001', 'follow_result', '待定', '3', '120', '0', '1', '1781403082', '1781403082');
INSERT INTO `tao_config` VALUES ('310', '10001', 'product_category', '塑料油墨', '1', '100', '0', '1', '1781403082', '1781403082');
INSERT INTO `tao_config` VALUES ('311', '10001', 'product_category', '金属油墨', '2', '110', '0', '1', '1781403082', '1781403082');
INSERT INTO `tao_config` VALUES ('312', '10001', 'product_category', 'UV油墨', '3', '120', '0', '1', '1781403082', '1781403082');
INSERT INTO `tao_config` VALUES ('313', '10001', 'product_category', 'PCB油墨', '4', '130', '0', '1', '1781403082', '1781403082');
INSERT INTO `tao_config` VALUES ('314', '10001', 'product_category', 'FPC油墨', '5', '140', '0', '1', '1781403082', '1781403082');
INSERT INTO `tao_config` VALUES ('315', '10001', 'product_category', '包装油墨', '6', '150', '0', '1', '1781403082', '1781403082');
INSERT INTO `tao_config` VALUES ('316', '10001', 'product_category', '户外广告', '7', '160', '0', '1', '1781403082', '1781403082');
INSERT INTO `tao_config` VALUES ('317', '10001', 'product_category', '玩具', '8', '170', '0', '1', '1781403082', '1781403082');
INSERT INTO `tao_config` VALUES ('318', '10001', 'product_category', '体育用品', '9', '180', '0', '1', '1781403082', '1781403082');
INSERT INTO `tao_config` VALUES ('319', '10001', 'product_category', '高遮盖', '10', '190', '0', '1', '1781403082', '1781403082');
INSERT INTO `tao_config` VALUES ('320', '10001', 'product_category', '特殊油墨', '11', '200', '0', '1', '1781403082', '1781403082');
INSERT INTO `tao_config` VALUES ('321', '10001', 'product_category', '助剂/辅料', '12', '210', '0', '1', '1781403082', '1781403082');
INSERT INTO `tao_config` VALUES ('322', '10001', 'currency', 'CNY', '1', '100', '0', '1', '1781403083', '1781403083');
INSERT INTO `tao_config` VALUES ('323', '10001', 'currency', 'USD', '2', '110', '0', '1', '1781403083', '1781403083');
INSERT INTO `tao_config` VALUES ('324', '10001', 'currency', 'EUR', '3', '120', '0', '1', '1781403083', '1781403083');
INSERT INTO `tao_config` VALUES ('325', '10001', 'currency', 'JPY', '4', '130', '0', '1', '1781403083', '1781403083');

-- ----------------------------
-- Table structure for tao_crm_agent_log
-- ----------------------------
DROP TABLE IF EXISTS `tao_crm_agent_log`;
CREATE TABLE `tao_crm_agent_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` varchar(50) NOT NULL,
  `intent` varchar(200) DEFAULT NULL COMMENT '用户意图',
  `messages` json DEFAULT NULL COMMENT '完整对话记录',
  `tools_called` json DEFAULT NULL COMMENT '[{name,params,result}]',
  `result` json DEFAULT NULL COMMENT '最终输出',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1=成功 0=失败',
  `error` varchar(500) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `create_time` int(11) NOT NULL,
  `store_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_session` (`session_id`),
  KEY `idx_user_time` (`user_id`,`create_time`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COMMENT='Agent执行日志';

-- ----------------------------
-- Records of tao_crm_agent_log
-- ----------------------------

-- ----------------------------
-- Table structure for tao_crm_ai_suggestion
-- ----------------------------
DROP TABLE IF EXISTS `tao_crm_ai_suggestion`;
CREATE TABLE `tao_crm_ai_suggestion` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `insight_id` int(11) DEFAULT NULL COMMENT '关联 insight',
  `source` varchar(50) NOT NULL COMMENT 'analyze/portrait/insight/followup_suggest/agent',
  `target_type` varchar(50) DEFAULT NULL,
  `target_id` int(11) DEFAULT NULL,
  `prompt_hash` varchar(64) DEFAULT NULL COMMENT 'prompt SHA256（调试用）',
  `content` json NOT NULL COMMENT 'AI 建议内容',
  `accepted` tinyint(1) DEFAULT NULL COMMENT 'null=未操作 0=拒绝 1=采纳',
  `accepted_by` int(11) DEFAULT NULL,
  `accepted_time` int(11) DEFAULT NULL,
  `outcome` varchar(50) DEFAULT NULL COMMENT 'converted/lost/pending/ignored',
  `outcome_data` json DEFAULT NULL,
  `outcome_checked_at` int(11) DEFAULT NULL,
  `create_time` int(11) NOT NULL,
  `store_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_insight` (`insight_id`),
  KEY `idx_target` (`target_type`,`target_id`),
  KEY `idx_source_outcome` (`source`,`outcome`),
  KEY `idx_accepted` (`accepted`,`create_time`)
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COMMENT='AI建议追踪';

-- ----------------------------
-- Records of tao_crm_ai_suggestion
-- ----------------------------
INSERT INTO `tao_crm_ai_suggestion` VALUES ('20', null, 'portrait', 'customer', '60', null, '{\"text\": \"该客户为高价值VIP客户，年均采购额约200万，建议：1）每月定期拜访 2）关注新品需求 3）提供专属价格方案\"}', '1', '10001', '1781150400', 'converted', null, null);
INSERT INTO `tao_crm_ai_suggestion` VALUES ('21', null, 'portrait', 'customer', '61', null, '{\"text\": \"该客户为招投标型大客户，Q3有明确采购需求，建议：1）提前准备投标文件 2）了解竞品策略 3）提供差异化方案\"}', '1', '10001', '1781236800', 'pending', null, null);
INSERT INTO `tao_crm_ai_suggestion` VALUES ('22', null, 'followup_suggest', 'customer', '62', null, '{\"text\": \"客户对UV胶印油墨ECO系列感兴趣，建议：1）强调环保认证 2）提供竞品对比 3）发送免费样品\"}', null, null, null, null, null, null);
INSERT INTO `tao_crm_ai_suggestion` VALUES ('23', null, 'followup_suggest', 'customer', '56', null, '{\"text\": \"客户对产品色差问题不满，建议：1）主动上门了解 2）提供补偿方案 3）承诺质量提升\"}', '0', '10001', '1778817600', 'lost', null, null);
INSERT INTO `tao_crm_ai_suggestion` VALUES ('24', null, 'analyze', 'customer', '50', null, '{\"text\": \"恒达彩印是典型的展会获客A级客户，决策层清晰，对价格不敏感但对品质要求高。建议采用高品质+技术服务策略。\"}', null, null, null, null, null, null);
INSERT INTO `tao_crm_ai_suggestion` VALUES ('30', null, 'portrait', 'customer', '86', null, '{\"text\": \"蓝海印务是VIP级客户，月均采购10万+。建议：1）设立专属客服 2）每月技术交流 3）优先满足交期需求\"}', '1', '10001', '1781236800', 'converted', null, null);
INSERT INTO `tao_crm_ai_suggestion` VALUES ('31', null, 'portrait', 'customer', '78', null, '{\"text\": \"天印包装为季度框架客户，采购稳定。建议：1）提前1个月沟通下季度计划 2）提供季度优惠方案 3）定期品质回访\"}', '1', '10001', '1781150400', 'converted', null, null);
INSERT INTO `tao_crm_ai_suggestion` VALUES ('32', null, 'followup_suggest', 'customer', '70', null, '{\"text\": \"新艺印务对UV PRO感兴趣，建议：1）发送UV PRO样品 2）提供与其他系列的对比 3）强调快速固化优势\"}', null, null, null, null, null, null);
INSERT INTO `tao_crm_ai_suggestion` VALUES ('33', null, 'analyze', 'customer', '80', null, '{\"text\": \"东北印务采购量持续下滑，可能是行业下行或竞品抢占。建议深入了解原因，如无法挽回则考虑降级处理。\"}', '0', '10001', '1772769600', 'lost', null, null);
INSERT INTO `tao_crm_ai_suggestion` VALUES ('34', null, 'followup_suggest', 'customer', '87', null, '{\"text\": \"光谷印科技术需求特殊，建议：1）安排技术专家上门 2）提供定制化方案 3）强调技术壁垒优势\"}', '1', '10001', '1781064000', 'pending', null, null);
INSERT INTO `tao_crm_ai_suggestion` VALUES ('35', null, 'portrait', 'customer', '73', null, '{\"text\": \"金陵彩印专注政府订单，特点是：批量大、流程长、利润稳定。建议走招投标流程，提前准备资质文件。\"}', null, null, null, 'pending', null, null);
INSERT INTO `tao_crm_ai_suggestion` VALUES ('36', null, 'analyze', 'customer', '81', null, '{\"text\": \"春城彩印连续多次联系不上，建议标记为无效客户或转公海，将精力集中到更有价值的客户。\"}', '1', '10068', '1771905600', 'converted', null, null);
INSERT INTO `tao_crm_ai_suggestion` VALUES ('37', null, 'followup_suggest', 'customer', '95', null, '{\"text\": \"西夏印务对UV LED感兴趣，西北市场是蓝海。建议：1）提供有竞争力的价格 2）帮助解决物流问题 3）提供本地化服务\"}', null, null, null, null, null, null);
INSERT INTO `tao_crm_ai_suggestion` VALUES ('38', null, 'analyze', 'customer', '57', null, '{\"text\": \"博罗包装已开始接触竞品，客户对价格敏感。建议：1）差异化竞争（技术服务）2）承诺交期 3）提供阶梯价格\"}', null, null, null, null, null, null);
INSERT INTO `tao_crm_ai_suggestion` VALUES ('39', null, 'insight', null, '101', null, '{\"text\": \"金平彩印流失风险高，建议紧急处理：电话道歉+上门拜访+补偿方案。优先处理色差问题根源。\"}', null, null, null, null, null, null);
INSERT INTO `tao_crm_ai_suggestion` VALUES ('40', null, 'portrait', 'customer', '60', null, '{\"text\": \"华艺集团是战略级客户，年均采购200万。核心维护策略：高层定期互访、新品优先试用、定制化服务团队\"}', '1', '10001', '1781323200', 'pending', null, null);
INSERT INTO `tao_crm_ai_suggestion` VALUES ('41', null, 'analyze', 'customer', '74', null, '{\"text\": \"华彩印务为技术导向型客户，环保意识强。应突出产品的环保认证和VOC排放优势，提供水性系列方案。\"}', null, null, null, null, null, null);
INSERT INTO `tao_crm_ai_suggestion` VALUES ('42', null, 'followup_suggest', 'customer', '88', null, '{\"text\": \"绿城包装为初创企业预算有限，建议：1）提供入门级产品方案 2）小批量起订 3）分期付款选项\"}', null, null, null, null, null, null);
INSERT INTO `tao_crm_ai_suggestion` VALUES ('43', null, 'analyze', 'customer', '93', null, '{\"text\": \"赣江印物对溶剂型油墨需求明确但竞争激烈，建议用品质对比+样品测试+价格优势三位一体策略。\"}', null, null, null, null, null, null);
INSERT INTO `tao_crm_ai_suggestion` VALUES ('44', null, 'portrait', 'customer', '71', null, '{\"text\": \"彩弘包装为高端客户，品质导向明确。不应打价格战，应强调产品品质稳定性和技术服务能力。\"}', null, null, null, null, null, null);

-- ----------------------------
-- Table structure for tao_crm_approval
-- ----------------------------
DROP TABLE IF EXISTS `tao_crm_approval`;
CREATE TABLE `tao_crm_approval` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '审批ID',
  `store_id` int(11) NOT NULL DEFAULT '0' COMMENT '租户ID',
  `target_type` varchar(50) NOT NULL DEFAULT '' COMMENT 'quotation/order/contract',
  `target_id` int(11) NOT NULL DEFAULT '0' COMMENT '对象ID',
  `submit_user_id` int(11) DEFAULT NULL COMMENT '提交人',
  `approve_user_id` int(11) DEFAULT NULL COMMENT '审批人',
  `approve_level` tinyint(1) DEFAULT '1' COMMENT '1=直属上级 2=总经理',
  `amount` decimal(12,2) DEFAULT '0.00' COMMENT '审批时金额',
  `status` tinyint(1) DEFAULT '1' COMMENT '1=待审批 2=已通过 3=已驳回',
  `comment` varchar(500) DEFAULT NULL COMMENT '审批意见',
  `approve_time` int(11) DEFAULT NULL,
  `create_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_store_id` (`store_id`),
  KEY `idx_target` (`target_type`,`target_id`),
  KEY `idx_approve_user` (`approve_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='CRM审批记录';

-- ----------------------------
-- Records of tao_crm_approval
-- ----------------------------

-- ----------------------------
-- Table structure for tao_crm_company_profile
-- ----------------------------
DROP TABLE IF EXISTS `tao_crm_company_profile`;
CREATE TABLE `tao_crm_company_profile` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `store_id` int(11) NOT NULL,
  `company_intro` text,
  `core_strengths` text,
  `tech_capability` text,
  `service_commitment` text,
  `contact_info` text,
  `contact_qrcode` varchar(255) DEFAULT '' COMMENT '联系方式二维码',
  `case_stories` text,
  `brand_voice` varchar(32) DEFAULT '专业',
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_store` (`store_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Records of tao_crm_company_profile
-- ----------------------------

-- ----------------------------
-- Table structure for tao_crm_contact
-- ----------------------------
DROP TABLE IF EXISTS `tao_crm_contact`;
CREATE TABLE `tao_crm_contact` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '联系人ID',
  `store_id` int(11) NOT NULL DEFAULT '0' COMMENT '租户ID',
  `customer_id` int(11) NOT NULL DEFAULT '0' COMMENT '所属客户ID',
  `contact_name` varchar(100) NOT NULL DEFAULT '' COMMENT '姓名',
  `position` varchar(100) DEFAULT NULL COMMENT '职位',
  `department` varchar(100) DEFAULT NULL COMMENT '部门',
  `mobile` varchar(50) DEFAULT NULL COMMENT '手机',
  `telephone` varchar(50) DEFAULT NULL COMMENT '固话',
  `email` varchar(100) DEFAULT NULL COMMENT '邮箱',
  `wechat` varchar(100) DEFAULT NULL COMMENT '微信号',
  `is_primary` tinyint(1) DEFAULT '0' COMMENT '是否首要联系人',
  `gender` tinyint(1) DEFAULT '0' COMMENT '性别 0=未知 1=男 2=女',
  `remark` varchar(500) DEFAULT NULL COMMENT '备注',
  `is_delete` tinyint(1) NOT NULL DEFAULT '0',
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_store_id` (`store_id`),
  KEY `idx_customer_id` (`customer_id`)
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COMMENT='CRM联系人表';

-- ----------------------------
-- Records of tao_crm_contact
-- ----------------------------
INSERT INTO `tao_crm_contact` VALUES ('10', '10001', '50', '张伟', '采购总监', '采购部', '13800138001', '020-85551234', 'zhangwei@hengda.com', 'zw_hengda', '1', '1', null, '0', '1765857600', '1781409600');
INSERT INTO `tao_crm_contact` VALUES ('11', '10001', '51', '李芳', '采购经理', '采购部', '13900139002', '0755-86661234', 'lifang@pengcheng.com', 'lifang_pc', '1', '2', null, '0', '1765857600', '1781409600');
INSERT INTO `tao_crm_contact` VALUES ('12', '10001', '52', '王强', '总经理', '总经办', '13700137003', '0769-83331234', 'wangqiang@liansheng.com', 'wq_ls', '1', '1', null, '0', '1765857600', '1781409600');
INSERT INTO `tao_crm_contact` VALUES ('13', '10001', '53', '陈丽', '采购主管', '采购部', '13600136004', '0757-82221234', 'chenli@nanhai.com', 'cl_nhcy', '1', '2', null, '0', '1765857600', '1781409600');
INSERT INTO `tao_crm_contact` VALUES ('14', '10001', '60', '赵刚', '副总经理', '总经办', '13500135005', '021-68881234', 'zhaogang@huayi.com', 'zg_hy', '1', '1', null, '0', '1765857600', '1781409600');
INSERT INTO `tao_crm_contact` VALUES ('15', '10001', '60', '孙敏', '采购专员', '采购部', '13400134006', '021-68881235', 'sunmin@huayi.com', 'sm_hy', '0', '2', null, '0', '1765857600', '1781409600');
INSERT INTO `tao_crm_contact` VALUES ('16', '10001', '61', '周杰', '采购总监', '采购部', '13300133007', '010-68881234', 'zhoujie@zhongcai.com', 'zj_zcl', '1', '1', null, '0', '1765857600', '1781409600');
INSERT INTO `tao_crm_contact` VALUES ('17', '10001', '62', '吴婷', '技术主管', '技术部', '13200132008', '028-85551234', 'wuting@jinjiang.com', 'wt_jj', '1', '2', null, '0', '1765857600', '1781409600');
INSERT INTO `tao_crm_contact` VALUES ('18', '10001', '63', '郑浩', '采购经理', '采购部', '13100131009', '027-87771234', 'zhenghao@changjiang.com', 'zh_cj', '1', '1', null, '0', '1765857600', '1781409600');
INSERT INTO `tao_crm_contact` VALUES ('19', '10001', '56', '钱波', '厂长', '生产部', '13000130010', '0754-84441234', 'qianbo@jinping.com', 'qb_jp', '1', '1', null, '0', '1765857600', '1781409600');
INSERT INTO `tao_crm_contact` VALUES ('20', '10001', '70', '林峰', '采购主管', '采购部', '13705770001', '0577-88881234', 'linfeng@xinyi.com', null, '1', '1', null, '0', '1765857600', '1781409600');
INSERT INTO `tao_crm_contact` VALUES ('21', '10001', '71', '黄丽华', '总经理', '总经办', '13605920002', '0592-66661234', 'huanglh@caihong.com', null, '1', '2', null, '0', '1765857600', '1781409600');
INSERT INTO `tao_crm_contact` VALUES ('22', '10001', '72', '杨明', '技术工程师', '技术部', '13507310003', '0731-88881234', 'yangming@znys.com', null, '1', '1', null, '0', '1765857600', '1781409600');
INSERT INTO `tao_crm_contact` VALUES ('23', '10001', '73', '徐刚', '采购总监', '采购部', '13402500004', '025-86661234', 'xugang@jlcy.com', null, '1', '1', null, '0', '1765857600', '1781409600');
INSERT INTO `tao_crm_contact` VALUES ('24', '10001', '78', '马超', '副总经理', '总经办', '13302200005', '022-68881234', 'machao@tianbao.com', null, '1', '1', null, '0', '1765857600', '1781409600');
INSERT INTO `tao_crm_contact` VALUES ('25', '10001', '78', '刘婷', '采购专员', '采购部', '13202200006', '022-68881235', 'liuting@tianbao.com', null, '0', '2', null, '0', '1765857600', '1781409600');
INSERT INTO `tao_crm_contact` VALUES ('26', '10001', '86', '韩志强', '总经理', '总经办', '13105320007', '0532-88881234', 'hanzq@lanhai.com', null, '1', '1', null, '0', '1765857600', '1781409600');
INSERT INTO `tao_crm_contact` VALUES ('27', '10001', '86', '丁芳', '采购经理', '采购部', '13005320008', '0532-88881235', 'dingfang@lanhai.com', null, '0', '2', null, '0', '1765857600', '1781409600');
INSERT INTO `tao_crm_contact` VALUES ('28', '10001', '73', '许明辉', '技术主管', '技术部', '13802500009', '025-86661236', 'xumh@jlcy.com', null, '0', '1', null, '0', '1765857600', '1781409600');
INSERT INTO `tao_crm_contact` VALUES ('29', '10001', '79', '曹磊', '采购经理', '采购部', '13903110010', '0311-88881234', 'caolei@jilian.com', null, '1', '1', null, '0', '1765857600', '1781409600');
INSERT INTO `tao_crm_contact` VALUES ('30', '10001', '74', '邓洁', '采购主管', '采购部', '13703710011', '0371-66661234', 'dengjie@huacai.com', null, '1', '2', null, '0', '1765857600', '1781409600');
INSERT INTO `tao_crm_contact` VALUES ('31', '10001', '87', '沈飞', '技术总监', '技术部', '13602700012', '027-88881234', 'shenfei@guanggu.com', null, '1', '1', null, '0', '1765857600', '1781409600');
INSERT INTO `tao_crm_contact` VALUES ('32', '10001', '76', '秦刚', '采购经理', '采购部', '13502900013', '029-86661234', 'qingang@qinchuan.com', null, '1', '1', null, '0', '1765857600', '1781409600');
INSERT INTO `tao_crm_contact` VALUES ('33', '10001', '82', '石磊', '厂长', '生产部', '13404510014', '0451-88881234', 'shilei@bingcheng.com', null, '1', '1', null, '0', '1765857600', '1781409600');
INSERT INTO `tao_crm_contact` VALUES ('34', '10001', '80', '高远', '采购经理', '采购部', '13302400015', '024-68881234', 'gaoyuan@dongbei.com', null, '1', '1', null, '0', '1765857600', '1781409600');
INSERT INTO `tao_crm_contact` VALUES ('35', '10001', '88', '韦明', '采购主管', '采购部', '13207710016', '0771-88881234', 'weiming@lvcheng.com', null, '1', '1', null, '0', '1765857600', '1781409600');
INSERT INTO `tao_crm_contact` VALUES ('36', '10001', '75', '蒋波', '技术主管', '技术部', '13102300017', '023-66661234', 'jiangbo@shancheng.com', null, '1', '1', null, '0', '1765857600', '1781409600');
INSERT INTO `tao_crm_contact` VALUES ('37', '10001', '95', '贺兰', '采购经理', '采购部', '13009510018', '0951-88881234', 'helan@xixia.com', null, '1', '2', null, '0', '1765857600', '1781409600');
INSERT INTO `tao_crm_contact` VALUES ('38', '10001', '77', '方健', '总经理', '总经办', '13805510019', '0551-88881234', 'fangjian@huishang.com', null, '1', '1', null, '0', '1765857600', '1781409600');
INSERT INTO `tao_crm_contact` VALUES ('39', '10001', '92', '莫莉', '采购主管', '采购部', '13708710020', '0871-66661234', 'moli@chuncheng.com', null, '1', '2', null, '0', '1765857600', '1781409600');
INSERT INTO `tao_crm_contact` VALUES ('40', '10001', '81', '罗刚', '厂长', '生产部', '13604310021', '0431-88881234', 'luogang@chuncheng.com', null, '1', '1', null, '0', '1765857600', '1781409600');

-- ----------------------------
-- Table structure for tao_crm_content_agent
-- ----------------------------
DROP TABLE IF EXISTS `tao_crm_content_agent`;
CREATE TABLE `tao_crm_content_agent` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `store_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `agent_type` varchar(32) NOT NULL,
  `style_id` int(11) DEFAULT '0',
  `preset_style` varchar(32) DEFAULT '',
  `audience_id` int(11) DEFAULT '0',
  `allowed_vars` text,
  `instructions` text,
  `status` tinyint(4) DEFAULT '1',
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_store_type` (`store_id`,`agent_type`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Records of tao_crm_content_agent
-- ----------------------------

-- ----------------------------
-- Table structure for tao_crm_content_keyword
-- ----------------------------
DROP TABLE IF EXISTS `tao_crm_content_keyword`;
CREATE TABLE `tao_crm_content_keyword` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `store_id` int(11) NOT NULL,
  `keyword` varchar(100) NOT NULL,
  `type` tinyint(4) DEFAULT '1',
  `search_volume` int(11) DEFAULT '0',
  `difficulty` tinyint(4) DEFAULT '0',
  `group_tag` varchar(32) DEFAULT '',
  `status` tinyint(4) DEFAULT '1',
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_store_type` (`store_id`,`type`),
  KEY `idx_keyword` (`keyword`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Records of tao_crm_content_keyword
-- ----------------------------

-- ----------------------------
-- Table structure for tao_crm_content_output
-- ----------------------------
DROP TABLE IF EXISTS `tao_crm_content_output`;
CREATE TABLE `tao_crm_content_output` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `store_id` int(11) NOT NULL,
  `topic_id` int(11) NOT NULL,
  `format` varchar(16) NOT NULL,
  `title` varchar(300) NOT NULL,
  `content` longtext,
  `keywords_used` varchar(500) DEFAULT NULL,
  `solution_id` int(11) DEFAULT '0',
  `word_count` int(11) DEFAULT '0',
  `status` tinyint(4) DEFAULT '1',
  `publish_url` varchar(500) DEFAULT NULL,
  `publish_time` int(11) DEFAULT NULL,
  `views` int(11) DEFAULT '0',
  `inquiries` int(11) DEFAULT '0',
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_topic` (`topic_id`),
  KEY `idx_store_status` (`store_id`,`status`)
) ENGINE=InnoDB AUTO_INCREMENT=87 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Records of tao_crm_content_output
-- ----------------------------

-- ----------------------------
-- Table structure for tao_crm_content_style
-- ----------------------------
DROP TABLE IF EXISTS `tao_crm_content_style`;
CREATE TABLE `tao_crm_content_style` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `store_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `role_desc` varchar(200) DEFAULT NULL,
  `system_prompt` text,
  `forbidden_words` varchar(500) DEFAULT '',
  `tone` varchar(32) DEFAULT '专业',
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_store` (`store_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Records of tao_crm_content_style
-- ----------------------------

-- ----------------------------
-- Table structure for tao_crm_content_topic
-- ----------------------------
DROP TABLE IF EXISTS `tao_crm_content_topic`;
CREATE TABLE `tao_crm_content_topic` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `store_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `content_type` varchar(32) DEFAULT 'article',
  `source_type` varchar(32) DEFAULT NULL,
  `source_ref_id` int(11) DEFAULT '0',
  `keywords` varchar(500) DEFAULT NULL,
  `angle` varchar(100) DEFAULT NULL,
  `status` tinyint(4) DEFAULT '1',
  `priority` tinyint(4) DEFAULT '3',
  `reviewer_uid` int(11) DEFAULT '0',
  `creator_uid` int(11) DEFAULT '0',
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_store_status` (`store_id`,`status`),
  KEY `idx_date` (`create_time`)
) ENGINE=InnoDB AUTO_INCREMENT=62 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Records of tao_crm_content_topic
-- ----------------------------

-- ----------------------------
-- Table structure for tao_crm_contract
-- ----------------------------
DROP TABLE IF EXISTS `tao_crm_contract`;
CREATE TABLE `tao_crm_contract` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '合同ID',
  `store_id` int(11) NOT NULL DEFAULT '0',
  `contract_no` varchar(50) NOT NULL DEFAULT '' COMMENT '合同号 CT-YYYYMMDD-NNN',
  `contract_name` varchar(200) NOT NULL DEFAULT '' COMMENT '合同名称',
  `customer_id` int(11) NOT NULL DEFAULT '0',
  `contact_id` int(11) DEFAULT NULL,
  `quotation_id` int(11) DEFAULT NULL,
  `order_id` int(11) DEFAULT NULL,
  `currency` varchar(10) DEFAULT 'CNY',
  `sign_date` int(11) DEFAULT NULL COMMENT '签订日期',
  `start_date` int(11) DEFAULT NULL,
  `end_date` int(11) DEFAULT NULL,
  `contract_amount` decimal(12,2) DEFAULT '0.00',
  `contract_content` text COMMENT '合同条款',
  `attachment` varchar(500) DEFAULT NULL,
  `status` tinyint(1) DEFAULT '1' COMMENT '1=草稿 2=已签订 3=履行中 4=已完成 5=已终止',
  `owner_user_id` int(11) DEFAULT NULL,
  `is_delete` tinyint(1) NOT NULL DEFAULT '0',
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_store_no` (`store_id`,`contract_no`),
  KEY `idx_store_id` (`store_id`),
  KEY `idx_customer_id` (`customer_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COMMENT='CRM合同表';

-- ----------------------------
-- Records of tao_crm_contract
-- ----------------------------
INSERT INTO `tao_crm_contract` VALUES ('1', '10001', 'CT-20260301-001', '2026年度UV油墨供货框架协议', '78', '24', null, '40', 'CNY', '1772337600', '1772337600', '1803873600', '52000.00', '年度框架协议，分月供货，月均4万元', null, '2', '10001', '0', '1780545600', '1781409600');
INSERT INTO `tao_crm_contract` VALUES ('2', '10001', 'CT-20260510-002', 'UV LED固化油墨采购合同', '86', '26', '40', '47', 'CNY', '1778385600', '1778385600', '1780977600', '95000.00', '采购UV LED固化油墨一批，含技术支持和售后', null, '3', '10001', '0', '1780545600', '1781409600');
INSERT INTO `tao_crm_contract` VALUES ('3', '10001', 'CT-20260520-003', '水性光油年度采购协议', '86', '27', null, '48', 'CNY', '1779940800', '1779940800', '1805601600', '120000.00', '水性光油年度采购，按季度分批交付', null, '2', '10001', '0', '1780545600', '1781409600');
INSERT INTO `tao_crm_contract` VALUES ('4', '10001', 'CT-20260501-004', '溶剂型油墨单次采购合同', '73', '23', '52', '52', 'CNY', '1778817600', '1778817600', '1781496000', '90000.00', '单次采购，交付期30天', null, '4', '10001', '0', '1780545600', '1781409600');
INSERT INTO `tao_crm_contract` VALUES ('5', '10001', 'CT-20260605-005', '特殊防伪油墨试单协议', '87', '31', '44', '50', 'CNY', '1780632000', '1780632000', '1786593600', '82000.00', '试单协议，如测试通过转为长期合同', null, '1', '10001', '0', '1780545600', '1781409600');
INSERT INTO `tao_crm_contract` VALUES ('6', '10001', 'CT-20260420-006', 'UV油墨采购合同', '79', '29', null, '43', 'CNY', '1776657600', '1776657600', '1780113600', '28000.00', '常规采购UV油墨', null, '3', '10001', '0', '1780545600', '1781409600');
INSERT INTO `tao_crm_contract` VALUES ('7', '10001', 'CT-20260610-007', '环保稀释剂供货协议', '60', '14', '32', '31', 'CNY', '1781064000', '1781064000', '1807329600', '85000.00', '季度协议，月均供货', null, '2', '10001', '0', '1780545600', '1781409600');
INSERT INTO `tao_crm_contract` VALUES ('8', '10001', 'CT-20251201-008', '东北印务2025年度合同(已终止)', '80', '34', null, '45', 'CNY', '1764993600', '1764993600', '1772769600', '48000.00', '原年度合同，客户业务萎缩已提前终止', null, '5', '10001', '0', '1780545600', '1781409600');

-- ----------------------------
-- Table structure for tao_crm_customer
-- ----------------------------
DROP TABLE IF EXISTS `tao_crm_customer`;
CREATE TABLE `tao_crm_customer` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '客户ID',
  `store_id` int(11) NOT NULL DEFAULT '0' COMMENT '租户ID',
  `customer_name` varchar(200) NOT NULL DEFAULT '' COMMENT '公司全称',
  `customer_code` varchar(50) NOT NULL DEFAULT '' COMMENT '客户编码 C-YYYYMMDD-NNN',
  `short_name` varchar(100) DEFAULT NULL COMMENT '简称',
  `industry` varchar(100) DEFAULT NULL COMMENT '行业',
  `industry_id` int(11) DEFAULT NULL COMMENT '行业ID',
  `level_id` int(11) DEFAULT NULL COMMENT '客户等级 1=A级 2=B级 3=C级 4=D级',
  `level_name` varchar(50) DEFAULT NULL COMMENT '客户等级名称',
  `source` varchar(100) DEFAULT NULL COMMENT '来源',
  `source_id` int(11) DEFAULT NULL COMMENT '来源ID',
  `customer_group` varchar(100) DEFAULT NULL COMMENT '客户分组',
  `group_id` int(11) DEFAULT NULL COMMENT '分组ID',
  `tax_number` varchar(50) DEFAULT NULL COMMENT '税号',
  `address` varchar(500) DEFAULT NULL COMMENT '公司地址',
  `province` varchar(50) DEFAULT NULL COMMENT '省份',
  `city` varchar(50) DEFAULT NULL COMMENT '城市',
  `area` varchar(50) DEFAULT NULL COMMENT '区县',
  `website` varchar(200) DEFAULT NULL COMMENT '网址',
  `credit_limit` decimal(12,2) DEFAULT '0.00' COMMENT '信用额度',
  `payment_terms` varchar(200) DEFAULT NULL COMMENT '结算方式',
  `remark` text COMMENT '备注',
  `status` tinyint(1) DEFAULT '1' COMMENT '1=正常 0=停用',
  `owner_user_id` int(11) DEFAULT '0' COMMENT '负责人（store_user），0=公海',
  `owner_department_id` int(11) DEFAULT NULL COMMENT '负责人所属部门（冗余）',
  `creator_user_id` int(11) DEFAULT NULL COMMENT '录入员（store_user）',
  `user_id` int(11) DEFAULT NULL COMMENT '关联小程序会员（tao_user.id）',
  `funnel_stage` tinyint(1) DEFAULT '1' COMMENT '漏斗阶段 1=初步接触 2=需求确认 3=报价 4=谈判 5=成交',
  `last_followup_time` int(11) DEFAULT NULL COMMENT '最后跟进时间',
  `enter_pool_time` int(11) DEFAULT NULL COMMENT '进入公海的时间',
  `is_delete` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否删除',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `ai_score` int(11) DEFAULT '0' COMMENT 'AI评分',
  `ai_tags` varchar(500) DEFAULT NULL COMMENT 'AI标签',
  `ai_suggestion` text COMMENT 'AI跟进建议',
  `ai_portrait` text COMMENT 'AI客户画像JSON',
  `ai_analysis_time` int(11) DEFAULT NULL COMMENT 'AI分析时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_store_code` (`store_id`,`customer_code`),
  KEY `idx_store_id` (`store_id`),
  KEY `idx_owner_user` (`owner_user_id`),
  KEY `idx_owner_dept` (`owner_department_id`),
  KEY `idx_creator` (`creator_user_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_funnel_stage` (`funnel_stage`),
  KEY `idx_last_followup` (`last_followup_time`)
) ENGINE=InnoDB AUTO_INCREMENT=96 DEFAULT CHARSET=utf8mb4 COMMENT='CRM客户表';

-- ----------------------------
-- Records of tao_crm_customer
-- ----------------------------
INSERT INTO `tao_crm_customer` VALUES ('49', '10001', '1212', 'C-20260614-001', '', '科技型', '1', '1', 'A级-战略', '客户介绍', '2', '塑料油墨', '1', '', '', '', '', '', '', '0.00', '', '', '1', '10001', null, '10001', null, '2', '1781403296', null, '0', '1781403290', '1781403296', '0', null, null, null, null);
INSERT INTO `tao_crm_customer` VALUES ('50', '10001', '广州恒达彩印有限公司', 'C-20260301-001', '恒达彩印', '制造', null, '1', 'A', '展会', null, null, null, null, null, null, null, null, null, '0.00', null, null, '1', '10001', null, null, null, '3', '1779595200', null, '0', '1765857600', '1779595200', '85', '高价值,决策快', null, null, null);
INSERT INTO `tao_crm_customer` VALUES ('51', '10001', '深圳鹏程包装有限公司', 'C-20260415-002', '鹏程包装', '贸易', null, '2', 'B', '线上推广', null, null, null, null, null, null, null, null, null, '0.00', null, null, '1', '10068', null, null, null, '3', '1780545600', null, '0', '1765857600', '1780545600', '72', '意向强,比价中', null, null, null);
INSERT INTO `tao_crm_customer` VALUES ('52', '10001', '东莞联盛印刷科技有限公司', 'C-20260120-003', '联盛印刷', '制造', null, '1', 'A', '客户转介绍', null, null, null, null, null, null, null, null, null, '0.00', null, null, '1', '10001', null, null, null, '4', '1778299200', null, '0', '1765857600', '1778299200', '90', '复购客户,付款及时', null, null, null);
INSERT INTO `tao_crm_customer` VALUES ('53', '10001', '佛山南海彩艺印刷厂', 'C-20251008-004', '南海彩艺', '制造', null, '2', 'B', '行业展会', null, null, null, null, null, null, null, null, null, '0.00', null, null, '1', '10001', null, null, null, '4', '1776657600', null, '0', '1765857600', '1776657600', '78', '老客户,量大', null, null, null);
INSERT INTO `tao_crm_customer` VALUES ('54', '10001', '中山市伟业印刷材料公司', 'C-20250901-005', '伟业印刷', '贸易', null, '3', 'C', '电话开发', null, null, null, null, null, null, null, null, null, '0.00', null, null, '1', '10068', null, null, null, '2', '1766376000', null, '0', '1765857600', '1766376000', '45', '潜力客户', null, null, null);
INSERT INTO `tao_crm_customer` VALUES ('55', '10001', '珠海横琴新材料有限公司', 'C-20250820-006', '横琴新材', '科技型', null, '3', 'C', '网络搜索', null, null, null, null, null, null, null, null, null, '0.00', null, null, '1', '10071', null, null, null, '2', '1766116800', null, '0', '1765857600', '1766116800', '38', '新材料需求', null, null, null);
INSERT INTO `tao_crm_customer` VALUES ('56', '10001', '汕头市金平彩印厂', 'C-20251101-007', '金平彩印', '制造', null, '2', 'B', '老客户', null, null, null, null, null, null, null, null, null, '0.00', null, null, '1', '10001', null, null, null, '3', '1778385600', null, '0', '1765857600', '1778385600', '55', '订单下滑,价格敏感', null, null, null);
INSERT INTO `tao_crm_customer` VALUES ('57', '10001', '惠州博罗印刷包装公司', 'C-20251015-008', '博罗包装', '制造', null, '3', 'C', '展会', null, null, null, null, null, null, null, null, null, '0.00', null, null, '1', '10068', null, null, null, '2', '1777953600', null, '0', '1765857600', '1777953600', '42', '竞品接触', null, null, null);
INSERT INTO `tao_crm_customer` VALUES ('58', '10001', '江门新会区永达印刷厂', 'C-20250701-009', '永达印刷', '制造', null, '3', 'C', '转介绍', null, null, null, null, null, null, null, null, null, '0.00', null, null, '1', '10001', null, null, null, '2', '1772769600', null, '0', '1765857600', '1772769600', '30', '沉睡风险', null, null, null);
INSERT INTO `tao_crm_customer` VALUES ('59', '10001', '肇庆端州印刷物资公司', 'C-20250615-010', '端州印物', '贸易', null, '4', 'D', '电话开发', null, null, null, null, null, null, null, null, null, '0.00', null, null, '1', '10071', null, null, null, '1', '1771041600', null, '0', '1765857600', '1771041600', '25', '小额客户', null, null, null);
INSERT INTO `tao_crm_customer` VALUES ('60', '10001', '上海华艺印刷集团', 'C-20260601-011', '华艺集团', '文化传媒', null, '1', 'A', '战略合作', null, null, null, null, null, null, null, null, null, '0.00', null, null, '1', '10001', null, null, null, '5', '1781323200', null, '0', '1765857600', '1781323200', '95', 'VIP,长期合作,付款优质', null, null, null);
INSERT INTO `tao_crm_customer` VALUES ('61', '10001', '北京中彩联印刷有限公司', 'C-20260605-012', '中彩联', '文化传媒', null, '1', 'A', '招投标', null, null, null, null, null, null, null, null, null, '0.00', null, null, '1', '10001', null, null, null, '4', '1781236800', null, '0', '1765857600', '1781236800', '88', '大客户,招投标,季度采购', null, null, null);
INSERT INTO `tao_crm_customer` VALUES ('62', '10001', '成都锦江印务有限公司', 'C-20260608-013', '锦江印务', '制造', null, '2', 'B', '线上咨询', null, null, null, null, null, null, null, null, null, '0.00', null, null, '1', '10068', null, null, null, '2', '1781150400', null, '0', '1765857600', '1781150400', '70', '新客户,UV油墨需求', null, null, null);
INSERT INTO `tao_crm_customer` VALUES ('63', '10001', '武汉长江印务科技公司', 'C-20260610-014', '长江印务', '科技型', null, '2', 'B', '行业论坛', null, null, null, null, null, null, null, null, null, '0.00', null, null, '1', '10068', null, null, null, '3', '1781323200', null, '0', '1765857600', '1781323200', '75', '技术导向,环保需求', null, null, null);
INSERT INTO `tao_crm_customer` VALUES ('64', '10001', '昆明滇池印刷服务公司', 'C-20251120-015', '滇池印刷', '制造', null, '3', 'C', '电话开发', null, null, null, null, null, null, null, null, null, '0.00', null, null, '1', '0', null, null, null, '1', '1762401600', null, '0', '1765857600', '1762401600', '20', null, null, null, null);
INSERT INTO `tao_crm_customer` VALUES ('65', '10001', '南京金陵印刷物资公司', 'C-20251201-016', '金陵印物', '贸易', null, '4', 'D', '网络搜索', null, null, null, null, null, null, null, null, null, '0.00', null, null, '1', '0', null, null, null, '1', '1763265600', null, '0', '1765857600', '1763265600', '15', null, null, null, null);
INSERT INTO `tao_crm_customer` VALUES ('70', '10001', '浙江温州新艺印务有限公司', 'C-20260601-017', '新艺印务', '制造', null, '2', 'B', '阿里巴巴', null, null, null, null, null, null, null, null, null, '0.00', null, null, '1', '10001', null, null, null, '3', '1781236800', null, '0', '1777235348', '1781236800', '82', '线上获客,UV需求', null, null, null);
INSERT INTO `tao_crm_customer` VALUES ('71', '10001', '福建厦门彩弘包装有限公司', 'C-20260603-018', '彩弘包装', '贸易', null, '1', 'A', '客户转介绍', null, null, null, null, null, null, null, null, null, '0.00', null, null, '1', '10001', null, null, null, '4', '1781150400', null, '0', '1766525967', '1781150400', '91', '高端客户,品质导向', null, null, null);
INSERT INTO `tao_crm_customer` VALUES ('72', '10001', '湖南长沙中南印刷厂', 'C-20260605-019', '中南印刷', '制造', null, '3', 'C', '电话开发', null, null, null, null, null, null, null, null, null, '0.00', null, null, '1', '10068', null, null, null, '2', '1780977600', null, '0', '1771115838', '1780977600', '60', '价格敏感,本地客户', null, null, null);
INSERT INTO `tao_crm_customer` VALUES ('73', '10001', '江苏南京金陵彩印有限公司', 'C-20260606-020', '金陵彩印', '文化传媒', null, '1', 'A', '招投标', null, null, null, null, null, null, null, null, null, '0.00', null, null, '1', '10001', null, null, null, '4', '1781064000', null, '0', '1777596090', '1781064000', '87', '政府订单,批量采购', null, null, null);
INSERT INTO `tao_crm_customer` VALUES ('74', '10001', '河南郑州华彩印务科技', 'C-20260607-021', '华彩印务', '科技型', null, '2', 'B', '行业论坛', null, null, null, null, null, null, null, null, null, '0.00', null, null, '1', '10068', null, null, null, '2', '1780891200', null, '0', '1769302928', '1780891200', '73', '技术导向,环保意识强', null, null, null);
INSERT INTO `tao_crm_customer` VALUES ('75', '10001', '重庆山城包装材料有限公司', 'C-20260608-022', '山城包装', '贸易', null, '3', 'C', '线上推广', null, null, null, null, null, null, null, null, null, '0.00', null, null, '1', '10071', null, null, null, '2', '1781150400', null, '0', '1770476803', '1781150400', '55', '新市场开拓', null, null, null);
INSERT INTO `tao_crm_customer` VALUES ('76', '10001', '陕西西安秦川印刷物资公司', 'C-20260609-023', '秦川印物', '贸易', null, '2', 'B', '展会', null, null, null, null, null, null, null, null, null, '0.00', null, null, '1', '10071', null, null, null, '3', '1780977600', null, '0', '1777114813', '1780977600', '68', '西北市场,物流关注', null, null, null);
INSERT INTO `tao_crm_customer` VALUES ('77', '10001', '安徽合肥徽商印务有限公司', 'C-20260610-024', '徽商印务', '制造', null, '2', 'B', '电话开发', null, null, null, null, null, null, null, null, null, '0.00', null, null, '1', '10001', null, null, null, '1', '1781236800', null, '0', '1770282119', '1781236800', '65', '刚接触,观望中', null, null, null);
INSERT INTO `tao_crm_customer` VALUES ('78', '10001', '天津滨海新区天印包装', 'C-20260301-025', '天印包装', '制造', null, '1', 'A', '战略合作', null, null, null, null, null, null, null, null, null, '0.00', null, null, '1', '10001', null, null, null, '5', '1780977600', null, '0', '1769843588', '1780977600', '93', 'VIP,年采购额150万,季度订单', null, null, null);
INSERT INTO `tao_crm_customer` VALUES ('79', '10001', '河北石家庄冀联印刷厂', 'C-20260401-026', '冀联印刷', '制造', null, '2', 'B', '老客户介绍', null, null, null, null, null, null, null, null, null, '0.00', null, null, '1', '10001', null, null, null, '4', '1780545600', null, '0', '1766234778', '1780545600', '79', '老客户,UV油墨为主', null, null, null);
INSERT INTO `tao_crm_customer` VALUES ('80', '10001', '辽宁沈阳东北印务集团', 'C-20251001-027', '东北印务', '制造', null, '3', 'C', '展会', null, null, null, null, null, null, null, null, null, '0.00', null, null, '1', '10001', null, null, null, '1', '1771905600', null, '0', '1773757753', '1771905600', '35', '大客户但活跃度下降', null, null, null);
INSERT INTO `tao_crm_customer` VALUES ('81', '10001', '吉林长春春城彩印厂', 'C-20250915-028', '春城彩印', '制造', null, '4', 'D', '电话开发', null, null, null, null, null, null, null, null, null, '0.00', null, null, '1', '10068', null, null, null, '1', '1770177600', null, '0', '1776542402', '1770177600', '22', '小额客户,长期未联系', null, null, null);
INSERT INTO `tao_crm_customer` VALUES ('82', '10001', '黑龙江哈尔滨冰城印刷公司', 'C-20250801-029', '冰城印刷', '贸易', null, '3', 'C', '网络搜索', null, null, null, null, null, null, null, null, null, '0.00', null, null, '1', '10071', null, null, null, '2', '1766721600', null, '0', '1771987436', '1766721600', '28', '有联系但未成交', null, null, null);
INSERT INTO `tao_crm_customer` VALUES ('83', '10001', '甘肃兰州陇西印刷物资站', 'C-20250715-030', '陇西印物', '贸易', null, '4', 'D', '电话开发', null, null, null, null, null, null, null, null, null, '0.00', null, null, '1', '10068', null, null, null, '1', '1765857600', null, '0', '1776301266', '1765857600', '15', '偏远地区,物流困难', null, null, null);
INSERT INTO `tao_crm_customer` VALUES ('84', '10001', '新疆乌鲁木齐天山印务', 'C-20250601-031', '天山印务', '制造', null, '4', 'D', '网络搜索', null, null, null, null, null, null, null, null, null, '0.00', null, null, '1', '0', null, null, null, '1', '1760673600', null, '0', '1774896387', '1760673600', '10', '西北偏远', null, null, null);
INSERT INTO `tao_crm_customer` VALUES ('85', '10001', '西藏拉萨雪域印刷服务部', 'C-20250501-032', '雪域印刷', '贸易', null, '4', 'D', '电话开发', null, null, null, null, null, null, null, null, null, '0.00', null, null, '1', '0', null, null, null, '1', '1758081600', null, '0', '1777927243', '1758081600', '5', '小微型', null, null, null);
INSERT INTO `tao_crm_customer` VALUES ('86', '10001', '山东青岛蓝海印务有限公司', 'C-20260501-033', '蓝海印务', '制造', null, '1', 'A', '战略合作', null, null, null, null, null, null, null, null, null, '0.00', null, null, '1', '10001', null, null, null, '5', '1781323200', null, '0', '1768245654', '1781323200', '96', 'VIP,月均采购10万+,付款优质', null, null, null);
INSERT INTO `tao_crm_customer` VALUES ('87', '10001', '湖北武汉光谷印刷科技园', 'C-20260515-034', '光谷印科', '科技型', null, '1', 'A', '政府推荐', null, null, null, null, null, null, null, null, null, '0.00', null, null, '1', '10001', null, null, null, '4', '1781064000', null, '0', '1766208714', '1781064000', '89', '高新企业,特殊油墨需求', null, null, null);
INSERT INTO `tao_crm_customer` VALUES ('88', '10001', '广西南宁绿城包装印刷厂', 'C-20260611-035', '绿城包装', '制造', null, '2', 'B', '线上咨询', null, null, null, null, null, null, null, null, null, '0.00', null, null, '1', '10068', null, null, null, '2', '1781323200', null, '0', '1767546811', '1781323200', '58', '新客,需培养', null, null, null);
INSERT INTO `tao_crm_customer` VALUES ('89', '10001', '贵州贵阳黔彩印务公司', 'C-20260612-036', '黔彩印务', '贸易', null, '3', 'C', '展会跟进', null, null, null, null, null, null, null, null, null, '0.00', null, null, '1', '10071', null, null, null, '1', '1781236800', null, '0', '1770980497', '1781236800', '45', '刚接触', null, null, null);
INSERT INTO `tao_crm_customer` VALUES ('90', '10001', '山西太原晋阳印刷有限公司', 'C-20260613-037', '晋阳印刷', '制造', null, '2', 'B', '电话开发', null, null, null, null, null, null, null, null, null, '0.00', null, null, '1', '10001', null, null, null, '1', '1781150400', null, '0', '1775240766', '1781150400', '52', '意向一般', null, null, null);
INSERT INTO `tao_crm_customer` VALUES ('91', '10001', '内蒙古呼和浩特草原印务', 'C-20260614-038', '草原印务', '贸易', null, '4', 'D', '网络搜索', null, null, null, null, null, null, null, null, null, '0.00', null, null, '1', '10068', null, null, null, '1', '1780977600', null, '0', '1769396841', '1780977600', '30', '刚注册,观察中', null, null, null);
INSERT INTO `tao_crm_customer` VALUES ('92', '10001', '云南昆明春城彩印包装厂', 'C-20260602-039', '春城彩印', '制造', null, '3', 'C', '线上推广', null, null, null, null, null, null, null, null, null, '0.00', null, null, '1', '10071', null, null, null, '2', '1781323200', null, '0', '1766601129', '1781323200', '62', '水性油墨需求', null, null, null);
INSERT INTO `tao_crm_customer` VALUES ('93', '10001', '江西南昌赣江印刷物资站', 'C-20260528-040', '赣江印物', '贸易', null, '3', 'C', '电话开发', null, null, null, null, null, null, null, null, null, '0.00', null, null, '1', '10068', null, null, null, '2', '1780804800', null, '0', '1778076465', '1780804800', '48', '溶剂型油墨需求', null, null, null);
INSERT INTO `tao_crm_customer` VALUES ('94', '10001', '海南海口海岛印刷服务公司', 'C-20260520-041', '海岛印刷', '贸易', null, '4', 'D', '网络搜索', null, null, null, null, null, null, null, null, null, '0.00', null, null, '1', '10071', null, null, null, '1', '1781150400', null, '0', '1777030193', '1781150400', '35', '旅游包装印刷', null, null, null);
INSERT INTO `tao_crm_customer` VALUES ('95', '10001', '宁夏银川西夏印务有限公司', 'C-20260604-042', '西夏印务', '制造', null, '3', 'C', '展会', null, null, null, null, null, null, null, null, null, '0.00', null, null, '1', '10001', null, null, null, '2', '1780891200', null, '0', '1773493686', '1780891200', '55', 'UV LED油墨关注', null, null, null);

-- ----------------------------
-- Table structure for tao_crm_customer_claim_log
-- ----------------------------
DROP TABLE IF EXISTS `tao_crm_customer_claim_log`;
CREATE TABLE `tao_crm_customer_claim_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `store_id` int(11) NOT NULL DEFAULT '0',
  `customer_id` int(11) NOT NULL DEFAULT '0',
  `from_user_id` int(11) DEFAULT '0' COMMENT '释放人（0=系统）',
  `to_user_id` int(11) DEFAULT '0' COMMENT '认领人',
  `action` tinyint(1) DEFAULT '1' COMMENT '1=系统掉入 2=主动释放 3=认领 4=管理员分配',
  `remark` varchar(500) DEFAULT NULL,
  `create_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_customer_id` (`customer_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COMMENT='CRM认领记录';

-- ----------------------------
-- Records of tao_crm_customer_claim_log
-- ----------------------------
INSERT INTO `tao_crm_customer_claim_log` VALUES ('1', '10001', '64', '0', '10001', '0', '从公海认领滇池印刷', '1778817600');
INSERT INTO `tao_crm_customer_claim_log` VALUES ('2', '10001', '70', '0', '10001', '0', '从公海认领新艺印务', '1779681600');
INSERT INTO `tao_crm_customer_claim_log` VALUES ('3', '10001', '84', '10001', '0', '0', '放弃天山印务（无法联系）', '1764129600');
INSERT INTO `tao_crm_customer_claim_log` VALUES ('4', '10001', '85', '10001', '0', '0', '放弃雪域印刷（距离太远）', '1767585600');
INSERT INTO `tao_crm_customer_claim_log` VALUES ('5', '10001', '65', '0', '10001', '0', '从公海认领金陵印物', '1779681600');
INSERT INTO `tao_crm_customer_claim_log` VALUES ('6', '10001', '81', '10068', '0', '0', '放弃春城彩印（长期失联）', '1771905600');

-- ----------------------------
-- Table structure for tao_crm_customer_collaborator
-- ----------------------------
DROP TABLE IF EXISTS `tao_crm_customer_collaborator`;
CREATE TABLE `tao_crm_customer_collaborator` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `store_id` int(11) NOT NULL DEFAULT '0',
  `customer_id` int(11) NOT NULL DEFAULT '0',
  `user_id` int(11) NOT NULL DEFAULT '0',
  `permission` tinyint(1) DEFAULT '1' COMMENT '1=只读 2=可编辑',
  `remark` varchar(200) DEFAULT NULL COMMENT '共享原因',
  `create_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_customer` (`customer_id`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COMMENT='CRM客户协作成员';

-- ----------------------------
-- Records of tao_crm_customer_collaborator
-- ----------------------------

-- ----------------------------
-- Table structure for tao_crm_event_log
-- ----------------------------
DROP TABLE IF EXISTS `tao_crm_event_log`;
CREATE TABLE `tao_crm_event_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_type` varchar(50) NOT NULL COMMENT '事件类型',
  `target_type` varchar(50) NOT NULL COMMENT '主体实体：customer/quotation/order/contract/followup/lead',
  `target_id` int(11) NOT NULL COMMENT '主体ID',
  `actor_user_id` int(11) DEFAULT NULL COMMENT '操作人（store_user_id）',
  `data` json DEFAULT NULL COMMENT '事件负载',
  `create_time` int(11) NOT NULL COMMENT '事件发生时间',
  `store_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_type_time` (`event_type`,`create_time`),
  KEY `idx_target` (`target_type`,`target_id`),
  KEY `idx_store_time` (`store_id`,`create_time`)
) ENGINE=InnoDB AUTO_INCREMENT=228 DEFAULT CHARSET=utf8mb4 COMMENT='CRM事件日志（不可变）';

-- ----------------------------
-- Records of tao_crm_event_log
-- ----------------------------
INSERT INTO `tao_crm_event_log` VALUES ('1', 'customer_created', 'customer', '50', '10001', '{\"source\": \"manual\", \"customer_id\": 50}', '1773693510');
INSERT INTO `tao_crm_event_log` VALUES ('2', 'customer_created', 'customer', '51', '10001', '{\"source\": \"manual\", \"customer_id\": 51}', '1777980064');
INSERT INTO `tao_crm_event_log` VALUES ('3', 'customer_created', 'customer', '52', '10071', '{\"source\": \"manual\", \"customer_id\": 52}', '1764661712');
INSERT INTO `tao_crm_event_log` VALUES ('4', 'customer_created', 'customer', '53', '10001', '{\"source\": \"manual\", \"customer_id\": 53}', '1767863942');
INSERT INTO `tao_crm_event_log` VALUES ('5', 'customer_created', 'customer', '54', '10071', '{\"source\": \"manual\", \"customer_id\": 54}', '1761350838');
INSERT INTO `tao_crm_event_log` VALUES ('6', 'customer_created', 'customer', '55', '10068', '{\"source\": \"manual\", \"customer_id\": 55}', '1776566008');
INSERT INTO `tao_crm_event_log` VALUES ('7', 'customer_created', 'customer', '56', '10071', '{\"source\": \"manual\", \"customer_id\": 56}', '1764338098');
INSERT INTO `tao_crm_event_log` VALUES ('8', 'customer_created', 'customer', '57', '10001', '{\"source\": \"manual\", \"customer_id\": 57}', '1769112380');
INSERT INTO `tao_crm_event_log` VALUES ('9', 'customer_created', 'customer', '58', '10068', '{\"source\": \"manual\", \"customer_id\": 58}', '1758895483');
INSERT INTO `tao_crm_event_log` VALUES ('10', 'customer_created', 'customer', '59', '10068', '{\"source\": \"manual\", \"customer_id\": 59}', '1764209263');
INSERT INTO `tao_crm_event_log` VALUES ('11', 'customer_created', 'customer', '60', '10068', '{\"source\": \"manual\", \"customer_id\": 60}', '1758464026');
INSERT INTO `tao_crm_event_log` VALUES ('12', 'customer_created', 'customer', '61', '10001', '{\"source\": \"manual\", \"customer_id\": 61}', '1758107829');
INSERT INTO `tao_crm_event_log` VALUES ('13', 'customer_created', 'customer', '62', '10071', '{\"source\": \"manual\", \"customer_id\": 62}', '1772712820');
INSERT INTO `tao_crm_event_log` VALUES ('14', 'customer_created', 'customer', '63', '10001', '{\"source\": \"manual\", \"customer_id\": 63}', '1766762273');
INSERT INTO `tao_crm_event_log` VALUES ('15', 'customer_created', 'customer', '64', '10071', '{\"source\": \"manual\", \"customer_id\": 64}', '1762090610');
INSERT INTO `tao_crm_event_log` VALUES ('16', 'customer_created', 'customer', '65', '10001', '{\"source\": \"manual\", \"customer_id\": 65}', '1772834699');
INSERT INTO `tao_crm_event_log` VALUES ('17', 'customer_created', 'customer', '70', '10001', '{\"source\": \"manual\", \"customer_id\": 70}', '1770920330');
INSERT INTO `tao_crm_event_log` VALUES ('18', 'customer_created', 'customer', '71', '10001', '{\"source\": \"manual\", \"customer_id\": 71}', '1769017286');
INSERT INTO `tao_crm_event_log` VALUES ('19', 'customer_created', 'customer', '72', '10001', '{\"source\": \"manual\", \"customer_id\": 72}', '1770973016');
INSERT INTO `tao_crm_event_log` VALUES ('20', 'customer_created', 'customer', '73', '10071', '{\"source\": \"manual\", \"customer_id\": 73}', '1773855572');
INSERT INTO `tao_crm_event_log` VALUES ('21', 'customer_created', 'customer', '74', '10001', '{\"source\": \"manual\", \"customer_id\": 74}', '1761695329');
INSERT INTO `tao_crm_event_log` VALUES ('22', 'customer_created', 'customer', '75', '10068', '{\"source\": \"manual\", \"customer_id\": 75}', '1764833800');
INSERT INTO `tao_crm_event_log` VALUES ('23', 'customer_created', 'customer', '76', '10071', '{\"source\": \"manual\", \"customer_id\": 76}', '1756975121');
INSERT INTO `tao_crm_event_log` VALUES ('24', 'customer_created', 'customer', '77', '10001', '{\"source\": \"manual\", \"customer_id\": 77}', '1767781022');
INSERT INTO `tao_crm_event_log` VALUES ('25', 'customer_created', 'customer', '78', '10068', '{\"source\": \"manual\", \"customer_id\": 78}', '1760274054');
INSERT INTO `tao_crm_event_log` VALUES ('26', 'customer_created', 'customer', '79', '10068', '{\"source\": \"manual\", \"customer_id\": 79}', '1776325465');
INSERT INTO `tao_crm_event_log` VALUES ('27', 'customer_created', 'customer', '80', '10001', '{\"source\": \"manual\", \"customer_id\": 80}', '1769566619');
INSERT INTO `tao_crm_event_log` VALUES ('28', 'customer_created', 'customer', '81', '10001', '{\"source\": \"manual\", \"customer_id\": 81}', '1772870181');
INSERT INTO `tao_crm_event_log` VALUES ('29', 'customer_created', 'customer', '82', '10068', '{\"source\": \"manual\", \"customer_id\": 82}', '1770996741');
INSERT INTO `tao_crm_event_log` VALUES ('30', 'customer_created', 'customer', '83', '10068', '{\"source\": \"manual\", \"customer_id\": 83}', '1777167711');
INSERT INTO `tao_crm_event_log` VALUES ('31', 'customer_created', 'customer', '84', '10068', '{\"source\": \"manual\", \"customer_id\": 84}', '1757675427');
INSERT INTO `tao_crm_event_log` VALUES ('32', 'customer_created', 'customer', '85', '10068', '{\"source\": \"manual\", \"customer_id\": 85}', '1775952255');
INSERT INTO `tao_crm_event_log` VALUES ('33', 'customer_created', 'customer', '86', '10068', '{\"source\": \"manual\", \"customer_id\": 86}', '1772267629');
INSERT INTO `tao_crm_event_log` VALUES ('34', 'customer_created', 'customer', '87', '10071', '{\"source\": \"manual\", \"customer_id\": 87}', '1771764510');
INSERT INTO `tao_crm_event_log` VALUES ('35', 'customer_created', 'customer', '88', '10001', '{\"source\": \"manual\", \"customer_id\": 88}', '1771360545');
INSERT INTO `tao_crm_event_log` VALUES ('36', 'customer_created', 'customer', '89', '10068', '{\"source\": \"manual\", \"customer_id\": 89}', '1758682527');
INSERT INTO `tao_crm_event_log` VALUES ('37', 'customer_created', 'customer', '90', '10068', '{\"source\": \"manual\", \"customer_id\": 90}', '1762117626');
INSERT INTO `tao_crm_event_log` VALUES ('38', 'customer_created', 'customer', '91', '10001', '{\"source\": \"manual\", \"customer_id\": 91}', '1763787027');
INSERT INTO `tao_crm_event_log` VALUES ('39', 'customer_created', 'customer', '92', '10071', '{\"source\": \"manual\", \"customer_id\": 92}', '1757828640');
INSERT INTO `tao_crm_event_log` VALUES ('40', 'customer_created', 'customer', '93', '10001', '{\"source\": \"manual\", \"customer_id\": 93}', '1777349951');
INSERT INTO `tao_crm_event_log` VALUES ('41', 'customer_created', 'customer', '94', '10068', '{\"source\": \"manual\", \"customer_id\": 94}', '1774202787');
INSERT INTO `tao_crm_event_log` VALUES ('42', 'customer_created', 'customer', '95', '10001', '{\"source\": \"manual\", \"customer_id\": 95}', '1767814121');
INSERT INTO `tao_crm_event_log` VALUES ('43', 'follow_up_completed', 'followup', '74', '10001', '{\"followup_id\": \"74\"}', '1781403296');
INSERT INTO `tao_crm_event_log` VALUES ('44', 'follow_up_completed', 'followup', '75', '10001', '{\"followup_id\": \"75\"}', '1779595200');
INSERT INTO `tao_crm_event_log` VALUES ('45', 'follow_up_completed', 'followup', '76', '10001', '{\"followup_id\": \"76\"}', '1778299200');
INSERT INTO `tao_crm_event_log` VALUES ('46', 'follow_up_completed', 'followup', '77', '10001', '{\"followup_id\": \"77\"}', '1774411200');
INSERT INTO `tao_crm_event_log` VALUES ('47', 'follow_up_completed', 'followup', '78', '10068', '{\"followup_id\": \"78\"}', '1766376000');
INSERT INTO `tao_crm_event_log` VALUES ('48', 'follow_up_completed', 'followup', '79', '10071', '{\"followup_id\": \"79\"}', '1766116800');
INSERT INTO `tao_crm_event_log` VALUES ('49', 'follow_up_completed', 'followup', '80', '10001', '{\"followup_id\": \"80\"}', '1778385600');
INSERT INTO `tao_crm_event_log` VALUES ('50', 'follow_up_completed', 'followup', '81', '10068', '{\"followup_id\": \"81\"}', '1777953600');
INSERT INTO `tao_crm_event_log` VALUES ('51', 'follow_up_completed', 'followup', '82', '10001', '{\"followup_id\": \"82\"}', '1772769600');
INSERT INTO `tao_crm_event_log` VALUES ('52', 'follow_up_completed', 'followup', '83', '10071', '{\"followup_id\": \"83\"}', '1771041600');
INSERT INTO `tao_crm_event_log` VALUES ('53', 'follow_up_completed', 'followup', '84', '10001', '{\"followup_id\": \"84\"}', '1781323200');
INSERT INTO `tao_crm_event_log` VALUES ('54', 'follow_up_completed', 'followup', '85', '10001', '{\"followup_id\": \"85\"}', '1781150400');
INSERT INTO `tao_crm_event_log` VALUES ('55', 'follow_up_completed', 'followup', '86', '10001', '{\"followup_id\": \"86\"}', '1780804800');
INSERT INTO `tao_crm_event_log` VALUES ('56', 'follow_up_completed', 'followup', '87', '10001', '{\"followup_id\": \"87\"}', '1781236800');
INSERT INTO `tao_crm_event_log` VALUES ('57', 'follow_up_completed', 'followup', '88', '10001', '{\"followup_id\": \"88\"}', '1780977600');
INSERT INTO `tao_crm_event_log` VALUES ('58', 'follow_up_completed', 'followup', '89', '10068', '{\"followup_id\": \"89\"}', '1781150400');
INSERT INTO `tao_crm_event_log` VALUES ('59', 'follow_up_completed', 'followup', '90', '10068', '{\"followup_id\": \"90\"}', '1781323200');
INSERT INTO `tao_crm_event_log` VALUES ('60', 'follow_up_completed', 'followup', '91', '10001', '{\"followup_id\": \"91\"}', '1781323200');
INSERT INTO `tao_crm_event_log` VALUES ('61', 'follow_up_completed', 'followup', '92', '10001', '{\"followup_id\": \"92\"}', '1781064000');
INSERT INTO `tao_crm_event_log` VALUES ('62', 'follow_up_completed', 'followup', '93', '10001', '{\"followup_id\": \"93\"}', '1780804800');
INSERT INTO `tao_crm_event_log` VALUES ('63', 'follow_up_completed', 'followup', '94', '10001', '{\"followup_id\": \"94\"}', '1778817600');
INSERT INTO `tao_crm_event_log` VALUES ('64', 'follow_up_completed', 'followup', '95', '10001', '{\"followup_id\": \"95\"}', '1780977600');
INSERT INTO `tao_crm_event_log` VALUES ('65', 'follow_up_completed', 'followup', '96', '10001', '{\"followup_id\": \"96\"}', '1779249600');
INSERT INTO `tao_crm_event_log` VALUES ('66', 'follow_up_completed', 'followup', '97', '10001', '{\"followup_id\": \"97\"}', '1778385600');
INSERT INTO `tao_crm_event_log` VALUES ('67', 'follow_up_completed', 'followup', '98', '10001', '{\"followup_id\": \"98\"}', '1773201600');
INSERT INTO `tao_crm_event_log` VALUES ('68', 'follow_up_completed', 'followup', '99', '10001', '{\"followup_id\": \"99\"}', '1781064000');
INSERT INTO `tao_crm_event_log` VALUES ('69', 'follow_up_completed', 'followup', '100', '10001', '{\"followup_id\": \"100\"}', '1780718400');
INSERT INTO `tao_crm_event_log` VALUES ('70', 'follow_up_completed', 'followup', '101', '10001', '{\"followup_id\": \"101\"}', '1780027200');
INSERT INTO `tao_crm_event_log` VALUES ('71', 'follow_up_completed', 'followup', '102', '10001', '{\"followup_id\": \"102\"}', '1771905600');
INSERT INTO `tao_crm_event_log` VALUES ('72', 'follow_up_completed', 'followup', '103', '10001', '{\"followup_id\": \"103\"}', '1770523200');
INSERT INTO `tao_crm_event_log` VALUES ('73', 'follow_up_completed', 'followup', '104', '10068', '{\"followup_id\": \"104\"}', '1770177600');
INSERT INTO `tao_crm_event_log` VALUES ('74', 'follow_up_completed', 'followup', '105', '10068', '{\"followup_id\": \"105\"}', '1769313600');
INSERT INTO `tao_crm_event_log` VALUES ('75', 'follow_up_completed', 'followup', '106', '10071', '{\"followup_id\": \"106\"}', '1766721600');
INSERT INTO `tao_crm_event_log` VALUES ('76', 'follow_up_completed', 'followup', '107', '10001', '{\"followup_id\": \"107\"}', '1781236800');
INSERT INTO `tao_crm_event_log` VALUES ('77', 'follow_up_completed', 'followup', '108', '10001', '{\"followup_id\": \"108\"}', '1780977600');
INSERT INTO `tao_crm_event_log` VALUES ('78', 'follow_up_completed', 'followup', '109', '10001', '{\"followup_id\": \"109\"}', '1781150400');
INSERT INTO `tao_crm_event_log` VALUES ('79', 'follow_up_completed', 'followup', '110', '10068', '{\"followup_id\": \"110\"}', '1780977600');
INSERT INTO `tao_crm_event_log` VALUES ('80', 'follow_up_completed', 'followup', '111', '10068', '{\"followup_id\": \"111\"}', '1780718400');
INSERT INTO `tao_crm_event_log` VALUES ('81', 'follow_up_completed', 'followup', '112', '10001', '{\"followup_id\": \"112\"}', '1781064000');
INSERT INTO `tao_crm_event_log` VALUES ('82', 'follow_up_completed', 'followup', '113', '10001', '{\"followup_id\": \"113\"}', '1780718400');
INSERT INTO `tao_crm_event_log` VALUES ('83', 'follow_up_completed', 'followup', '114', '10068', '{\"followup_id\": \"114\"}', '1780891200');
INSERT INTO `tao_crm_event_log` VALUES ('84', 'follow_up_completed', 'followup', '115', '10071', '{\"followup_id\": \"115\"}', '1781150400');
INSERT INTO `tao_crm_event_log` VALUES ('85', 'follow_up_completed', 'followup', '116', '10071', '{\"followup_id\": \"116\"}', '1780977600');
INSERT INTO `tao_crm_event_log` VALUES ('86', 'follow_up_completed', 'followup', '117', '10071', '{\"followup_id\": \"117\"}', '1780545600');
INSERT INTO `tao_crm_event_log` VALUES ('87', 'follow_up_completed', 'followup', '118', '10001', '{\"followup_id\": \"118\"}', '1781236800');
INSERT INTO `tao_crm_event_log` VALUES ('88', 'follow_up_completed', 'followup', '119', '10001', '{\"followup_id\": \"119\"}', '1780545600');
INSERT INTO `tao_crm_event_log` VALUES ('89', 'follow_up_completed', 'followup', '120', '10001', '{\"followup_id\": \"120\"}', '1779681600');
INSERT INTO `tao_crm_event_log` VALUES ('90', 'follow_up_completed', 'followup', '121', '10068', '{\"followup_id\": \"121\"}', '1781323200');
INSERT INTO `tao_crm_event_log` VALUES ('91', 'follow_up_completed', 'followup', '122', '10071', '{\"followup_id\": \"122\"}', '1781236800');
INSERT INTO `tao_crm_event_log` VALUES ('92', 'follow_up_completed', 'followup', '123', '10071', '{\"followup_id\": \"123\"}', '1781323200');
INSERT INTO `tao_crm_event_log` VALUES ('93', 'follow_up_completed', 'followup', '124', '10068', '{\"followup_id\": \"124\"}', '1780804800');
INSERT INTO `tao_crm_event_log` VALUES ('94', 'follow_up_completed', 'followup', '125', '10068', '{\"followup_id\": \"125\"}', '1781064000');
INSERT INTO `tao_crm_event_log` VALUES ('95', 'follow_up_completed', 'followup', '126', '10071', '{\"followup_id\": \"126\"}', '1781150400');
INSERT INTO `tao_crm_event_log` VALUES ('96', 'follow_up_completed', 'followup', '127', '10001', '{\"followup_id\": \"127\"}', '1780891200');
INSERT INTO `tao_crm_event_log` VALUES ('97', 'follow_up_completed', 'followup', '128', '10001', '{\"followup_id\": \"128\"}', '1780545600');
INSERT INTO `tao_crm_event_log` VALUES ('98', 'follow_up_completed', 'followup', '129', '10001', '{\"followup_id\": \"129\"}', '1780200000');
INSERT INTO `tao_crm_event_log` VALUES ('99', 'follow_up_completed', 'followup', '130', '10068', '{\"followup_id\": \"130\"}', '1780718400');
INSERT INTO `tao_crm_event_log` VALUES ('100', 'follow_up_completed', 'followup', '131', '10001', '{\"followup_id\": \"131\"}', '1780804800');
INSERT INTO `tao_crm_event_log` VALUES ('101', 'follow_up_completed', 'followup', '132', '10001', '{\"followup_id\": \"132\"}', '1778817600');
INSERT INTO `tao_crm_event_log` VALUES ('102', 'follow_up_completed', 'followup', '133', '10001', '{\"followup_id\": \"133\"}', '1778817600');
INSERT INTO `tao_crm_event_log` VALUES ('103', 'quotation_sent', 'quotation', '30', '10001', '{\"quotation_id\": \"30\"}', '1779681600');
INSERT INTO `tao_crm_event_log` VALUES ('104', 'quotation_sent', 'quotation', '31', '10068', '{\"quotation_id\": \"31\"}', '1780632000');
INSERT INTO `tao_crm_event_log` VALUES ('105', 'quotation_sent', 'quotation', '32', '10001', '{\"quotation_id\": \"32\"}', '1780804800');
INSERT INTO `tao_crm_event_log` VALUES ('106', 'quotation_confirmed', 'quotation', '32', '10001', '{\"quotation_id\": \"32\"}', '1780804800');
INSERT INTO `tao_crm_event_log` VALUES ('107', 'quotation_sent', 'quotation', '33', '10001', '{\"quotation_id\": \"33\"}', '1781236800');
INSERT INTO `tao_crm_event_log` VALUES ('108', 'quotation_sent', 'quotation', '34', '10068', '{\"quotation_id\": \"34\"}', '1781323200');
INSERT INTO `tao_crm_event_log` VALUES ('109', 'quotation_sent', 'quotation', '35', '10001', '{\"quotation_id\": \"35\"}', '1772510400');
INSERT INTO `tao_crm_event_log` VALUES ('110', 'quotation_confirmed', 'quotation', '35', '10001', '{\"quotation_id\": \"35\"}', '1772510400');
INSERT INTO `tao_crm_event_log` VALUES ('111', 'quotation_sent', 'quotation', '36', '10001', '{\"quotation_id\": \"36\"}', '1775102400');
INSERT INTO `tao_crm_event_log` VALUES ('112', 'quotation_confirmed', 'quotation', '36', '10001', '{\"quotation_id\": \"36\"}', '1775102400');
INSERT INTO `tao_crm_event_log` VALUES ('113', 'quotation_sent', 'quotation', '40', '10001', '{\"quotation_id\": \"40\"}', '1780372800');
INSERT INTO `tao_crm_event_log` VALUES ('114', 'quotation_confirmed', 'quotation', '40', '10001', '{\"quotation_id\": \"40\"}', '1780372800');
INSERT INTO `tao_crm_event_log` VALUES ('115', 'quotation_sent', 'quotation', '41', '10001', '{\"quotation_id\": \"41\"}', '1780891200');
INSERT INTO `tao_crm_event_log` VALUES ('116', 'quotation_sent', 'quotation', '42', '10001', '{\"quotation_id\": \"42\"}', '1781064000');
INSERT INTO `tao_crm_event_log` VALUES ('117', 'quotation_sent', 'quotation', '43', '10068', '{\"quotation_id\": \"43\"}', '1778904000');
INSERT INTO `tao_crm_event_log` VALUES ('118', 'quotation_rejected', 'quotation', '43', '10068', '{\"reason\": \"价格/交期不匹配\", \"quotation_id\": \"43\"}', '1778904000');
INSERT INTO `tao_crm_event_log` VALUES ('119', 'quotation_sent', 'quotation', '44', '10001', '{\"quotation_id\": \"44\"}', '1780718400');
INSERT INTO `tao_crm_event_log` VALUES ('120', 'quotation_confirmed', 'quotation', '44', '10001', '{\"quotation_id\": \"44\"}', '1780718400');
INSERT INTO `tao_crm_event_log` VALUES ('121', 'customer_created', 'customer', '49', '10001', '{\"source\": \"客户介绍\", \"industry\": \"科技型\", \"owner_id\": 10001, \"creator_id\": 10001, \"customer_name\": \"1212\"}', '1781403290');
INSERT INTO `tao_crm_event_log` VALUES ('122', 'follow_up_completed', 'customer', '49', '10001', '{\"result\": \"有效\", \"follow_type\": \"电话\", \"followup_id\": \"74\"}', '1781403296');
INSERT INTO `tao_crm_event_log` VALUES ('123', 'quotation_confirmed', 'quotation', '46', '10071', '{\"quotation_id\": \"46\"}', '1780545600');
INSERT INTO `tao_crm_event_log` VALUES ('124', 'quotation_sent', 'quotation', '47', '10071', '{\"quotation_id\": \"47\"}', '1780804800');
INSERT INTO `tao_crm_event_log` VALUES ('125', 'quotation_sent', 'quotation', '48', '10068', '{\"quotation_id\": \"48\"}', '1781150400');
INSERT INTO `tao_crm_event_log` VALUES ('126', 'quotation_sent', 'quotation', '49', '10071', '{\"quotation_id\": \"49\"}', '1780545600');
INSERT INTO `tao_crm_event_log` VALUES ('127', 'quotation_sent', 'quotation', '50', '10068', '{\"quotation_id\": \"50\"}', '1780113600');
INSERT INTO `tao_crm_event_log` VALUES ('128', 'quotation_sent', 'quotation', '51', '10001', '{\"quotation_id\": \"51\"}', '1779163200');
INSERT INTO `tao_crm_event_log` VALUES ('129', 'quotation_rejected', 'quotation', '51', '10001', '{\"reason\": \"价格/交期不匹配\", \"quotation_id\": \"51\"}', '1779163200');
INSERT INTO `tao_crm_event_log` VALUES ('130', 'quotation_sent', 'quotation', '52', '10001', '{\"quotation_id\": \"52\"}', '1779508800');
INSERT INTO `tao_crm_event_log` VALUES ('131', 'quotation_converted', 'quotation', '52', '10001', '{\"quotation_id\": \"52\"}', '1779508800');
INSERT INTO `tao_crm_event_log` VALUES ('132', 'quotation_sent', 'quotation', '53', '10001', '{\"quotation_id\": \"53\"}', '1779768000');
INSERT INTO `tao_crm_event_log` VALUES ('133', 'quotation_confirmed', 'quotation', '53', '10001', '{\"quotation_id\": \"53\"}', '1779768000');
INSERT INTO `tao_crm_event_log` VALUES ('134', 'quotation_sent', 'quotation', '54', '10071', '{\"quotation_id\": \"54\"}', '1781236800');
INSERT INTO `tao_crm_event_log` VALUES ('135', 'quotation_sent', 'quotation', '55', '10001', '{\"quotation_id\": \"55\"}', '1781323200');
INSERT INTO `tao_crm_event_log` VALUES ('136', 'quotation_sent', 'quotation', '56', '10001', '{\"quotation_id\": \"56\"}', '1780459200');
INSERT INTO `tao_crm_event_log` VALUES ('137', 'quotation_converted', 'quotation', '56', '10001', '{\"quotation_id\": \"56\"}', '1780459200');
INSERT INTO `tao_crm_event_log` VALUES ('138', 'quotation_sent', 'quotation', '57', '10001', '{\"quotation_id\": \"57\"}', '1775102400');
INSERT INTO `tao_crm_event_log` VALUES ('139', 'quotation_confirmed', 'quotation', '57', '10001', '{\"quotation_id\": \"57\"}', '1775102400');
INSERT INTO `tao_crm_event_log` VALUES ('140', 'quotation_sent', 'quotation', '58', '10068', '{\"quotation_id\": \"58\"}', '1777694400');
INSERT INTO `tao_crm_event_log` VALUES ('141', 'quotation_confirmed', 'quotation', '58', '10068', '{\"quotation_id\": \"58\"}', '1777694400');
INSERT INTO `tao_crm_event_log` VALUES ('142', 'quotation_sent', 'quotation', '59', '10071', '{\"quotation_id\": \"59\"}', '1781409600');
INSERT INTO `tao_crm_event_log` VALUES ('143', 'quotation_sent', 'quotation', '60', '10001', '{\"quotation_id\": \"60\"}', '1775188800');
INSERT INTO `tao_crm_event_log` VALUES ('144', 'quotation_confirmed', 'quotation', '60', '10001', '{\"quotation_id\": \"60\"}', '1775188800');
INSERT INTO `tao_crm_event_log` VALUES ('145', 'order_created', 'order', '20', '10001', '{\"order_id\": \"20\"}', '1774411200');
INSERT INTO `tao_crm_event_log` VALUES ('146', 'order_status_changed', 'order', '20', '10001', '{\"order_id\": \"20\", \"new_status\": \"5\"}', '1774692804');
INSERT INTO `tao_crm_event_log` VALUES ('147', 'order_created', 'order', '21', '10001', '{\"order_id\": \"21\"}', '1778299200');
INSERT INTO `tao_crm_event_log` VALUES ('148', 'order_status_changed', 'order', '21', '10001', '{\"order_id\": \"21\", \"new_status\": \"5\"}', '1778721103');
INSERT INTO `tao_crm_event_log` VALUES ('149', 'order_created', 'order', '22', '10001', '{\"order_id\": \"22\"}', '1766289600');
INSERT INTO `tao_crm_event_log` VALUES ('150', 'order_status_changed', 'order', '22', '10001', '{\"order_id\": \"22\", \"new_status\": \"5\"}', '1766469809');
INSERT INTO `tao_crm_event_log` VALUES ('151', 'order_created', 'order', '23', '10001', '{\"order_id\": \"23\"}', '1771473600');
INSERT INTO `tao_crm_event_log` VALUES ('152', 'order_status_changed', 'order', '23', '10001', '{\"order_id\": \"23\", \"new_status\": \"5\"}', '1771965028');
INSERT INTO `tao_crm_event_log` VALUES ('153', 'order_created', 'order', '24', '10001', '{\"order_id\": \"24\"}', '1776657600');
INSERT INTO `tao_crm_event_log` VALUES ('154', 'order_status_changed', 'order', '24', '10001', '{\"order_id\": \"24\", \"new_status\": \"5\"}', '1776875130');
INSERT INTO `tao_crm_event_log` VALUES ('155', 'order_created', 'order', '25', '10001', '{\"order_id\": \"25\"}', '1773201600');
INSERT INTO `tao_crm_event_log` VALUES ('156', 'order_status_changed', 'order', '25', '10001', '{\"order_id\": \"25\", \"new_status\": \"5\"}', '1773454308');
INSERT INTO `tao_crm_event_log` VALUES ('157', 'order_created', 'order', '26', '10001', '{\"order_id\": \"26\"}', '1776225600');
INSERT INTO `tao_crm_event_log` VALUES ('158', 'order_status_changed', 'order', '26', '10001', '{\"order_id\": \"26\", \"new_status\": \"5\"}', '1776361188');
INSERT INTO `tao_crm_event_log` VALUES ('159', 'order_created', 'order', '27', '10068', '{\"order_id\": \"27\"}', '1772337600');
INSERT INTO `tao_crm_event_log` VALUES ('160', 'order_status_changed', 'order', '27', '10068', '{\"order_id\": \"27\", \"new_status\": \"5\"}', '1772556353');
INSERT INTO `tao_crm_event_log` VALUES ('161', 'order_created', 'order', '28', '10068', '{\"order_id\": \"28\"}', '1774929600');
INSERT INTO `tao_crm_event_log` VALUES ('162', 'order_status_changed', 'order', '28', '10068', '{\"order_id\": \"28\", \"new_status\": \"5\"}', '1775208038');
INSERT INTO `tao_crm_event_log` VALUES ('163', 'order_created', 'order', '29', '10001', '{\"order_id\": \"29\"}', '1768449600');
INSERT INTO `tao_crm_event_log` VALUES ('164', 'order_status_changed', 'order', '29', '10001', '{\"order_id\": \"29\", \"new_status\": \"5\"}', '1768665679');
INSERT INTO `tao_crm_event_log` VALUES ('165', 'order_created', 'order', '30', '10071', '{\"order_id\": \"30\"}', '1772337600');
INSERT INTO `tao_crm_event_log` VALUES ('166', 'order_status_changed', 'order', '30', '10071', '{\"order_id\": \"30\", \"new_status\": \"5\"}', '1772771320');
INSERT INTO `tao_crm_event_log` VALUES ('167', 'order_created', 'order', '31', '10001', '{\"order_id\": \"31\"}', '1781236800');
INSERT INTO `tao_crm_event_log` VALUES ('168', 'order_status_changed', 'order', '31', '10001', '{\"order_id\": \"31\", \"new_status\": \"4\"}', '1781548207');
INSERT INTO `tao_crm_event_log` VALUES ('169', 'order_created', 'order', '32', '10001', '{\"order_id\": \"32\"}', '1780891200');
INSERT INTO `tao_crm_event_log` VALUES ('170', 'order_created', 'order', '33', '10068', '{\"order_id\": \"33\"}', '1779249600');
INSERT INTO `tao_crm_event_log` VALUES ('171', 'order_status_changed', 'order', '33', '10068', '{\"order_id\": \"33\", \"new_status\": \"5\"}', '1779586289');
INSERT INTO `tao_crm_event_log` VALUES ('172', 'order_created', 'order', '34', '10068', '{\"order_id\": \"34\"}', '1780286400');
INSERT INTO `tao_crm_event_log` VALUES ('173', 'order_created', 'order', '40', '10001', '{\"order_id\": \"40\"}', '1772337600');
INSERT INTO `tao_crm_event_log` VALUES ('174', 'order_status_changed', 'order', '40', '10001', '{\"order_id\": \"40\", \"new_status\": \"5\"}', '1772710886');
INSERT INTO `tao_crm_event_log` VALUES ('175', 'order_created', 'order', '41', '10001', '{\"order_id\": \"41\"}', '1776225600');
INSERT INTO `tao_crm_event_log` VALUES ('176', 'order_status_changed', 'order', '41', '10001', '{\"order_id\": \"41\", \"new_status\": \"5\"}', '1776501239');
INSERT INTO `tao_crm_event_log` VALUES ('177', 'order_created', 'order', '42', '10001', '{\"order_id\": \"42\"}', '1779249600');
INSERT INTO `tao_crm_event_log` VALUES ('178', 'order_status_changed', 'order', '42', '10001', '{\"order_id\": \"42\", \"new_status\": \"5\"}', '1779669330');
INSERT INTO `tao_crm_event_log` VALUES ('179', 'order_created', 'order', '43', '10001', '{\"order_id\": \"43\"}', '1776657600');
INSERT INTO `tao_crm_event_log` VALUES ('180', 'order_status_changed', 'order', '43', '10001', '{\"order_id\": \"43\", \"new_status\": \"5\"}', '1777194131');
INSERT INTO `tao_crm_event_log` VALUES ('181', 'order_created', 'order', '44', '10001', '{\"order_id\": \"44\"}', '1779681600');
INSERT INTO `tao_crm_event_log` VALUES ('182', 'order_status_changed', 'order', '44', '10001', '{\"order_id\": \"44\", \"new_status\": \"5\"}', '1780059218');
INSERT INTO `tao_crm_event_log` VALUES ('183', 'order_created', 'order', '45', '10001', '{\"order_id\": \"45\"}', '1764993600');
INSERT INTO `tao_crm_event_log` VALUES ('184', 'order_status_changed', 'order', '45', '10001', '{\"order_id\": \"45\", \"new_status\": \"5\"}', '1765458320');
INSERT INTO `tao_crm_event_log` VALUES ('185', 'order_created', 'order', '46', '10001', '{\"order_id\": \"46\"}', '1770177600');
INSERT INTO `tao_crm_event_log` VALUES ('186', 'order_status_changed', 'order', '46', '10001', '{\"order_id\": \"46\", \"new_status\": \"5\"}', '1770418736');
INSERT INTO `tao_crm_event_log` VALUES ('187', 'order_created', 'order', '47', '10001', '{\"order_id\": \"47\"}', '1778385600');
INSERT INTO `tao_crm_event_log` VALUES ('188', 'order_status_changed', 'order', '47', '10001', '{\"order_id\": \"47\", \"new_status\": \"5\"}', '1778931969');
INSERT INTO `tao_crm_event_log` VALUES ('189', 'order_created', 'order', '48', '10001', '{\"order_id\": \"48\"}', '1779940800');
INSERT INTO `tao_crm_event_log` VALUES ('190', 'order_status_changed', 'order', '48', '10001', '{\"order_id\": \"48\", \"new_status\": \"5\"}', '1780168237');
INSERT INTO `tao_crm_event_log` VALUES ('191', 'order_created', 'order', '49', '10001', '{\"order_id\": \"49\"}', '1781064000');
INSERT INTO `tao_crm_event_log` VALUES ('192', 'order_status_changed', 'order', '49', '10001', '{\"order_id\": \"49\", \"new_status\": \"4\"}', '1781162899');
INSERT INTO `tao_crm_event_log` VALUES ('193', 'order_created', 'order', '50', '10001', '{\"order_id\": \"50\"}', '1780286400');
INSERT INTO `tao_crm_event_log` VALUES ('194', 'order_status_changed', 'order', '50', '10001', '{\"order_id\": \"50\", \"new_status\": \"4\"}', '1780720142');
INSERT INTO `tao_crm_event_log` VALUES ('195', 'order_created', 'order', '51', '10068', '{\"order_id\": \"51\"}', '1778817600');
INSERT INTO `tao_crm_event_log` VALUES ('196', 'order_status_changed', 'order', '51', '10068', '{\"order_id\": \"51\", \"new_status\": \"5\"}', '1779419069');
INSERT INTO `tao_crm_event_log` VALUES ('197', 'order_created', 'order', '52', '10001', '{\"order_id\": \"52\"}', '1780632000');
INSERT INTO `tao_crm_event_log` VALUES ('198', 'order_created', 'order', '53', '10001', '{\"order_id\": \"53\"}', '1780891200');
INSERT INTO `tao_crm_event_log` VALUES ('199', 'order_created', 'order', '54', '10001', '{\"order_id\": \"54\"}', '1781150400');
INSERT INTO `tao_crm_event_log` VALUES ('200', 'order_created', 'order', '55', '10068', '{\"order_id\": \"55\"}', '1777608000');
INSERT INTO `tao_crm_event_log` VALUES ('201', 'order_status_changed', 'order', '55', '10068', '{\"order_id\": \"55\", \"new_status\": \"6\"}', '1778117513');
INSERT INTO `tao_crm_event_log` VALUES ('202', 'order_created', 'order', '56', '10071', '{\"order_id\": \"56\"}', '1780113600');
INSERT INTO `tao_crm_event_log` VALUES ('203', 'order_status_changed', 'order', '56', '10071', '{\"order_id\": \"56\", \"new_status\": \"5\"}', '1780463891');
INSERT INTO `tao_crm_event_log` VALUES ('204', 'order_created', 'order', '57', '10001', '{\"order_id\": \"57\"}', '1781236800');
INSERT INTO `tao_crm_event_log` VALUES ('205', 'order_created', 'order', '58', '10068', '{\"order_id\": \"58\"}', '1780372800');
INSERT INTO `tao_crm_event_log` VALUES ('206', 'order_created', 'order', '59', '10068', '{\"order_id\": \"59\"}', '1779422400');
INSERT INTO `tao_crm_event_log` VALUES ('207', 'order_status_changed', 'order', '59', '10068', '{\"order_id\": \"59\", \"new_status\": \"5\"}', '1779999655');
INSERT INTO `tao_crm_event_log` VALUES ('208', 'order_created', 'order', '60', '10071', '{\"order_id\": \"60\"}', '1780804800');
INSERT INTO `tao_crm_event_log` VALUES ('209', 'order_created', 'order', '61', '10071', '{\"order_id\": \"61\"}', '1779076800');
INSERT INTO `tao_crm_event_log` VALUES ('210', 'order_status_changed', 'order', '61', '10071', '{\"order_id\": \"61\", \"new_status\": \"5\"}', '1779305215');
INSERT INTO `tao_crm_event_log` VALUES ('211', 'order_created', 'order', '62', '10071', '{\"order_id\": \"62\"}', '1780459200');
INSERT INTO `tao_crm_event_log` VALUES ('212', 'order_status_changed', 'order', '62', '10071', '{\"order_id\": \"62\", \"new_status\": \"4\"}', '1780664271');
INSERT INTO `tao_crm_event_log` VALUES ('213', 'order_created', 'order', '63', '10001', '{\"order_id\": \"63\"}', '1779681600');
INSERT INTO `tao_crm_event_log` VALUES ('214', 'order_status_changed', 'order', '63', '10001', '{\"order_id\": \"63\", \"new_status\": \"5\"}', '1780203409');
INSERT INTO `tao_crm_event_log` VALUES ('215', 'order_created', 'order', '64', '10001', '{\"order_id\": \"64\"}', '1778212800');
INSERT INTO `tao_crm_event_log` VALUES ('216', 'order_status_changed', 'order', '64', '10001', '{\"order_id\": \"64\", \"new_status\": \"6\"}', '1778789867');
INSERT INTO `tao_crm_event_log` VALUES ('217', 'order_created', 'order', '65', '10071', '{\"order_id\": \"65\"}', '1781323200');
INSERT INTO `tao_crm_event_log` VALUES ('218', 'contract_signed', 'contract', '1', '10001', '{\"contract_id\": 1, \"contract_no\": \"CT-20260301-001\"}', '1772337600');
INSERT INTO `tao_crm_event_log` VALUES ('219', 'contract_signed', 'contract', '2', '10001', '{\"contract_id\": 2, \"contract_no\": \"CT-20260510-002\"}', '1778385600');
INSERT INTO `tao_crm_event_log` VALUES ('220', 'contract_signed', 'contract', '3', '10001', '{\"contract_id\": 3, \"contract_no\": \"CT-20260520-003\"}', '1779940800');
INSERT INTO `tao_crm_event_log` VALUES ('221', 'contract_signed', 'contract', '4', '10001', '{\"contract_id\": 4, \"contract_no\": \"CT-20260501-004\"}', '1778817600');
INSERT INTO `tao_crm_event_log` VALUES ('222', 'contract_signed', 'contract', '5', '10001', '{\"contract_id\": 5, \"contract_no\": \"CT-20260605-005\"}', '1780632000');
INSERT INTO `tao_crm_event_log` VALUES ('223', 'contract_signed', 'contract', '6', '10001', '{\"contract_id\": 6, \"contract_no\": \"CT-20260420-006\"}', '1776657600');
INSERT INTO `tao_crm_event_log` VALUES ('224', 'contract_signed', 'contract', '7', '10001', '{\"contract_id\": 7, \"contract_no\": \"CT-20260610-007\"}', '1781064000');
INSERT INTO `tao_crm_event_log` VALUES ('225', 'contract_signed', 'contract', '8', '10001', '{\"contract_id\": 8, \"contract_no\": \"CT-20251201-008\"}', '1764993600');
INSERT INTO `tao_crm_event_log` VALUES ('226', 'customer_claimed', 'customer', '64', '10001', '{\"from_pool\": true, \"claimed_by\": 10001}', '1762401600');
INSERT INTO `tao_crm_event_log` VALUES ('227', 'customer_claimed', 'customer', '70', '10001', '{\"from_pool\": true, \"claimed_by\": 10001}', '1779681600');

-- ----------------------------
-- Table structure for tao_crm_followup
-- ----------------------------
DROP TABLE IF EXISTS `tao_crm_followup`;
CREATE TABLE `tao_crm_followup` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '跟进ID',
  `store_id` int(11) NOT NULL DEFAULT '0' COMMENT '租户ID',
  `customer_id` int(11) NOT NULL DEFAULT '0' COMMENT '客户ID',
  `contact_id` int(11) DEFAULT NULL COMMENT '联系人ID（可空）',
  `follow_type` varchar(50) DEFAULT NULL COMMENT '跟进方式：电话/拜访/微信/邮件/其他',
  `follow_content` text COMMENT '跟进内容',
  `attachment` varchar(500) DEFAULT '' COMMENT '附件',
  `ai_suggestion` text COMMENT 'AI跟进建议',
  `follow_date` int(11) DEFAULT NULL COMMENT '跟进时间',
  `next_follow_date` int(11) DEFAULT NULL COMMENT '下次跟进时间',
  `next_follow_content` varchar(500) DEFAULT NULL COMMENT '下次跟进计划',
  `result` varchar(50) DEFAULT NULL COMMENT '跟进结果：有效/无效/待定',
  `owner_user_id` int(11) DEFAULT NULL COMMENT '跟进人',
  `is_delete` tinyint(1) NOT NULL DEFAULT '0',
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_store_id` (`store_id`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_owner_user` (`owner_user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=144 DEFAULT CHARSET=utf8mb4 COMMENT='CRM跟进记录表';

-- ----------------------------
-- Records of tao_crm_followup
-- ----------------------------
INSERT INTO `tao_crm_followup` VALUES ('74', '10001', '49', null, '电话', '&lt;p&gt;1212&lt;/p&gt;', '', null, '1781403296', null, null, '有效', '10001', '0', '1781403296', '1781403296');
INSERT INTO `tao_crm_followup` VALUES ('75', '10001', '50', null, '电话', '与张总沟通UV油墨需求，客户对PRO系列感兴趣，要求出正式报价', '', null, '1779595200', null, null, '有效', '10001', '0', '1779595200', '1779595200');
INSERT INTO `tao_crm_followup` VALUES ('76', '10001', '52', null, '拜访', '拜访王总，了解近期生产情况，客户表示UV油墨用量稳定，下月会再采购', '', null, '1778299200', null, null, '有效', '10001', '0', '1778299200', '1778299200');
INSERT INTO `tao_crm_followup` VALUES ('77', '10001', '52', null, '电话', '确认上次订单交付情况，客户对品质满意', '', null, '1774411200', null, null, '有效', '10001', '0', '1774411200', '1774411200');
INSERT INTO `tao_crm_followup` VALUES ('78', '10001', '54', null, '电话', '客户表示有采购意向但需要时间评估，约定下月再联系', '', null, '1766376000', null, null, '有效', '10068', '0', '1766376000', '1766376000');
INSERT INTO `tao_crm_followup` VALUES ('79', '10001', '55', null, '拜访', '拜访后客户表示公司规模较小，担心产能不足，需要进一步了解', '', null, '1766116800', null, null, '待定', '10071', '0', '1766116800', '1766116800');
INSERT INTO `tao_crm_followup` VALUES ('80', '10001', '56', null, '电话', '钱厂长反映上次产品色差问题，已协调技术处理，但客户仍有些不满', '', null, '1778385600', null, null, '待定', '10001', '0', '1778385600', '1778385600');
INSERT INTO `tao_crm_followup` VALUES ('81', '10001', '57', null, '微信', '简单问候，客户回复较冷淡，提到在对比竞品价格', '', null, '1777953600', null, null, '无效', '10068', '0', '1777953600', '1777953600');
INSERT INTO `tao_crm_followup` VALUES ('82', '10001', '58', null, '电话', '客户说近期订单量减少，暂时不需要采购，后续再联系', '', null, '1772769600', null, null, '待定', '10001', '0', '1772769600', '1772769600');
INSERT INTO `tao_crm_followup` VALUES ('83', '10001', '59', null, '电话', '未接通，连续3次联系不上', '', null, '1771041600', null, null, '无效', '10071', '0', '1771041600', '1771041600');
INSERT INTO `tao_crm_followup` VALUES ('84', '10001', '60', null, '拜访', '拜访赵总，洽谈Q3采购计划。客户对UV LED固化油墨非常感兴趣，计划下月试样。已安排技术团队对接。', '', null, '1781323200', null, null, '有效', '10001', '0', '1781323200', '1781323200');
INSERT INTO `tao_crm_followup` VALUES ('85', '10001', '60', null, '微信', '发送UV LED固化油墨产品资料和技术参数', '', null, '1781150400', null, null, '有效', '10001', '0', '1781150400', '1781150400');
INSERT INTO `tao_crm_followup` VALUES ('86', '10001', '60', null, '电话', '首次联系，了解客户需求。客户主营高端包装印刷，年采购额约200万，对供应商资质要求高。', '', null, '1780804800', null, null, '有效', '10001', '0', '1780804800', '1780804800');
INSERT INTO `tao_crm_followup` VALUES ('87', '10001', '61', null, '电话', '与周总电话沟通，了解Q3招标需求。客户计划采购UV系列和水性系列，预算约50万。', '', null, '1781236800', null, null, '有效', '10001', '0', '1781236800', '1781236800');
INSERT INTO `tao_crm_followup` VALUES ('88', '10001', '61', null, '邮件', '发送公司资质文件、产品认证和案例介绍，为招标准备材料', '', null, '1780977600', null, null, '有效', '10001', '0', '1780977600', '1780977600');
INSERT INTO `tao_crm_followup` VALUES ('89', '10001', '62', null, '电话', '与吴主管沟通UV油墨技术需求，客户对环保型产品有明确倾向', '', null, '1781150400', null, null, '有效', '10068', '0', '1781150400', '1781150400');
INSERT INTO `tao_crm_followup` VALUES ('90', '10001', '63', null, '拜访', '拜访郑经理，现场介绍水性柔版油墨和UV LED油墨。客户表示技术指标符合要求，已报价。', '', null, '1781323200', null, null, '有效', '10068', '0', '1781323200', '1781323200');
INSERT INTO `tao_crm_followup` VALUES ('91', '10001', '86', null, '拜访', '拜访韩总，沟通年度框架协议。客户对UV LED油墨需求明确，已安排试样。', '', null, '1781323200', null, null, '有效', '10001', '0', '1781323200', '1781323200');
INSERT INTO `tao_crm_followup` VALUES ('92', '10001', '86', null, '电话', '确认上次订单交付时间，韩总表示满意', '', null, '1781064000', null, null, '有效', '10001', '0', '1781064000', '1781064000');
INSERT INTO `tao_crm_followup` VALUES ('93', '10001', '86', null, '微信', '发送新品UV LED固化油墨资料', '', null, '1780804800', null, null, '有效', '10001', '0', '1780804800', '1780804800');
INSERT INTO `tao_crm_followup` VALUES ('94', '10001', '86', null, '拜访', '首次拜访蓝海印务，了解采购需求，客户年采购量约120万', '', null, '1778817600', null, null, '有效', '10001', '0', '1778817600', '1778817600');
INSERT INTO `tao_crm_followup` VALUES ('95', '10001', '78', null, '电话', '与马总确认Q3采购计划，预计下月下单', '', null, '1780977600', null, null, '有效', '10001', '0', '1780977600', '1780977600');
INSERT INTO `tao_crm_followup` VALUES ('96', '10001', '78', null, '拜访', '季度拜访，回顾Q2合作情况，客户对品质满意', '', null, '1779249600', null, null, '有效', '10001', '0', '1779249600', '1779249600');
INSERT INTO `tao_crm_followup` VALUES ('97', '10001', '78', null, '微信', '发送季度对账单', '', null, '1778385600', null, null, '有效', '10001', '0', '1778385600', '1778385600');
INSERT INTO `tao_crm_followup` VALUES ('98', '10001', '78', null, '电话', '首次联系，介绍公司产品线', '', null, '1773201600', null, null, '有效', '10001', '0', '1773201600', '1773201600');
INSERT INTO `tao_crm_followup` VALUES ('99', '10001', '87', null, '拜访', '与沈飞技术总监深入交流特殊油墨需求，技术方案已通过', '', null, '1781064000', null, null, '有效', '10001', '0', '1781064000', '1781064000');
INSERT INTO `tao_crm_followup` VALUES ('100', '10001', '87', null, '邮件', '发送技术方案和认证文件', '', null, '1780718400', null, null, '有效', '10001', '0', '1780718400', '1780718400');
INSERT INTO `tao_crm_followup` VALUES ('101', '10001', '87', null, '电话', '初步了解需求：特殊防伪油墨+UV LED', '', null, '1780027200', null, null, '有效', '10001', '0', '1780027200', '1780027200');
INSERT INTO `tao_crm_followup` VALUES ('102', '10001', '80', null, '电话', '联系高经理，客户表示近期订单减少，暂无采购计划', '', null, '1771905600', null, null, '待定', '10001', '0', '1771905600', '1771905600');
INSERT INTO `tao_crm_followup` VALUES ('103', '10001', '80', null, '微信', '节日问候，客户未回复', '', null, '1770523200', null, null, '无效', '10001', '0', '1770523200', '1770523200');
INSERT INTO `tao_crm_followup` VALUES ('104', '10001', '81', null, '电话', '多次拨打未接通', '', null, '1770177600', null, null, '无效', '10068', '0', '1770177600', '1770177600');
INSERT INTO `tao_crm_followup` VALUES ('105', '10001', '81', null, '微信', '发送消息未回复', '', null, '1769313600', null, null, '无效', '10068', '0', '1769313600', '1769313600');
INSERT INTO `tao_crm_followup` VALUES ('106', '10001', '82', null, '电话', '石厂长说还在评估供货商，会再联系', '', null, '1766721600', null, null, '待定', '10071', '0', '1766721600', '1766721600');
INSERT INTO `tao_crm_followup` VALUES ('107', '10001', '70', null, '电话', '与新艺印务林主管沟通，客户对UV PRO系列感兴趣，已发报价', '', null, '1781236800', null, null, '有效', '10001', '0', '1781236800', '1781236800');
INSERT INTO `tao_crm_followup` VALUES ('108', '10001', '70', null, '微信', '发送UV PRO产品样品图片', '', null, '1780977600', null, null, '有效', '10001', '0', '1780977600', '1780977600');
INSERT INTO `tao_crm_followup` VALUES ('109', '10001', '71', null, '拜访', '拜访彩弘包装黄总，客户专注高端包装，对品质要求极高', '', null, '1781150400', null, null, '有效', '10001', '0', '1781150400', '1781150400');
INSERT INTO `tao_crm_followup` VALUES ('110', '10001', '72', null, '电话', '与杨工沟通技术需求，客户对UV油墨固化速度有特殊要求', '', null, '1780977600', null, null, '有效', '10068', '0', '1780977600', '1780977600');
INSERT INTO `tao_crm_followup` VALUES ('111', '10001', '72', null, '邮件', '发送UV油墨技术参数表', '', null, '1780718400', null, null, '有效', '10068', '0', '1780718400', '1780718400');
INSERT INTO `tao_crm_followup` VALUES ('112', '10001', '73', null, '拜访', '拜访金陵彩印徐总监，了解政府订单招标流程', '', null, '1781064000', null, null, '有效', '10001', '0', '1781064000', '1781064000');
INSERT INTO `tao_crm_followup` VALUES ('113', '10001', '73', null, '电话', '确认投标资质要求，准备材料', '', null, '1780718400', null, null, '有效', '10001', '0', '1780718400', '1780718400');
INSERT INTO `tao_crm_followup` VALUES ('114', '10001', '74', null, '微信', '与华彩印务邓主管聊天，客户关注环保认证', '', null, '1780891200', null, null, '有效', '10068', '0', '1780891200', '1780891200');
INSERT INTO `tao_crm_followup` VALUES ('115', '10001', '75', null, '电话', '与山城包装蒋工沟通，重庆市场对我们的水性系列有兴趣', '', null, '1781150400', null, null, '有效', '10071', '0', '1781150400', '1781150400');
INSERT INTO `tao_crm_followup` VALUES ('116', '10001', '76', null, '拜访', '拜访秦川印物秦经理，西安市场竞品较多，需突出优势', '', null, '1780977600', null, null, '有效', '10071', '0', '1780977600', '1780977600');
INSERT INTO `tao_crm_followup` VALUES ('117', '10001', '76', null, '微信', '发送竞品对比分析表', '', null, '1780545600', null, null, '有效', '10071', '0', '1780545600', '1780545600');
INSERT INTO `tao_crm_followup` VALUES ('118', '10001', '77', null, '电话', '与徽商印务方总初步接触，客户规模中等，有合作意向', '', null, '1781236800', null, null, '有效', '10001', '0', '1781236800', '1781236800');
INSERT INTO `tao_crm_followup` VALUES ('119', '10001', '79', null, '拜访', '拜访冀联印刷曹经理，现有客户维护，确认下批订单', '', null, '1780545600', null, null, '有效', '10001', '0', '1780545600', '1780545600');
INSERT INTO `tao_crm_followup` VALUES ('120', '10001', '79', null, '电话', '订单交付确认，客户满意', '', null, '1779681600', null, null, '有效', '10001', '0', '1779681600', '1779681600');
INSERT INTO `tao_crm_followup` VALUES ('121', '10001', '88', null, '电话', '与绿城包装韦主管沟通，客户是初创企业，预算有限', '', null, '1781323200', null, null, '有效', '10068', '0', '1781323200', '1781323200');
INSERT INTO `tao_crm_followup` VALUES ('122', '10001', '89', null, '微信', '与黔彩印务初步接触，发送公司介绍', '', null, '1781236800', null, null, '有效', '10071', '0', '1781236800', '1781236800');
INSERT INTO `tao_crm_followup` VALUES ('123', '10001', '92', null, '电话', '了解春城彩印莫主管需求，主要采购水性油墨', '', null, '1781323200', null, null, '有效', '10071', '0', '1781323200', '1781323200');
INSERT INTO `tao_crm_followup` VALUES ('124', '10001', '93', null, '电话', '赣江印物需要溶剂型油墨，已安排寄样', '', null, '1780804800', null, null, '有效', '10068', '0', '1780804800', '1780804800');
INSERT INTO `tao_crm_followup` VALUES ('125', '10001', '93', null, '微信', '确认样品已收到，客户正在测试', '', null, '1781064000', null, null, '有效', '10068', '0', '1781064000', '1781064000');
INSERT INTO `tao_crm_followup` VALUES ('126', '10001', '94', null, '电话', '海岛印刷主营旅游包装，需求小批量多品种', '', null, '1781150400', null, null, '有效', '10071', '0', '1781150400', '1781150400');
INSERT INTO `tao_crm_followup` VALUES ('127', '10001', '95', null, '拜访', '拜访西夏印务贺经理，客户对我们UV LED固化油墨很感兴趣', '', null, '1780891200', null, null, '有效', '10001', '0', '1780891200', '1780891200');
INSERT INTO `tao_crm_followup` VALUES ('128', '10001', '95', null, '邮件', '发送UV LED固化油墨全套技术文档', '', null, '1780545600', null, null, '有效', '10001', '0', '1780545600', '1780545600');
INSERT INTO `tao_crm_followup` VALUES ('129', '10001', '50', null, '邮件', '发送恒达彩印报价跟进邮件，询问客户决策进度', '', null, '1780200000', null, null, '有效', '10001', '0', '1780200000', '1780200000');
INSERT INTO `tao_crm_followup` VALUES ('130', '10001', '51', null, '微信', '向鹏程包装李经理发送产品对比资料', '', null, '1780718400', null, null, '有效', '10068', '0', '1780718400', '1780718400');
INSERT INTO `tao_crm_followup` VALUES ('131', '10001', '52', null, '电话', '联盛印刷回访，了解最近一批UV油墨使用情况', '', null, '1780804800', null, null, '有效', '10001', '0', '1780804800', '1780804800');
INSERT INTO `tao_crm_followup` VALUES ('132', '10001', '53', null, '拜访', '季度拜访南海彩艺，客户对水性光油有新增需求', '', null, '1778817600', null, null, '有效', '10001', '0', '1778817600', '1778817600');
INSERT INTO `tao_crm_followup` VALUES ('133', '10001', '56', null, '邮件', '发送色差问题解决方案和补偿提案', '', null, '1778817600', null, null, '待定', '10001', '0', '1778817600', '1778817600');
INSERT INTO `tao_crm_followup` VALUES ('134', '10001', '61', null, '微信', '中彩联周总发来招标文件预览，确认参与投标', '', null, '1781323200', null, null, '有效', '10001', '0', '1781323200', '1781323200');
INSERT INTO `tao_crm_followup` VALUES ('135', '10001', '62', null, '电话', '确认锦江印务样品测试进度，吴主管表示下周出结果', '', null, '1781236800', null, null, '有效', '10068', '0', '1781236800', '1781236800');
INSERT INTO `tao_crm_followup` VALUES ('136', '10001', '63', null, '微信', '长江印务郑经理对报价方案满意度高，等待内部审批', '', null, '1781323200', null, null, '有效', '10068', '0', '1781323200', '1781323200');
INSERT INTO `tao_crm_followup` VALUES ('137', '10001', '83', null, '电话', '陇西印物电话未接通，稍后再试', '', null, '1769313600', null, null, '无效', '10068', '0', '1769313600', '1769313600');
INSERT INTO `tao_crm_followup` VALUES ('138', '10001', '84', null, '电话', '天山印务已停机', '', null, '1764129600', null, null, '无效', '10001', '0', '1764129600', '1764129600');
INSERT INTO `tao_crm_followup` VALUES ('139', '10001', '90', null, '电话', '晋阳印刷首次联系，客户表示需要时间了解', '', null, '1781150400', null, null, '待定', '10001', '0', '1781150400', '1781150400');
INSERT INTO `tao_crm_followup` VALUES ('140', '10001', '91', null, '微信', '草原印务添加微信后暂无互动', '', null, '1780977600', null, null, '无效', '10068', '0', '1780977600', '1780977600');
INSERT INTO `tao_crm_followup` VALUES ('141', '10001', '54', null, '电话', '最后一次联系伟业印刷，客户仍说再评估', '', null, '1767585600', null, null, '无效', '10068', '0', '1767585600', '1767585600');
INSERT INTO `tao_crm_followup` VALUES ('142', '10001', '60', null, '微信', '与华艺赵总确认下周三的拜访时间', '', null, '1781406000', null, null, '有效', '10001', '0', '1781406000', '1781406000');
INSERT INTO `tao_crm_followup` VALUES ('143', '10001', '55', null, '电话', '横琴新材电话已无法接通，号码可能已变更', '', null, '1781402400', null, null, '无效', '10071', '0', '1781402400', '1781402400');

-- ----------------------------
-- Table structure for tao_crm_insight
-- ----------------------------
DROP TABLE IF EXISTS `tao_crm_insight`;
CREATE TABLE `tao_crm_insight` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idempotency_key` varchar(100) NOT NULL COMMENT '幂等键：type:target:user:date',
  `type` varchar(50) NOT NULL COMMENT 'followup_overdue/repurchase_window/pool_warning/churn_risk/dormant/cross_sell/anomaly/health_score',
  `priority` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0=普通 1=重要 2=紧急',
  `status` varchar(20) NOT NULL DEFAULT 'active' COMMENT 'active/dismissed/resolved/expired',
  `target_type` varchar(50) DEFAULT NULL COMMENT 'customer/quotation/order',
  `target_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL COMMENT '目标业务员',
  `title` varchar(200) DEFAULT NULL,
  `summary` varchar(500) DEFAULT NULL,
  `detail` json DEFAULT NULL COMMENT '结构化详情',
  `suggestion` varchar(500) DEFAULT NULL COMMENT 'AI行动建议（可空）',
  `suggestion_id` int(11) DEFAULT NULL COMMENT '关联crm_ai_suggestion（阶段3启用）',
  `source_rule` varchar(50) NOT NULL COMMENT '来源规则名',
  `rule_params` json DEFAULT NULL COMMENT '规则参数快照',
  `action_url` varchar(200) DEFAULT NULL COMMENT '点击跳转链接',
  `dismissed_reason` varchar(200) DEFAULT NULL,
  `resolved_by_event_id` int(11) DEFAULT NULL COMMENT '关联crm_event_log.id',
  `create_date` date NOT NULL COMMENT '生成日期',
  `create_time` int(11) NOT NULL,
  `update_time` int(11) DEFAULT NULL,
  `store_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_idempotency` (`idempotency_key`),
  KEY `idx_user_status_pri` (`user_id`,`status`,`priority`),
  KEY `idx_target` (`target_type`,`target_id`),
  KEY `idx_date` (`create_date`),
  KEY `idx_store_date` (`store_id`,`create_date`)
) ENGINE=InnoDB AUTO_INCREMENT=122 DEFAULT CHARSET=utf8mb4 COMMENT='AI洞察缓存';

-- ----------------------------
-- Records of tao_crm_insight
-- ----------------------------
INSERT INTO `tao_crm_insight` VALUES ('100', 'repurchase_window:customer:52:10001:2026-06-13', 'repurchase_window', '1', 'resolved', 'customer', '52', '10001', '联盛印刷 已进入复购窗口期', '距上次采购36天，历史平均采购间隔45天', '{\"avg_interval\": 45, \"days_since_last\": 36}', '建议主动联系王总，提及上次订单使用情况，推送新品信息', null, 'scanRepurchaseWindow', null, '/crm/customer/detail?id=52', null, null, '2026-06-13', '1781323200');
INSERT INTO `tao_crm_insight` VALUES ('101', 'churn_risk:customer:56:10001:2026-06-13', 'churn_risk', '2', 'active', 'customer', '56', '10001', '金平彩印 存在流失风险', '距上次采购60天（历史间隔35天），近30天无跟进', '{\"avg_interval\": 35, \"days_since_order\": 60}', '建议尽快联系钱厂长，了解色差问题处理进展，提供补偿方案', null, 'scanChurnRisk', null, '/crm/customer/detail?id=56', null, null, '2026-06-13', '1781323200');
INSERT INTO `tao_crm_insight` VALUES ('102', 'dormant:customer:58:10001:2026-06-13', 'dormant', '1', 'active', 'customer', '58', '10001', '永达印刷 已沉睡100天', '该客户曾有成交但超过100天未跟进', '{\"days_since_followup\": 100}', '建议发送节日问候+新品推荐，重新激活关系', null, 'scanDormant', null, '/crm/customer/detail?id=58', null, null, '2026-06-13', '1781323200');
INSERT INTO `tao_crm_insight` VALUES ('103', 'followup_overdue:customer:50:10001:2026-06-14', 'followup_overdue', '2', 'active', 'customer', '50', '10001', '广州恒达彩印有限公司 报价已发20天未回复', '报价单 Q-20260525-A1 发送于 2026-05-25，金额 ¥55100.00，已过20天未回复', '{\"quotation_id\": 30, \"quotation_no\": \"Q-20260525-A1\", \"avg_reply_days\": 3, \"days_since_send\": 20}', null, null, 'scanFollowupOverdue', '{\"threshold\": 7, \"avg_reply_days\": 3}', '/crm/quotation/detail?id=30', null, null, '2026-06-14', '1781422723');
INSERT INTO `tao_crm_insight` VALUES ('104', 'repurchase_window:customer:56:10001:2026-06-14', 'repurchase_window', '2', 'active', 'customer', '56', '10001', '汕头市金平彩印厂 已进入复购窗口期', '距上次采购60天，历史平均采购间隔35天，上次采购金额 ¥20000.00', '{\"threshold\": 28, \"avg_interval\": 35, \"last_order_id\": 26, \"days_since_last\": 60, \"last_order_amount\": \"20000.00\"}', null, null, 'scanRepurchaseWindow', '{\"threshold\": 0.8}', '/crm/customer/detail?id=56', null, null, '2026-06-14', '1781422723');
INSERT INTO `tao_crm_insight` VALUES ('105', 'churn_risk:customer:56:10001:2026-06-14', 'churn_risk', '2', 'active', 'customer', '56', '10001', '汕头市金平彩印厂 存在流失风险', '距上次采购60天（历史间隔35天），近30天无跟进记录', '{\"avg_interval\": 35, \"days_since_order\": 60, \"days_since_followup\": 35}', null, null, 'scanChurnRisk', '{\"churn_multiplier\": 1.5}', '/crm/customer/detail?id=56', null, null, '2026-06-14', '1781422723');
INSERT INTO `tao_crm_insight` VALUES ('106', 'repurchase_window:customer:80:10001:2026-06-14', 'repurchase_window', '2', 'active', 'customer', '80', '10001', '辽宁沈阳东北印务集团 已进入复购窗口期', '距上次采购130天，历史平均采购间隔60天，上次采购金额 ¥46000.00', '{\"threshold\": 48, \"avg_interval\": 60, \"last_order_id\": 46, \"days_since_last\": 130, \"last_order_amount\": \"46000.00\"}', null, null, 'scanRepurchaseWindow', '{\"threshold\": 0.8}', '/crm/customer/detail?id=80', null, null, '2026-06-14', '1781422723');
INSERT INTO `tao_crm_insight` VALUES ('107', 'churn_risk:customer:80:10001:2026-06-14', 'churn_risk', '2', 'active', 'customer', '80', '10001', '辽宁沈阳东北印务集团 存在流失风险', '距上次采购130天（历史间隔60天），近30天无跟进记录', '{\"avg_interval\": 60, \"days_since_order\": 130, \"days_since_followup\": 110}', null, null, 'scanChurnRisk', '{\"churn_multiplier\": 1.5}', '/crm/customer/detail?id=80', null, null, '2026-06-14', '1781422723');
INSERT INTO `tao_crm_insight` VALUES ('108', 'repurchase_window:customer:52:10001:2026-06-14', 'repurchase_window', '1', 'active', 'customer', '52', '10001', '东莞联盛印刷科技有限公司 已进入复购窗口期', '距上次采购36天，历史平均采购间隔45天，上次采购金额 ¥42000.00', '{\"threshold\": 36, \"avg_interval\": 45, \"last_order_id\": 21, \"days_since_last\": 36, \"last_order_amount\": \"42000.00\"}', null, null, 'scanRepurchaseWindow', '{\"threshold\": 0.8}', '/crm/customer/detail?id=52', null, null, '2026-06-14', '1781422723');
INSERT INTO `tao_crm_insight` VALUES ('109', 'repurchase_window:customer:53:10001:2026-06-14', 'repurchase_window', '1', 'active', 'customer', '53', '10001', '佛山南海彩艺印刷厂 已进入复购窗口期', '距上次采购55天，历史平均采购间隔60天，上次采购金额 ¥45000.00', '{\"threshold\": 48, \"avg_interval\": 60, \"last_order_id\": 24, \"days_since_last\": 55, \"last_order_amount\": \"45000.00\"}', null, null, 'scanRepurchaseWindow', '{\"threshold\": 0.8}', '/crm/customer/detail?id=53', null, null, '2026-06-14', '1781422723');
INSERT INTO `tao_crm_insight` VALUES ('110', 'dormant:customer:58:10001:2026-06-14', 'dormant', '1', 'active', 'customer', '58', '10001', '江门新会区永达印刷厂 已沉睡100天', '该客户曾有成交但超过100天未跟进，建议主动联系唤醒', '{\"order_count\": 1, \"last_order_amount\": \"11500.00\", \"days_since_followup\": 100}', null, null, 'scanDormant', '{\"dormant_days\": 90}', '/crm/customer/detail?id=58', null, null, '2026-06-14', '1781422723');
INSERT INTO `tao_crm_insight` VALUES ('111', 'dormant:customer:80:10001:2026-06-14', 'dormant', '1', 'active', 'customer', '80', '10001', '辽宁沈阳东北印务集团 已沉睡110天', '该客户曾有成交但超过110天未跟进，建议主动联系唤醒', '{\"order_count\": 2, \"last_order_amount\": \"46000.00\", \"days_since_followup\": 110}', null, null, 'scanDormant', '{\"dormant_days\": 90}', '/crm/customer/detail?id=80', null, null, '2026-06-14', '1781422723');
INSERT INTO `tao_crm_insight` VALUES ('112', 'repurchase_window:customer:57:10068:2026-06-14', 'repurchase_window', '2', 'active', 'customer', '57', '10068', '惠州博罗印刷包装公司 已进入复购窗口期', '距上次采购75天，历史平均采购间隔30天，上次采购金额 ¥23000.00', '{\"threshold\": 24, \"avg_interval\": 30, \"last_order_id\": 28, \"days_since_last\": 75, \"last_order_amount\": \"23000.00\"}', null, null, 'scanRepurchaseWindow', '{\"threshold\": 0.8}', '/crm/customer/detail?id=57', null, null, '2026-06-14', '1781422723');
INSERT INTO `tao_crm_insight` VALUES ('113', 'churn_risk:customer:57:10068:2026-06-14', 'churn_risk', '2', 'active', 'customer', '57', '10068', '惠州博罗印刷包装公司 存在流失风险', '距上次采购75天（历史间隔30天），近30天无跟进记录', '{\"avg_interval\": 30, \"days_since_order\": 75, \"days_since_followup\": 40}', null, null, 'scanChurnRisk', '{\"churn_multiplier\": 1.5}', '/crm/customer/detail?id=57', null, null, '2026-06-14', '1781422723');
INSERT INTO `tao_crm_insight` VALUES ('114', 'pool_warning:customer:83:10068:2026-06-14', 'pool_warning', '2', 'active', 'customer', '83', '10068', '甘肃兰州陇西印刷物资站 还剩0天将掉入公海', '最后跟进于 2025-12-16，距公海掉入还有0天（公海规则：180天未跟进自动掉入）', '{\"pool_days\": 180, \"remaining_days\": 0, \"last_followup_time\": 1765857600, \"days_since_followup\": 180}', null, null, 'scanPoolWarning', '{\"pool_days\": 180, \"warning_days\": 7}', '/crm/customer/detail?id=83', null, null, '2026-06-14', '1781422723');
INSERT INTO `tao_crm_insight` VALUES ('115', 'followup_overdue:customer:88:10068:2026-06-14', 'followup_overdue', '2', 'active', 'customer', '88', '10068', '广西南宁绿城包装印刷厂 报价已发15天未回复', '报价单 Q-20260530-GX 发送于 2026-05-30，金额 ¥18000.00，已过15天未回复', '{\"quotation_id\": 50, \"quotation_no\": \"Q-20260530-GX\", \"avg_reply_days\": 3, \"days_since_send\": 15}', null, null, 'scanFollowupOverdue', '{\"threshold\": 7, \"avg_reply_days\": 3}', '/crm/quotation/detail?id=50', null, null, '2026-06-14', '1781422723');
INSERT INTO `tao_crm_insight` VALUES ('116', 'followup_overdue:customer:51:10068:2026-06-14', 'followup_overdue', '1', 'active', 'customer', '51', '10068', '深圳鹏程包装有限公司 报价已发9天未回复', '报价单 Q-20260605-A2 发送于 2026-06-05，金额 ¥32000.00，已过9天未回复', '{\"quotation_id\": 31, \"quotation_no\": \"Q-20260605-A2\", \"avg_reply_days\": 3, \"days_since_send\": 9}', null, null, 'scanFollowupOverdue', '{\"threshold\": 7, \"avg_reply_days\": 3}', '/crm/quotation/detail?id=31', null, null, '2026-06-14', '1781422723');
INSERT INTO `tao_crm_insight` VALUES ('117', 'pool_warning:customer:54:10068:2026-06-14', 'pool_warning', '1', 'active', 'customer', '54', '10068', '中山市伟业印刷材料公司 还剩6天将掉入公海', '最后跟进于 2025-12-22，距公海掉入还有6天（公海规则：180天未跟进自动掉入）', '{\"pool_days\": 180, \"remaining_days\": 6, \"last_followup_time\": 1766376000, \"days_since_followup\": 174}', null, null, 'scanPoolWarning', '{\"pool_days\": 180, \"warning_days\": 7}', '/crm/customer/detail?id=54', null, null, '2026-06-14', '1781422723');
INSERT INTO `tao_crm_insight` VALUES ('118', 'pool_warning:customer:55:10071:2026-06-14', 'pool_warning', '2', 'active', 'customer', '55', '10071', '珠海横琴新材料有限公司 还剩3天将掉入公海', '最后跟进于 2025-12-19，距公海掉入还有3天（公海规则：180天未跟进自动掉入）', '{\"pool_days\": 180, \"remaining_days\": 3, \"last_followup_time\": 1766116800, \"days_since_followup\": 177}', null, null, 'scanPoolWarning', '{\"pool_days\": 180, \"warning_days\": 7}', '/crm/customer/detail?id=55', null, null, '2026-06-14', '1781422723');
INSERT INTO `tao_crm_insight` VALUES ('119', 'dormant:customer:59:10071:2026-06-14', 'dormant', '1', 'active', 'customer', '59', '10071', '肇庆端州印刷物资公司 已沉睡120天', '该客户曾有成交但超过120天未跟进，建议主动联系唤醒', '{\"order_count\": 1, \"last_order_amount\": \"8500.00\", \"days_since_followup\": 120}', null, null, 'scanDormant', '{\"dormant_days\": 90}', '/crm/customer/detail?id=59', null, null, '2026-06-14', '1781422723');
INSERT INTO `tao_crm_insight` VALUES ('120', 'followup_overdue:customer:76:10071:2026-06-14', 'followup_overdue', '1', 'active', 'customer', '76', '10071', '陕西西安秦川印刷物资公司 报价已发7天未回复', '报价单 Q-20260607-QC 发送于 2026-06-07，金额 ¥36860.00，已过7天未回复', '{\"quotation_id\": 47, \"quotation_no\": \"Q-20260607-QC\", \"avg_reply_days\": 3, \"days_since_send\": 7}', null, null, 'scanFollowupOverdue', '{\"threshold\": 7, \"avg_reply_days\": 3}', '/crm/quotation/detail?id=47', null, null, '2026-06-14', '1781422723');
INSERT INTO `tao_crm_insight` VALUES ('121', 'followup_overdue:customer:92:10071:2026-06-14', 'followup_overdue', '1', 'active', 'customer', '92', '10071', '云南昆明春城彩印包装厂 报价已发10天未回复', '报价单 Q-20260604-YN 发送于 2026-06-04，金额 ¥21000.00，已过10天未回复', '{\"quotation_id\": 49, \"quotation_no\": \"Q-20260604-YN\", \"avg_reply_days\": 3, \"days_since_send\": 10}', null, null, 'scanFollowupOverdue', '{\"threshold\": 7, \"avg_reply_days\": 3}', '/crm/quotation/detail?id=49', null, null, '2026-06-14', '1781422723');

-- ----------------------------
-- Table structure for tao_crm_knowledge
-- ----------------------------
DROP TABLE IF EXISTS `tao_crm_knowledge`;
CREATE TABLE `tao_crm_knowledge` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `store_id` int(11) DEFAULT NULL,
  `title` varchar(200) NOT NULL COMMENT '标题',
  `content` text COMMENT '内容',
  `category` varchar(50) DEFAULT '' COMMENT '分类: 产品知识/销售话术/竞品对比/成功案例/FAQ',
  `tags` varchar(200) DEFAULT '' COMMENT '标签，逗号分隔',
  `sort_order` int(11) DEFAULT '100',
  `is_delete` tinyint(1) DEFAULT '0',
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_store` (`store_id`),
  KEY `idx_category` (`category`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COMMENT='CRM知识库';

-- ----------------------------
-- Records of tao_crm_knowledge
-- ----------------------------
INSERT INTO `tao_crm_knowledge` VALUES ('20', '10001', 'UV胶印油墨产品知识', 'UV胶印油墨是一种在紫外线照射下固化的油墨，具有快速固化、高光泽度、耐磨损等优点。适用于纸张、塑料、金属等多种基材。', '产品知识', 'UV油墨,胶印,产品介绍', '1', '0', '1778817600', '1781409600');
INSERT INTO `tao_crm_knowledge` VALUES ('21', '10001', '客户常见异议处理话术', '1. 价格异议：强调品质差异和售后服务 2. 交期异议：说明生产周期，提供加急方案 3. 品质异议：先道歉，了解具体情况，提出解决方案', '销售话术', '话术,异议处理,销售技巧', '2', '0', '1778817600', '1781409600');
INSERT INTO `tao_crm_knowledge` VALUES ('22', '10001', '水性油墨 vs 溶剂型油墨对比', '水性油墨：环保无VOC排放，适合食品包装，干燥较慢，成本中等。溶剂型油墨：色彩鲜艳，干燥快，适合非食品包装。', '竞品对比', '水性油墨,溶剂型,对比', '3', '0', '1778817600', '1781409600');
INSERT INTO `tao_crm_knowledge` VALUES ('23', '10001', '华艺集团合作案例', '华艺集团是国内高端包装印刷龙头，与我司合作3年，年采购额约200万。合作模式：季度框架协议+月度订单。', '成功案例', '华艺,案例,大客户', '4', '0', '1778817600', '1781409600');

-- ----------------------------
-- Table structure for tao_crm_lead
-- ----------------------------
DROP TABLE IF EXISTS `tao_crm_lead`;
CREATE TABLE `tao_crm_lead` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '线索ID',
  `store_id` int(11) NOT NULL DEFAULT '0',
  `lead_name` varchar(200) NOT NULL DEFAULT '' COMMENT '线索名称',
  `contact_person` varchar(100) DEFAULT NULL COMMENT '联系人',
  `contact_phone` varchar(50) DEFAULT NULL COMMENT '联系人电话',
  `contact_position` varchar(100) DEFAULT NULL COMMENT '联系人职位',
  `company_name` varchar(200) DEFAULT NULL COMMENT '公司名称（可空）',
  `source` varchar(100) DEFAULT NULL COMMENT '线索来源',
  `industry` varchar(100) DEFAULT NULL COMMENT '行业',
  `requirement` text COMMENT '需求描述',
  `expected_amount` decimal(12,2) DEFAULT '0.00' COMMENT '预计金额',
  `priority` tinyint(1) DEFAULT '2' COMMENT '1=低 2=中 3=高',
  `status` tinyint(1) DEFAULT '1' COMMENT '1=新建 2=跟进中 3=已转客户 4=已关闭',
  `customer_id` int(11) DEFAULT NULL COMMENT '转为客户后的ID',
  `owner_user_id` int(11) DEFAULT NULL COMMENT '负责人',
  `next_follow_date` int(11) DEFAULT NULL COMMENT '下次跟进时间',
  `remark` text COMMENT '备注',
  `is_delete` tinyint(1) NOT NULL DEFAULT '0',
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_store_id` (`store_id`),
  KEY `idx_owner` (`owner_user_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COMMENT='CRM线索表';

-- ----------------------------
-- Records of tao_crm_lead
-- ----------------------------
INSERT INTO `tao_crm_lead` VALUES ('20', '10001', '杭州西湖印刷厂UV油墨需求', '刘总', '15800158001', null, '杭州西湖印刷厂', '官网咨询', '制造', '需要UV胶印油墨，月用量约500kg，要求环保认证', '50000.00', '3', '2', null, '10001', '1781582400', null, '0', '1780545600', '1781409600');
INSERT INTO `tao_crm_lead` VALUES ('21', '10001', '苏州工业园区印刷项目', '黄经理', '15900159002', null, '苏州工业园印刷有限公司', '展会', '制造', '新建生产线，需要全套油墨供应方案', '200000.00', '3', '1', null, '10001', '1781496000', null, '0', '1780545600', '1781409600');
INSERT INTO `tao_crm_lead` VALUES ('22', '10001', '厦门外贸印刷询盘', '林小姐', '15700157003', null, '厦门通达贸易有限公司', '阿里巴巴', '贸易', '出口东南亚，需要食品级油墨和UV油墨', '80000.00', '2', '1', null, '10068', '1781668800', null, '0', '1780545600', '1781409600');
INSERT INTO `tao_crm_lead` VALUES ('25', '10001', '常州天宁印刷UV油墨升级需求', '朱经理', '13905190025', null, '常州天宁印刷有限公司', '行业论坛', '制造', '现有UV油墨供应商品质不稳定，寻找替代', '35000.00', '3', '2', null, '10001', '1781755200', null, '0', '1780545600', '1781409600');
INSERT INTO `tao_crm_lead` VALUES ('26', '10001', '绍兴柯桥纺织印花项目', '吕总', '13805750026', null, '绍兴柯桥纺织印染厂', '展会', '制造', '纺织品印花油墨需求，需要耐水洗、耐摩擦', '120000.00', '3', '1', null, '10001', '1781582400', null, '0', '1780545600', '1781409600');
INSERT INTO `tao_crm_lead` VALUES ('27', '10001', '大连远洋印刷物资进口商', '冯经理', '13704110027', null, '大连远洋贸易有限公司', '阿里巴巴', '贸易', '出口韩国市场，需要全套油墨产品线', '250000.00', '3', '1', null, '10068', '1781668800', null, '0', '1780545600', '1781409600');
INSERT INTO `tao_crm_lead` VALUES ('28', '10001', '无锡新区印刷产业园', '顾主任', '13605100028', null, '无锡新区印刷产业园管理办', '政府推荐', '科技型', '园区引进印刷企业，需要供应商入库', '500000.00', '2', '1', null, '10001', '1782014400', null, '0', '1780545600', '1781409600');
INSERT INTO `tao_crm_lead` VALUES ('29', '10001', '东莞长安镇包装协会', '陈秘书长', '13507690029', null, '东莞长安包装行业协会', '行业活动', '贸易', '协会会员集体采购，需要优惠方案', '200000.00', '2', '2', null, '10068', '1781582400', null, '0', '1780545600', '1781409600');
INSERT INTO `tao_crm_lead` VALUES ('30', '10001', '温州龙港印刷城', '潘老板', '13405770030', null, '温州龙港印刷城', '转介绍', '制造', '小型印刷集群，批量小但客户多', '60000.00', '2', '1', null, '10071', '1781668800', null, '0', '1780545600', '1781409600');
INSERT INTO `tao_crm_lead` VALUES ('31', '10001', '芜湖经济开发区项目', '刘主任', '13305530031', null, '芜湖经济开发区管委会', '政府推荐', '科技型', '新建印刷产业园区，需要供应商目录', '300000.00', '2', '1', null, '10001', '1781928000', null, '0', '1780545600', '1781409600');
INSERT INTO `tao_crm_lead` VALUES ('32', '10001', '长春一汽包装配套项目', '于经理', '13204310032', null, '一汽集团包装材料部', '招投标', '制造', '汽车零部件包装印刷，要求TS16949认证', '180000.00', '3', '1', null, '10068', '1781841600', null, '0', '1780545600', '1781409600');
INSERT INTO `tao_crm_lead` VALUES ('33', '10001', '义乌小商品城印刷商户群', '吴姐', '13105790033', null, '义乌国际商贸城', '线上推广', '贸易', '小商品包装印刷，品种多、量不定', '40000.00', '1', '1', null, '10071', '1781755200', null, '0', '1780545600', '1781409600');
INSERT INTO `tao_crm_lead` VALUES ('34', '10001', '佛山顺德家电包装印刷', '梁总', '13007570034', null, '佛山顺德家电包装有限公司', '展会', '制造', '家电外壳印刷，对附着力要求高', '160000.00', '3', '2', null, '10001', '1781582400', null, '0', '1780545600', '1781409600');
INSERT INTO `tao_crm_lead` VALUES ('35', '10001', '连云港港口外贸包装', '孟经理', '13705180035', null, '连云港港口包装物流公司', '网络搜索', '贸易', '出口包装印刷，需求稳定但利润薄', '90000.00', '2', '1', null, '10071', '1781668800', null, '0', '1780545600', '1781409600');

-- ----------------------------
-- Table structure for tao_crm_notification
-- ----------------------------
DROP TABLE IF EXISTS `tao_crm_notification`;
CREATE TABLE `tao_crm_notification` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT '接收人',
  `type` varchar(50) NOT NULL COMMENT 'insight_alert/insight_digest/agent_action/approval',
  `title` varchar(200) NOT NULL,
  `content` varchar(500) DEFAULT NULL,
  `source_type` varchar(50) DEFAULT NULL COMMENT 'insight/agent/approval',
  `source_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `read_time` int(11) DEFAULT NULL,
  `action_url` varchar(200) DEFAULT NULL COMMENT '点击跳转前端路由',
  `create_time` int(11) NOT NULL,
  `store_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_read` (`user_id`,`is_read`,`create_time`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COMMENT='CRM通知';

-- ----------------------------
-- Records of tao_crm_notification
-- ----------------------------
INSERT INTO `tao_crm_notification` VALUES ('27', '10001', 'insight_alert', '广州恒达彩印有限公司 报价已发20天未回复', '报价单 Q-20260525-A1 发送于 2026-05-25，金额 ¥55100.00，已过20天未回复', 'insight', '103', '0', null, '/crm/quotation/detail?id=30', '1781422724', '10001');
INSERT INTO `tao_crm_notification` VALUES ('28', '10001', 'insight_alert', '4 个客户处于复购窗口状态', '汕头市金平彩印厂 已进入复购窗口期、辽宁沈阳东北印务集团 已进入复购窗口期、东莞联盛印刷科技有限公司 已进入复购窗口期、佛山南海彩艺印刷厂 已进入复购窗口期', 'insight', '0', '0', null, '/crm/dashboard', '1781422724', '10001');
INSERT INTO `tao_crm_notification` VALUES ('29', '10001', 'insight_alert', '2 个客户处于流失风险状态', '汕头市金平彩印厂 存在流失风险、辽宁沈阳东北印务集团 存在流失风险', 'insight', '0', '0', null, '/crm/dashboard', '1781422724', '10001');
INSERT INTO `tao_crm_notification` VALUES ('30', '10001', 'insight_alert', '2 个客户处于沉睡客户状态', '江门新会区永达印刷厂 已沉睡100天、辽宁沈阳东北印务集团 已沉睡110天', 'insight', '0', '0', null, '/crm/dashboard', '1781422724', '10001');
INSERT INTO `tao_crm_notification` VALUES ('31', '10068', 'insight_alert', '惠州博罗印刷包装公司 已进入复购窗口期', '距上次采购75天，历史平均采购间隔30天，上次采购金额 ¥23000.00', 'insight', '112', '0', null, '/crm/customer/detail?id=57', '1781422724', '10001');
INSERT INTO `tao_crm_notification` VALUES ('32', '10068', 'insight_alert', '惠州博罗印刷包装公司 存在流失风险', '距上次采购75天（历史间隔30天），近30天无跟进记录', 'insight', '113', '0', null, '/crm/customer/detail?id=57', '1781422724', '10001');
INSERT INTO `tao_crm_notification` VALUES ('33', '10068', 'insight_alert', '2 个客户处于公海预警状态', '甘肃兰州陇西印刷物资站 还剩0天将掉入公海、中山市伟业印刷材料公司 还剩6天将掉入公海', 'insight', '0', '0', null, '/crm/dashboard', '1781422724', '10001');
INSERT INTO `tao_crm_notification` VALUES ('34', '10068', 'insight_alert', '2 个客户处于报价未回复状态', '广西南宁绿城包装印刷厂 报价已发15天未回复、深圳鹏程包装有限公司 报价已发9天未回复', 'insight', '0', '0', null, '/crm/dashboard', '1781422724', '10001');
INSERT INTO `tao_crm_notification` VALUES ('35', '10071', 'insight_alert', '珠海横琴新材料有限公司 还剩3天将掉入公海', '最后跟进于 2025-12-19，距公海掉入还有3天（公海规则：180天未跟进自动掉入）', 'insight', '118', '0', null, '/crm/customer/detail?id=55', '1781422724', '10001');
INSERT INTO `tao_crm_notification` VALUES ('36', '10071', 'insight_alert', '肇庆端州印刷物资公司 已沉睡120天', '该客户曾有成交但超过120天未跟进，建议主动联系唤醒', 'insight', '119', '0', null, '/crm/customer/detail?id=59', '1781422724', '10001');
INSERT INTO `tao_crm_notification` VALUES ('37', '10071', 'insight_alert', '2 个客户处于报价未回复状态', '陕西西安秦川印刷物资公司 报价已发7天未回复、云南昆明春城彩印包装厂 报价已发10天未回复', 'insight', '0', '0', null, '/crm/dashboard', '1781422724', '10001');

-- ----------------------------
-- Table structure for tao_crm_order
-- ----------------------------
DROP TABLE IF EXISTS `tao_crm_order`;
CREATE TABLE `tao_crm_order` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '订单ID',
  `store_id` int(11) NOT NULL DEFAULT '0',
  `order_no` varchar(50) NOT NULL DEFAULT '' COMMENT '订单号 SO-YYYYMMDD-NNN',
  `customer_id` int(11) NOT NULL DEFAULT '0',
  `contact_id` int(11) DEFAULT NULL,
  `quotation_id` int(11) DEFAULT NULL COMMENT '来源报价单',
  `currency` varchar(10) DEFAULT 'CNY',
  `order_date` int(11) DEFAULT NULL,
  `delivery_date` int(11) DEFAULT NULL COMMENT '预计交付日期',
  `total_amount` decimal(12,2) DEFAULT '0.00',
  `discount_amount` decimal(12,2) DEFAULT '0.00',
  `final_amount` decimal(12,2) DEFAULT '0.00',
  `paid_amount` decimal(12,2) DEFAULT '0.00',
  `unpaid_amount` decimal(12,2) DEFAULT '0.00',
  `remark` text,
  `status` tinyint(1) DEFAULT '1' COMMENT '1=待确认 2=生产中 3=待发货 4=已发货 5=已完成 6=已取消',
  `payment_status` tinyint(1) DEFAULT '1' COMMENT '1=未付款 2=部分付款 3=已付清',
  `owner_user_id` int(11) DEFAULT NULL,
  `is_delete` tinyint(1) NOT NULL DEFAULT '0',
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_store_no` (`store_id`,`order_no`),
  KEY `idx_store_id` (`store_id`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_quotation_id` (`quotation_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=66 DEFAULT CHARSET=utf8mb4 COMMENT='CRM订单表';

-- ----------------------------
-- Records of tao_crm_order
-- ----------------------------
INSERT INTO `tao_crm_order` VALUES ('20', '10001', 'SO-20260509-B1-1', '52', null, null, 'CNY', '1774411200', null, '38000.00', '3000.00', '35000.00', '0.00', '0.00', null, '5', '3', '10001', '0', '1774411200', '1774411200');
INSERT INTO `tao_crm_order` VALUES ('21', '10001', 'SO-20260610-B1-2', '52', null, null, 'CNY', '1778299200', null, '45000.00', '3000.00', '42000.00', '0.00', '0.00', null, '5', '3', '10001', '0', '1778299200', '1778299200');
INSERT INTO `tao_crm_order` VALUES ('22', '10001', 'SO-20251220-B2-1', '53', null, null, 'CNY', '1766289600', null, '22000.00', '0.00', '22000.00', '0.00', '0.00', null, '5', '3', '10001', '0', '1766289600', '1766289600');
INSERT INTO `tao_crm_order` VALUES ('23', '10001', 'SO-20260218-B2-2', '53', null, null, 'CNY', '1771473600', null, '30000.00', '2000.00', '28000.00', '0.00', '0.00', null, '5', '3', '10001', '0', '1771473600', '1771473600');
INSERT INTO `tao_crm_order` VALUES ('24', '10001', 'SO-20260420-B2-3', '53', null, null, 'CNY', '1776657600', null, '48000.00', '3000.00', '45000.00', '0.00', '0.00', null, '5', '3', '10001', '0', '1776657600', '1776657600');
INSERT INTO `tao_crm_order` VALUES ('25', '10001', 'SO-20260311-D1-1', '56', null, null, 'CNY', '1773201600', null, '15000.00', '1000.00', '14000.00', '0.00', '0.00', null, '5', '3', '10001', '0', '1773201600', '1773201600');
INSERT INTO `tao_crm_order` VALUES ('26', '10001', 'SO-20260415-D1-2', '56', null, null, 'CNY', '1776225600', null, '20000.00', '0.00', '20000.00', '0.00', '0.00', null, '5', '3', '10001', '0', '1776225600', '1776225600');
INSERT INTO `tao_crm_order` VALUES ('27', '10001', 'SO-20260228-D2-1', '57', null, null, 'CNY', '1772337600', null, '18000.00', '1000.00', '17000.00', '0.00', '0.00', null, '5', '3', '10068', '0', '1772337600', '1772337600');
INSERT INTO `tao_crm_order` VALUES ('28', '10001', 'SO-20260401-D2-2', '57', null, null, 'CNY', '1774929600', null, '25000.00', '2000.00', '23000.00', '0.00', '0.00', null, '5', '3', '10068', '0', '1774929600', '1774929600');
INSERT INTO `tao_crm_order` VALUES ('29', '10001', 'SO-20260115-E1-1', '58', null, null, 'CNY', '1768449600', null, '12000.00', '500.00', '11500.00', '0.00', '0.00', null, '5', '3', '10001', '0', '1768449600', '1768449600');
INSERT INTO `tao_crm_order` VALUES ('30', '10001', 'SO-20260301-E2-1', '59', null, null, 'CNY', '1772337600', null, '8500.00', '0.00', '8500.00', '0.00', '0.00', null, '5', '3', '10071', '0', '1772337600', '1772337600');
INSERT INTO `tao_crm_order` VALUES ('31', '10001', 'SO-20260612-F1-1', '60', null, null, 'CNY', '1781236800', null, '80000.00', '5000.00', '75000.00', '0.00', '0.00', null, '4', '2', '10001', '0', '1781236800', '1781236800');
INSERT INTO `tao_crm_order` VALUES ('32', '10001', 'SO-20260608-F2-1', '61', null, null, 'CNY', '1780891200', null, '52000.00', '2000.00', '50000.00', '0.00', '0.00', null, '2', '1', '10001', '0', '1780891200', '1780891200');
INSERT INTO `tao_crm_order` VALUES ('33', '10001', 'SO-20260520-F3-1', '62', null, null, 'CNY', '1779249600', null, '15000.00', '0.00', '15000.00', '0.00', '0.00', null, '5', '3', '10068', '0', '1779249600', '1779249600');
INSERT INTO `tao_crm_order` VALUES ('34', '10001', 'SO-20260601-F4-1', '63', null, null, 'CNY', '1780286400', null, '28000.00', '3000.00', '25000.00', '0.00', '0.00', null, '3', '1', '10068', '0', '1780286400', '1780286400');
INSERT INTO `tao_crm_order` VALUES ('40', '10001', 'SO-20260301-TB-1', '78', null, null, 'CNY', '1772337600', null, '52000.00', '2000.00', '50000.00', '0.00', '0.00', null, '5', '3', '10001', '0', '1772337600', '1772337600');
INSERT INTO `tao_crm_order` VALUES ('41', '10001', 'SO-20260415-TB-2', '78', null, null, 'CNY', '1776225600', null, '68000.00', '3000.00', '65000.00', '0.00', '0.00', null, '5', '3', '10001', '0', '1776225600', '1776225600');
INSERT INTO `tao_crm_order` VALUES ('42', '10001', 'SO-20260520-TB-3', '78', null, null, 'CNY', '1779249600', null, '55000.00', '0.00', '55000.00', '0.00', '0.00', null, '5', '3', '10001', '0', '1779249600', '1779249600');
INSERT INTO `tao_crm_order` VALUES ('43', '10001', 'SO-20260420-JL-1', '79', null, null, 'CNY', '1776657600', null, '28000.00', '1000.00', '27000.00', '0.00', '0.00', null, '5', '3', '10001', '0', '1776657600', '1776657600');
INSERT INTO `tao_crm_order` VALUES ('44', '10001', 'SO-20260525-JL-2', '79', null, null, 'CNY', '1779681600', null, '32000.00', '0.00', '32000.00', '0.00', '0.00', null, '5', '3', '10001', '0', '1779681600', '1779681600');
INSERT INTO `tao_crm_order` VALUES ('45', '10001', 'SO-20251201-DB-1', '80', null, null, 'CNY', '1764993600', null, '45000.00', '3000.00', '42000.00', '0.00', '0.00', null, '5', '3', '10001', '0', '1764993600', '1764993600');
INSERT INTO `tao_crm_order` VALUES ('46', '10001', 'SO-20260130-DB-2', '80', null, null, 'CNY', '1770177600', null, '48000.00', '2000.00', '46000.00', '0.00', '0.00', null, '5', '3', '10001', '0', '1770177600', '1770177600');
INSERT INTO `tao_crm_order` VALUES ('47', '10001', 'SO-20260510-LH-1', '86', null, null, 'CNY', '1778385600', null, '95000.00', '5000.00', '90000.00', '0.00', '0.00', null, '5', '3', '10001', '0', '1778385600', '1778385600');
INSERT INTO `tao_crm_order` VALUES ('48', '10001', 'SO-20260528-LH-2', '86', null, null, 'CNY', '1779940800', null, '120000.00', '8000.00', '112000.00', '0.00', '0.00', null, '5', '3', '10001', '0', '1779940800', '1779940800');
INSERT INTO `tao_crm_order` VALUES ('49', '10001', 'SO-20260610-LH-3', '86', null, null, 'CNY', '1781064000', null, '108000.00', '5000.00', '103000.00', '0.00', '0.00', null, '4', '2', '10001', '0', '1781064000', '1781064000');
INSERT INTO `tao_crm_order` VALUES ('50', '10001', 'SO-20260601-GG-1', '87', null, null, 'CNY', '1780286400', null, '78000.00', '3000.00', '75000.00', '0.00', '0.00', null, '4', '2', '10001', '0', '1780286400', '1780286400');
INSERT INTO `tao_crm_order` VALUES ('51', '10001', 'SO-20260515-ZN-1', '72', null, null, 'CNY', '1778817600', null, '18000.00', '0.00', '18000.00', '0.00', '0.00', null, '5', '3', '10068', '0', '1778817600', '1778817600');
INSERT INTO `tao_crm_order` VALUES ('52', '10001', 'SO-20260605-JL-1', '73', null, null, 'CNY', '1780632000', null, '85000.00', '5000.00', '80000.00', '0.00', '0.00', null, '3', '1', '10001', '0', '1780632000', '1780632000');
INSERT INTO `tao_crm_order` VALUES ('53', '10001', 'SO-20260608-XY-1', '70', null, null, 'CNY', '1780891200', null, '25000.00', '0.00', '25000.00', '0.00', '0.00', null, '2', '1', '10001', '0', '1780891200', '1780891200');
INSERT INTO `tao_crm_order` VALUES ('54', '10001', 'SO-20260611-CS-1', '79', null, null, 'CNY', '1781150400', null, '15000.00', '0.00', '15000.00', '0.00', '0.00', null, '1', '1', '10001', '0', '1781150400', '1781150400');
INSERT INTO `tao_crm_order` VALUES ('55', '10001', 'SO-20260501-ZZ-1', '74', null, null, 'CNY', '1777608000', null, '22000.00', '1000.00', '21000.00', '0.00', '0.00', null, '6', '3', '10068', '0', '1777608000', '1777608000');
INSERT INTO `tao_crm_order` VALUES ('56', '10001', 'SO-20260530-QC-1', '76', null, null, 'CNY', '1780113600', null, '35000.00', '2000.00', '33000.00', '0.00', '0.00', null, '5', '3', '10071', '0', '1780113600', '1780113600');
INSERT INTO `tao_crm_order` VALUES ('57', '10001', 'SO-20260612-HS-1', '77', null, null, 'CNY', '1781236800', null, '12000.00', '0.00', '12000.00', '0.00', '0.00', null, '1', '1', '10001', '0', '1781236800', '1781236800');
INSERT INTO `tao_crm_order` VALUES ('58', '10001', 'SO-20260602-GX-1', '88', null, null, 'CNY', '1780372800', null, '16000.00', '0.00', '16000.00', '0.00', '0.00', null, '3', '1', '10068', '0', '1780372800', '1780372800');
INSERT INTO `tao_crm_order` VALUES ('59', '10001', 'SO-20260522-NC-1', '93', null, null, 'CNY', '1779422400', null, '28000.00', '1000.00', '27000.00', '0.00', '0.00', null, '5', '3', '10068', '0', '1779422400', '1779422400');
INSERT INTO `tao_crm_order` VALUES ('60', '10001', 'SO-20260607-YN-1', '92', null, null, 'CNY', '1780804800', null, '19000.00', '0.00', '19000.00', '0.00', '0.00', null, '2', '1', '10071', '0', '1780804800', '1780804800');
INSERT INTO `tao_crm_order` VALUES ('61', '10001', 'SO-20260518-SC-1', '75', null, null, 'CNY', '1779076800', null, '14000.00', '0.00', '14000.00', '0.00', '0.00', null, '5', '3', '10071', '0', '1779076800', '1779076800');
INSERT INTO `tao_crm_order` VALUES ('62', '10001', 'SO-20260603-HN-1', '94', null, null, 'CNY', '1780459200', null, '11000.00', '0.00', '11000.00', '0.00', '0.00', null, '4', '2', '10071', '0', '1780459200', '1780459200');
INSERT INTO `tao_crm_order` VALUES ('63', '10001', 'SO-20260525-NX-1', '95', null, null, 'CNY', '1779681600', null, '24000.00', '1000.00', '23000.00', '0.00', '0.00', null, '5', '3', '10001', '0', '1779681600', '1779681600');
INSERT INTO `tao_crm_order` VALUES ('64', '10001', 'SO-20260508-SX-1', '90', null, null, 'CNY', '1778212800', null, '13000.00', '0.00', '13000.00', '0.00', '0.00', null, '6', '1', '10001', '0', '1778212800', '1778212800');
INSERT INTO `tao_crm_order` VALUES ('65', '10001', 'SO-20260613-CQ-1', '89', null, null, 'CNY', '1781323200', null, '8500.00', '0.00', '8500.00', '0.00', '0.00', null, '1', '1', '10071', '0', '1781323200', '1781323200');

-- ----------------------------
-- Table structure for tao_crm_order_item
-- ----------------------------
DROP TABLE IF EXISTS `tao_crm_order_item`;
CREATE TABLE `tao_crm_order_item` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `store_id` int(11) NOT NULL DEFAULT '0',
  `order_id` int(11) NOT NULL DEFAULT '0',
  `product_id` int(11) DEFAULT NULL,
  `product_name` varchar(200) NOT NULL DEFAULT '',
  `specification` varchar(200) DEFAULT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `quantity` decimal(10,2) DEFAULT '0.00',
  `unit_price` decimal(10,2) DEFAULT '0.00',
  `amount` decimal(12,2) DEFAULT '0.00',
  `delivered_quantity` decimal(10,2) DEFAULT '0.00',
  `sort_order` int(11) DEFAULT '0',
  `is_delete` tinyint(1) NOT NULL DEFAULT '0',
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_store_id` (`store_id`),
  KEY `idx_order_id` (`order_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COMMENT='CRM订单明细表';

-- ----------------------------
-- Records of tao_crm_order_item
-- ----------------------------
INSERT INTO `tao_crm_order_item` VALUES ('1', '10001', '20', '10', 'UV胶印油墨 PRO', '25kg/桶', '桶', '200.00', '95.00', '19000.00', '0.00', '0', '0', '1774411200', '1774411200');
INSERT INTO `tao_crm_order_item` VALUES ('2', '10001', '20', '14', '环保稀释剂 快干型', '18L/桶', '桶', '150.00', '48.00', '7200.00', '0.00', '0', '0', '1774411200', '1774411200');
INSERT INTO `tao_crm_order_item` VALUES ('3', '10001', '20', '16', '洗车水 浓缩型', '25L/桶', '桶', '250.00', '35.00', '8750.00', '0.00', '0', '0', '1774411200', '1774411200');
INSERT INTO `tao_crm_order_item` VALUES ('4', '10001', '21', '10', 'UV胶印油墨 PRO', '25kg/桶', '桶', '250.00', '95.00', '23750.00', '0.00', '0', '0', '1778299200', '1778299200');
INSERT INTO `tao_crm_order_item` VALUES ('5', '10001', '21', '12', '水性光油 高光型', '20kg/桶', '桶', '180.00', '75.00', '13500.00', '0.00', '0', '0', '1778299200', '1778299200');
INSERT INTO `tao_crm_order_item` VALUES ('6', '10001', '31', '17', '溶剂型油墨 高浓度', '15kg/桶', '桶', '300.00', '135.00', '40500.00', '0.00', '0', '0', '1781236800', '1781236800');
INSERT INTO `tao_crm_order_item` VALUES ('7', '10001', '31', '19', 'UV LED固化油墨', '25kg/桶', '桶', '200.00', '128.00', '25600.00', '0.00', '0', '0', '1781236800', '1781236800');
INSERT INTO `tao_crm_order_item` VALUES ('8', '10001', '34', '20', '水性柔版油墨', '20kg/桶', '桶', '300.00', '58.00', '17400.00', '0.00', '0', '0', '1780286400', '1780286400');

-- ----------------------------
-- Table structure for tao_crm_product
-- ----------------------------
DROP TABLE IF EXISTS `tao_crm_product`;
CREATE TABLE `tao_crm_product` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '产品ID',
  `store_id` int(11) NOT NULL DEFAULT '0' COMMENT '租户ID',
  `product_name` varchar(200) NOT NULL DEFAULT '' COMMENT '产品名称',
  `product_code` varchar(50) DEFAULT NULL COMMENT '产品编码',
  `specification` varchar(200) DEFAULT NULL COMMENT '规格型号',
  `unit` varchar(50) DEFAULT NULL COMMENT '单位（kg/桶/套…）',
  `category` varchar(100) DEFAULT NULL COMMENT '产品分类',
  `category_id` int(11) DEFAULT NULL COMMENT '产品分类ID',
  `reference_price` decimal(12,2) DEFAULT '0.00' COMMENT '参考单价',
  `cost_price` decimal(12,2) DEFAULT '0.00' COMMENT '成本价',
  `image_url` varchar(500) DEFAULT NULL COMMENT '产品图片',
  `description` text COMMENT '产品描述',
  `status` tinyint(1) DEFAULT '1' COMMENT '1=上架 0=下架',
  `sort_order` int(11) DEFAULT '0' COMMENT '排序',
  `is_delete` tinyint(1) NOT NULL DEFAULT '0',
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_store_id` (`store_id`),
  KEY `idx_category` (`category`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COMMENT='CRM产品表';

-- ----------------------------
-- Records of tao_crm_product
-- ----------------------------
INSERT INTO `tao_crm_product` VALUES ('10', '10001', 'UV胶印油墨 PRO', 'UV-PRO-001', '25kg/桶', '桶', 'UV系列', null, '95.00', '72.00', null, null, '1', '1', '0', '1765857600', '1781409600');
INSERT INTO `tao_crm_product` VALUES ('11', '10001', 'UV胶印油墨 ECO', 'UV-ECO-002', '20kg/桶', '桶', 'UV系列', null, '68.00', '50.00', null, null, '1', '2', '0', '1765857600', '1781409600');
INSERT INTO `tao_crm_product` VALUES ('12', '10001', '水性光油 高光型', 'WB-GLOSS-001', '20kg/桶', '桶', '水性系列', null, '75.00', '55.00', null, null, '1', '3', '0', '1765857600', '1781409600');
INSERT INTO `tao_crm_product` VALUES ('13', '10001', '水性光油 哑光型', 'WB-MATT-002', '20kg/桶', '桶', '水性系列', null, '70.00', '52.00', null, null, '1', '4', '0', '1765857600', '1781409600');
INSERT INTO `tao_crm_product` VALUES ('14', '10001', '环保稀释剂 快干型', 'THIN-FAST-001', '18L/桶', '桶', '稀释剂', null, '48.00', '35.00', null, null, '1', '5', '0', '1765857600', '1781409600');
INSERT INTO `tao_crm_product` VALUES ('15', '10001', '环保稀释剂 慢干型', 'THIN-SLOW-002', '18L/桶', '桶', '稀释剂', null, '45.00', '33.00', null, null, '1', '6', '0', '1765857600', '1781409600');
INSERT INTO `tao_crm_product` VALUES ('16', '10001', '洗车水 浓缩型', 'WASH-CONC-001', '25L/桶', '桶', '辅助材料', null, '35.00', '22.00', null, null, '1', '7', '0', '1765857600', '1781409600');
INSERT INTO `tao_crm_product` VALUES ('17', '10001', '溶剂型油墨 高浓度', 'SOL-HC-001', '15kg/桶', '桶', '溶剂系列', null, '135.00', '98.00', null, null, '1', '8', '0', '1765857600', '1781409600');
INSERT INTO `tao_crm_product` VALUES ('18', '10001', '溶剂型油墨 标准型', 'SOL-STD-002', '15kg/桶', '桶', '溶剂系列', null, '110.00', '80.00', null, null, '1', '9', '0', '1765857600', '1781409600');
INSERT INTO `tao_crm_product` VALUES ('19', '10001', 'UV LED固化油墨', 'UV-LED-001', '25kg/桶', '桶', 'UV系列', null, '128.00', '95.00', null, null, '1', '10', '0', '1765857600', '1781409600');
INSERT INTO `tao_crm_product` VALUES ('20', '10001', '水性柔版油墨', 'WB-FLEXO-001', '20kg/桶', '桶', '水性系列', null, '58.00', '42.00', null, null, '1', '11', '0', '1765857600', '1781409600');
INSERT INTO `tao_crm_product` VALUES ('21', '10001', '金属油墨 金色', 'MET-GOLD-001', '10kg/桶', '桶', '特殊油墨', null, '280.00', '210.00', null, null, '1', '12', '0', '1765857600', '1781409600');
INSERT INTO `tao_crm_product` VALUES ('22', '10001', '金属油墨 银色', 'MET-SIL-002', '10kg/桶', '桶', '特殊油墨', null, '260.00', '195.00', null, null, '1', '13', '0', '1765857600', '1781409600');
INSERT INTO `tao_crm_product` VALUES ('23', '10001', '防伪油墨 紫外荧光', 'SEC-UV-001', '5kg/桶', '桶', '特殊油墨', null, '450.00', '340.00', null, null, '1', '14', '0', '1765857600', '1781409600');
INSERT INTO `tao_crm_product` VALUES ('24', '10001', '食品级油墨', 'FOOD-SAFE-001', '20kg/桶', '桶', '特殊油墨', null, '320.00', '245.00', null, null, '1', '15', '0', '1765857600', '1781409600');

-- ----------------------------
-- Table structure for tao_crm_projection
-- ----------------------------
DROP TABLE IF EXISTS `tao_crm_projection`;
CREATE TABLE `tao_crm_projection` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(50) NOT NULL COMMENT 'funnel_stats/user_performance/customer_health/product_performance/team_ranking/revenue_trend/kpi_dashboard',
  `key` varchar(100) NOT NULL COMMENT '维度键（如user:42或funnel:overall）',
  `data` json NOT NULL COMMENT '投影数据',
  `calculated_at` int(11) NOT NULL COMMENT '计算时间',
  `store_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_type_key` (`type`,`key`,`store_id`),
  KEY `idx_store` (`store_id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COMMENT='CRM预计算投影';

-- ----------------------------
-- Records of tao_crm_projection
-- ----------------------------
INSERT INTO `tao_crm_projection` VALUES ('7', 'funnel_stats', 'overall', '{\"total\": 43, \"stages\": {\"1\": {\"name\": \"初步接触\", \"count\": 13}, \"2\": {\"name\": \"需求确认\", \"count\": 14}, \"3\": {\"name\": \"报价\", \"count\": 6}, \"4\": {\"name\": \"谈判\", \"count\": 7}, \"5\": {\"name\": \"成交\", \"count\": 3}}}', '1781422723');
INSERT INTO `tao_crm_projection` VALUES ('8', 'user_performance', 'user:10001', '{\"month\": \"2026-06\", \"order_count\": 8, \"total_amount\": 435000}', '1781422723');
INSERT INTO `tao_crm_projection` VALUES ('9', 'user_performance', 'user:10068', '{\"month\": \"2026-06\", \"order_count\": 2, \"total_amount\": 41000}', '1781422723');
INSERT INTO `tao_crm_projection` VALUES ('10', 'user_performance', 'user:10071', '{\"month\": \"2026-06\", \"order_count\": 3, \"total_amount\": 38500}', '1781422723');
INSERT INTO `tao_crm_projection` VALUES ('11', 'customer_health', 'overall', '{\"优\": 23, \"差\": 16, \"新\": 1, \"良\": 3}', '1781422723');
INSERT INTO `tao_crm_projection` VALUES ('12', 'team_ranking', 'overall', '[{\"name\": \"系统管理员\", \"rank\": 1, \"month\": \"2026-06\", \"amount\": 435000, \"user_id\": 10001}, {\"name\": \"短视频助手\", \"rank\": 2, \"month\": \"2026-06\", \"amount\": 41000, \"user_id\": 10068}, {\"name\": \"xiechen\", \"rank\": 3, \"month\": \"2026-06\", \"amount\": 38500, \"user_id\": 10071}]', '1781422723');
INSERT INTO `tao_crm_projection` VALUES ('13', 'revenue_trend', 'overall', '[{\"month\": \"2025-07\", \"amount\": 0}, {\"month\": \"2025-08\", \"amount\": 0}, {\"month\": \"2025-09\", \"amount\": 0}, {\"month\": \"2025-10\", \"amount\": 0}, {\"month\": \"2025-11\", \"amount\": 0}, {\"month\": \"2025-12\", \"amount\": 64000}, {\"month\": \"2026-01\", \"amount\": 11500}, {\"month\": \"2026-02\", \"amount\": 74000}, {\"month\": \"2026-03\", \"amount\": 147500}, {\"month\": \"2026-04\", \"amount\": 157000}, {\"month\": \"2026-05\", \"amount\": 495000}, {\"month\": \"2026-06\", \"amount\": 514500}]', '1781422723');
INSERT INTO `tao_crm_projection` VALUES ('14', 'kpi_dashboard', 'overall', '{\"month\": \"2026-06\", \"revenue\": 514500, \"order_count\": 13, \"new_customers\": 1, \"last_month_revenue\": 495000}', '1781422723');

-- ----------------------------
-- Table structure for tao_crm_quotation
-- ----------------------------
DROP TABLE IF EXISTS `tao_crm_quotation`;
CREATE TABLE `tao_crm_quotation` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '报价单ID',
  `store_id` int(11) NOT NULL DEFAULT '0' COMMENT '租户ID',
  `quotation_no` varchar(50) NOT NULL DEFAULT '' COMMENT '报价单号 Q-YYYYMMDD-NNN',
  `customer_id` int(11) NOT NULL DEFAULT '0' COMMENT '客户ID',
  `contact_id` int(11) DEFAULT NULL COMMENT '联系人ID',
  `currency` varchar(10) DEFAULT 'CNY' COMMENT '币种',
  `quotation_date` int(11) DEFAULT NULL COMMENT '报价日期',
  `valid_days` int(11) DEFAULT '30' COMMENT '有效天数',
  `total_amount` decimal(12,2) DEFAULT '0.00' COMMENT '合计金额',
  `discount_rate` decimal(5,2) DEFAULT '0.00' COMMENT '折扣率',
  `discount_amount` decimal(12,2) DEFAULT '0.00' COMMENT '折扣金额',
  `final_amount` decimal(12,2) DEFAULT '0.00' COMMENT '折后金额',
  `tax_rate` decimal(5,2) DEFAULT '0.00' COMMENT '增值税率(%)',
  `tax_amount` decimal(12,2) DEFAULT '0.00' COMMENT '增值税额',
  `cn_amount_text` varchar(100) DEFAULT '' COMMENT '大写金额',
  `remark` text COMMENT '备注',
  `status` tinyint(1) DEFAULT '1' COMMENT '1=草稿 2=已发送 3=已确认 4=已拒绝 5=已转订单',
  `order_id` int(11) DEFAULT NULL COMMENT '关联订单ID',
  `owner_user_id` int(11) DEFAULT NULL COMMENT '负责人',
  `is_delete` tinyint(1) NOT NULL DEFAULT '0',
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_store_no` (`store_id`,`quotation_no`),
  KEY `idx_store_id` (`store_id`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_status` (`status`),
  KEY `idx_owner_user` (`owner_user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=utf8mb4 COMMENT='CRM报价单表';

-- ----------------------------
-- Records of tao_crm_quotation
-- ----------------------------
INSERT INTO `tao_crm_quotation` VALUES ('30', '10001', 'Q-20260525-A1', '50', null, 'CNY', '1779681600', '30', '58000.00', '5.00', '2900.00', '55100.00', '0.00', '0.00', '', null, '2', null, '10001', '0', '1779681600', '1779681600');
INSERT INTO `tao_crm_quotation` VALUES ('31', '10001', 'Q-20260605-A2', '51', null, 'CNY', '1780632000', '30', '32000.00', '0.00', '0.00', '32000.00', '0.00', '0.00', '', null, '2', null, '10068', '0', '1780632000', '1780632000');
INSERT INTO `tao_crm_quotation` VALUES ('32', '10001', 'Q-20260610-F1', '60', null, 'CNY', '1781064000', '30', '85000.00', '8.00', '6800.00', '78200.00', '0.00', '0.00', '', null, '3', null, '10001', '0', '1780804800', '1780804800');
INSERT INTO `tao_crm_quotation` VALUES ('33', '10001', 'Q-20260612-F2', '61', null, 'CNY', '1781236800', '30', '55000.00', '3.00', '1650.00', '53350.00', '0.00', '0.00', '', null, '1', null, '10001', '0', '1781236800', '1781236800');
INSERT INTO `tao_crm_quotation` VALUES ('34', '10001', 'Q-20260613-F3', '63', null, 'CNY', '1781323200', '30', '30000.00', '2.00', '600.00', '29400.00', '0.00', '0.00', '', null, '2', null, '10068', '0', '1781323200', '1781323200');
INSERT INTO `tao_crm_quotation` VALUES ('35', '10001', 'Q-20260301-HIST', '52', null, 'CNY', '1772337600', '30', '40000.00', '5.00', '2000.00', '38000.00', '0.00', '0.00', '', null, '3', null, '10001', '0', '1772510400', '1772510400');
INSERT INTO `tao_crm_quotation` VALUES ('36', '10001', 'Q-20260401-HIST', '56', null, 'CNY', '1775016000', '30', '18000.00', '0.00', '0.00', '18000.00', '0.00', '0.00', '', null, '3', null, '10001', '0', '1775102400', '1775102400');
INSERT INTO `tao_crm_quotation` VALUES ('40', '10001', 'Q-20260601-LH', '86', null, 'CNY', '1780286400', '30', '105000.00', '5.00', '5250.00', '99750.00', '0.00', '0.00', '', null, '3', null, '10001', '0', '1780372800', '1780372800');
INSERT INTO `tao_crm_quotation` VALUES ('41', '10001', 'Q-20260608-XY', '70', null, 'CNY', '1780891200', '30', '28000.00', '3.00', '840.00', '27160.00', '0.00', '0.00', '', null, '2', null, '10001', '0', '1780891200', '1780891200');
INSERT INTO `tao_crm_quotation` VALUES ('42', '10001', 'Q-20260610-CS', '79', null, 'CNY', '1781064000', '30', '16000.00', '0.00', '0.00', '16000.00', '0.00', '0.00', '', null, '1', null, '10001', '0', '1781064000', '1781064000');
INSERT INTO `tao_crm_quotation` VALUES ('43', '10001', 'Q-20260515-ZZ', '74', null, 'CNY', '1778817600', '30', '24000.00', '2.00', '480.00', '23520.00', '0.00', '0.00', '', null, '4', null, '10068', '0', '1778904000', '1778904000');
INSERT INTO `tao_crm_quotation` VALUES ('44', '10001', 'Q-20260605-GG', '87', null, 'CNY', '1780632000', '30', '82000.00', '8.00', '6560.00', '75440.00', '0.00', '0.00', '', null, '3', null, '10001', '0', '1780718400', '1780718400');
INSERT INTO `tao_crm_quotation` VALUES ('45', '10001', 'Q-20260609-TB', '78', null, 'CNY', '1780977600', '30', '58000.00', '5.00', '2900.00', '55100.00', '0.00', '0.00', '', null, '2', null, '10001', '0', '1780977600', '1780977600');
INSERT INTO `tao_crm_quotation` VALUES ('46', '10001', 'Q-20260603-SC', '75', null, 'CNY', '1780459200', '30', '16000.00', '0.00', '0.00', '16000.00', '0.00', '0.00', '', null, '3', null, '10071', '0', '1780545600', '1780545600');
INSERT INTO `tao_crm_quotation` VALUES ('47', '10001', 'Q-20260607-QC', '76', null, 'CNY', '1780804800', '30', '38000.00', '3.00', '1140.00', '36860.00', '0.00', '0.00', '', null, '2', null, '10071', '0', '1780804800', '1780804800');
INSERT INTO `tao_crm_quotation` VALUES ('48', '10001', 'Q-20260611-NC', '93', null, 'CNY', '1781150400', '30', '30000.00', '2.00', '600.00', '29400.00', '0.00', '0.00', '', null, '1', null, '10068', '0', '1781150400', '1781150400');
INSERT INTO `tao_crm_quotation` VALUES ('49', '10001', 'Q-20260604-YN', '92', null, 'CNY', '1780545600', '30', '21000.00', '0.00', '0.00', '21000.00', '0.00', '0.00', '', null, '2', null, '10071', '0', '1780545600', '1780545600');
INSERT INTO `tao_crm_quotation` VALUES ('50', '10001', 'Q-20260530-GX', '88', null, 'CNY', '1780113600', '30', '18000.00', '0.00', '0.00', '18000.00', '0.00', '0.00', '', null, '2', null, '10068', '0', '1780113600', '1780113600');
INSERT INTO `tao_crm_quotation` VALUES ('51', '10001', 'Q-20260518-HS', '77', null, 'CNY', '1779076800', '30', '14000.00', '0.00', '0.00', '14000.00', '0.00', '0.00', '', null, '4', null, '10001', '0', '1779163200', '1779163200');
INSERT INTO `tao_crm_quotation` VALUES ('52', '10001', 'Q-20260520-JL', '73', null, 'CNY', '1779249600', '30', '90000.00', '5.00', '4500.00', '85500.00', '0.00', '0.00', '', null, '5', null, '10001', '0', '1779508800', '1779508800');
INSERT INTO `tao_crm_quotation` VALUES ('53', '10001', 'Q-20260525-NX', '95', null, 'CNY', '1779681600', '30', '26000.00', '2.00', '520.00', '25480.00', '0.00', '0.00', '', null, '3', null, '10001', '0', '1779768000', '1779768000');
INSERT INTO `tao_crm_quotation` VALUES ('54', '10001', 'Q-20260612-HN', '94', null, 'CNY', '1781236800', '30', '13000.00', '0.00', '0.00', '13000.00', '0.00', '0.00', '', null, '2', null, '10071', '0', '1781236800', '1781236800');
INSERT INTO `tao_crm_quotation` VALUES ('55', '10001', 'Q-20260613-SX', '90', null, 'CNY', '1781323200', '30', '15000.00', '0.00', '0.00', '15000.00', '0.00', '0.00', '', null, '1', null, '10001', '0', '1781323200', '1781323200');
INSERT INTO `tao_crm_quotation` VALUES ('56', '10001', 'Q-20260602-JL-HIST', '79', null, 'CNY', '1780372800', '30', '30000.00', '3.00', '900.00', '29100.00', '0.00', '0.00', '', null, '5', null, '10001', '0', '1780459200', '1780459200');
INSERT INTO `tao_crm_quotation` VALUES ('57', '10001', 'Q-20260401-TB-HIST', '78', null, 'CNY', '1775016000', '30', '50000.00', '5.00', '2500.00', '47500.00', '0.00', '0.00', '', null, '3', null, '10001', '0', '1775102400', '1775102400');
INSERT INTO `tao_crm_quotation` VALUES ('58', '10001', 'Q-20260501-ZN', '72', null, 'CNY', '1777608000', '30', '19000.00', '0.00', '0.00', '19000.00', '0.00', '0.00', '', null, '3', null, '10068', '0', '1777694400', '1777694400');
INSERT INTO `tao_crm_quotation` VALUES ('59', '10001', 'Q-20260614-CQ', '89', null, 'CNY', '1781409600', '30', '10000.00', '0.00', '0.00', '10000.00', '0.00', '0.00', '', null, '1', null, '10071', '0', '1781409600', '1781409600');
INSERT INTO `tao_crm_quotation` VALUES ('60', '10001', 'Q-20260401-DB-HIST', '80', null, 'CNY', '1775102400', '30', '43000.00', '2.00', '860.00', '42140.00', '0.00', '0.00', '', null, '3', null, '10001', '0', '1775188800', '1775188800');

-- ----------------------------
-- Table structure for tao_crm_quotation_item
-- ----------------------------
DROP TABLE IF EXISTS `tao_crm_quotation_item`;
CREATE TABLE `tao_crm_quotation_item` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '明细ID',
  `store_id` int(11) NOT NULL DEFAULT '0' COMMENT '租户ID',
  `quotation_id` int(11) NOT NULL DEFAULT '0' COMMENT '报价单ID',
  `product_id` int(11) DEFAULT NULL COMMENT '关联产品ID',
  `product_name` varchar(200) NOT NULL DEFAULT '' COMMENT '产品名称',
  `specification` varchar(200) DEFAULT NULL COMMENT '规格型号',
  `unit` varchar(50) DEFAULT NULL COMMENT '单位',
  `quantity` decimal(10,2) DEFAULT '0.00' COMMENT '数量',
  `unit_price` decimal(10,2) DEFAULT '0.00' COMMENT '单价',
  `amount` decimal(12,2) DEFAULT '0.00' COMMENT '小计',
  `sort_order` int(11) DEFAULT '0' COMMENT '排序',
  `is_delete` tinyint(1) NOT NULL DEFAULT '0',
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_store_id` (`store_id`),
  KEY `idx_quotation_id` (`quotation_id`),
  KEY `idx_product_id` (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COMMENT='CRM报价明细表';

-- ----------------------------
-- Records of tao_crm_quotation_item
-- ----------------------------
INSERT INTO `tao_crm_quotation_item` VALUES ('9', '10001', '40', '10', 'UV胶印油墨 PRO', '25kg/桶', '桶', '500.00', '95.00', '47500.00', '1', '0', '1780545600', '1781409600');
INSERT INTO `tao_crm_quotation_item` VALUES ('10', '10001', '40', '19', 'UV LED固化油墨', '25kg/桶', '桶', '300.00', '128.00', '38400.00', '2', '0', '1780545600', '1781409600');
INSERT INTO `tao_crm_quotation_item` VALUES ('11', '10001', '40', '24', '食品级油墨', '20kg/桶', '桶', '80.00', '320.00', '25600.00', '3', '0', '1780545600', '1781409600');
INSERT INTO `tao_crm_quotation_item` VALUES ('12', '10001', '44', '19', 'UV LED固化油墨', '25kg/桶', '桶', '400.00', '128.00', '51200.00', '1', '0', '1780545600', '1781409600');
INSERT INTO `tao_crm_quotation_item` VALUES ('13', '10001', '44', '12', '水性光油 高光型', '20kg/桶', '桶', '300.00', '75.00', '22500.00', '2', '0', '1780545600', '1781409600');
INSERT INTO `tao_crm_quotation_item` VALUES ('14', '10001', '52', '17', '溶剂型油墨 高浓度', '15kg/桶', '桶', '400.00', '135.00', '54000.00', '1', '0', '1780545600', '1781409600');
INSERT INTO `tao_crm_quotation_item` VALUES ('15', '10001', '52', '14', '环保稀释剂 快干型', '18L/桶', '桶', '500.00', '48.00', '24000.00', '2', '0', '1780545600', '1781409600');
INSERT INTO `tao_crm_quotation_item` VALUES ('16', '10001', '41', '10', 'UV胶印油墨 PRO', '25kg/桶', '桶', '200.00', '95.00', '19000.00', '1', '0', '1780545600', '1781409600');
INSERT INTO `tao_crm_quotation_item` VALUES ('17', '10001', '49', '20', '水性柔版油墨', '20kg/桶', '桶', '250.00', '58.00', '14500.00', '1', '0', '1780545600', '1781409600');
INSERT INTO `tao_crm_quotation_item` VALUES ('18', '10001', '47', '21', '金属油墨 金色', '10kg/桶', '桶', '80.00', '280.00', '22400.00', '1', '0', '1780545600', '1781409600');
INSERT INTO `tao_crm_quotation_item` VALUES ('19', '10001', '53', '11', 'UV胶印油墨 ECO', '20kg/桶', '桶', '250.00', '68.00', '17000.00', '1', '0', '1780545600', '1781409600');
INSERT INTO `tao_crm_quotation_item` VALUES ('20', '10001', '58', '12', '水性光油 高光型', '20kg/桶', '桶', '200.00', '75.00', '15000.00', '1', '0', '1780545600', '1781409600');
INSERT INTO `tao_crm_quotation_item` VALUES ('21', '10001', '60', '14', '环保稀释剂 快干型', '18L/桶', '桶', '500.00', '48.00', '24000.00', '1', '0', '1780545600', '1781409600');
INSERT INTO `tao_crm_quotation_item` VALUES ('22', '10001', '50', '20', '水性柔版油墨', '20kg/桶', '桶', '200.00', '58.00', '11600.00', '1', '0', '1780545600', '1781409600');
INSERT INTO `tao_crm_quotation_item` VALUES ('23', '10001', '45', '17', '溶剂型油墨 高浓度', '15kg/桶', '桶', '300.00', '135.00', '40500.00', '1', '0', '1780545600', '1781409600');

-- ----------------------------
-- Table structure for tao_invoice_company
-- ----------------------------
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
  UNIQUE KEY `uniq_store_tax` (`store_id`,`tax_number`),
  KEY `idx_store_id` (`store_id`),
  KEY `idx_tax_number` (`tax_number`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COMMENT='销方公司档案';

-- ----------------------------
-- Records of tao_invoice_company
-- ----------------------------

-- ----------------------------
-- Table structure for tao_invoice_company_member
-- ----------------------------
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
  UNIQUE KEY `uniq_company_member` (`company_id`,`member_id`),
  KEY `idx_company_id` (`company_id`),
  KEY `idx_member_id` (`member_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COMMENT='公司会员绑定';

-- ----------------------------
-- Records of tao_invoice_company_member
-- ----------------------------

-- ----------------------------
-- Table structure for tao_invoice_record
-- ----------------------------
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
  `tax_rate` decimal(10,4) DEFAULT '0.0000' COMMENT '税率',
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
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COMMENT='代开票申请记录';

-- ----------------------------
-- Records of tao_invoice_record
-- ----------------------------

-- ----------------------------
-- Table structure for tao_member_message
-- ----------------------------
DROP TABLE IF EXISTS `tao_member_message`;
CREATE TABLE `tao_member_message` (
  `message_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `store_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商城ID',
  `apply_no` varchar(32) NOT NULL DEFAULT '' COMMENT '申请编号',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `service_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '申请的服务ID',
  `service_type` varchar(30) NOT NULL DEFAULT '' COMMENT '服务类型',
  `company_name` varchar(100) NOT NULL DEFAULT '' COMMENT '企业名称',
  `contact_name` varchar(30) NOT NULL DEFAULT '' COMMENT '联系人姓名',
  `contact_phone` varchar(20) NOT NULL DEFAULT '' COMMENT '联系电话',
  `email` varchar(50) DEFAULT '' COMMENT '邮箱地址',
  `taxpayer_type` varchar(30) DEFAULT '' COMMENT '纳税人类型',
  `tax_number` varchar(20) DEFAULT '' COMMENT '税号',
  `declaration_period` varchar(20) DEFAULT '' COMMENT '申报周期',
  `expected_date` date DEFAULT NULL COMMENT '期望完成时间',
  `requirements` text COMMENT '详细需求描述',
  `attachments` text COMMENT '附件信息 JSON格式',
  `apply_status` tinyint(3) unsigned NOT NULL DEFAULT '10' COMMENT '申请状态 (10待处理 20处理中 30已完成 40已取消)',
  `process_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '处理时间',
  `processor_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '处理人ID',
  `remark` varchar(500) DEFAULT '' COMMENT '备注',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`message_id`),
  KEY `idx_store_id` (`store_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_service_id` (`service_id`),
  KEY `idx_apply_status` (`apply_status`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COMMENT='会员服务申请表';

-- ----------------------------
-- Records of tao_member_message
-- ----------------------------

-- ----------------------------
-- Table structure for tao_member_setting
-- ----------------------------
DROP TABLE IF EXISTS `tao_member_setting`;
CREATE TABLE `tao_member_setting` (
  `setting_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `store_id` int(11) NOT NULL DEFAULT '0' COMMENT '商城ID',
  `is_open` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否开启会员功能 0关闭 1开启',
  `renewal_remind` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否开启续费提醒 0关闭 1开启',
  `renewal_remind_days` int(11) NOT NULL DEFAULT '7' COMMENT '续费提醒天数(到期前N天)',
  `rights` text COMMENT '会员权益配置 JSON格式',
  `auto_upgrade` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否自动升级 0关闭 1开启',
  `upgrade_rules` text COMMENT '自动升级规则 JSON格式',
  `sms_remind` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否短信提醒 0关闭 1开启',
  `expire_enabled` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否开启到期提醒 0关闭 1开启',
  `expire_times` text COMMENT '到期提醒时间 JSON数组 格式: ["7","15","30"]',
  `expire_methods` text COMMENT '到期提醒方式 JSON数组 格式: ["system","sms","email"]',
  `expire_template_system` text COMMENT '到期提醒系统消息模板',
  `expire_template_sms` text COMMENT '到期提醒短信模板',
  `expire_template_email` text COMMENT '到期提醒邮件模板',
  `renewal_enabled` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否开启续费提醒 0关闭 1开启',
  `renewal_frequency` varchar(20) NOT NULL DEFAULT 'weekly' COMMENT '续费提醒频率 daily weekly monthly custom',
  `renewal_count` int(11) NOT NULL DEFAULT '3' COMMENT '续费提醒次数',
  `renewal_methods` text COMMENT '续费提醒方式 JSON数组 格式: ["system","sms","email"]',
  `renewal_template_system` text COMMENT '续费提醒系统消息模板',
  `renewal_template_sms` text COMMENT '续费提醒短信模板',
  `renewal_template_email` text COMMENT '续费提醒邮件模板',
  `expire_template` varchar(255) DEFAULT NULL COMMENT '到期提醒模板ID',
  `renewal_template` varchar(255) DEFAULT NULL COMMENT '续费提醒模板ID',
  `sms_template_before` varchar(255) DEFAULT NULL COMMENT '到期前短信模板ID',
  `sms_template_after` varchar(255) DEFAULT NULL COMMENT '到期后短信模板ID',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`setting_id`),
  UNIQUE KEY `idx_store_id` (`store_id`) USING BTREE,
  KEY `idx_store_id_open` (`store_id`,`is_open`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COMMENT='会员设置表';

-- ----------------------------
-- Records of tao_member_setting
-- ----------------------------
INSERT INTO `tao_member_setting` VALUES ('1', '10001', '1', '1', '7', '[{\"type\":\"discount\",\"title\":\"1212\",\"description\":\"1212\",\"icon\":\"\",\"enabled\":true,\"discountType\":\"percent\",\"discountValue\":10,\"pointsMultiple\":1}]', '0', '[{\"from_service_id\":1,\"to_service_id\":2,\"condition\":\"points\",\"value\":1,\"enabled\":true}]', '0', '1', '[\"7\",\"15\",\"30\",\"3\",\"1\"]', '[\"system\",\"sms\",\"email\"]', '您的会员将于{days}天后到期，请及时续费。', '【商城提醒】您的会员将于{days}天后到期，请及时续费。', '您的会员将于{days}天后到期，请及时续费。', '1', 'monthly', '3', '[\"system\"]', '您的会员已到期，请及时续费。', '【商城提醒】您的会员已到期，请及时续费。', '您的会员已到期，请及时续费。', null, null, null, null, '1763863494', '1764485052');

-- ----------------------------
-- Table structure for tao_member_user
-- ----------------------------
DROP TABLE IF EXISTS `tao_member_user`;
CREATE TABLE `tao_member_user` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `store_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商城ID',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `service_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '会员服务ID',
  `expire_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '到期时间',
  `start_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '开始时间',
  `total_days` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '总天数',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '状态 0过期 1有效 2暂停',
  `is_auto_renew` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否自动续费 0否 1是',
  `renew_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '续费次数',
  `upgrade_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '升级次数',
  `total_amount` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '累计消费金额',
  `last_renew_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '最后续费时间',
  `remark` varchar(500) NOT NULL DEFAULT '' COMMENT '备注信息',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_store_user` (`store_id`,`user_id`),
  KEY `idx_store_id` (`store_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_service_id` (`service_id`),
  KEY `idx_status` (`status`),
  KEY `idx_expire_time` (`expire_time`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COMMENT='会员用户表';

-- ----------------------------
-- Records of tao_member_user
-- ----------------------------

-- ----------------------------
-- Table structure for tao_platform_user
-- ----------------------------
DROP TABLE IF EXISTS `tao_platform_user`;
CREATE TABLE `tao_platform_user` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `mobile` varchar(20) NOT NULL DEFAULT '' COMMENT '主手机号',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_mobile` (`mobile`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='平台用户表（跨店铺唯一身份）';

-- ----------------------------
-- Records of tao_platform_user
-- ----------------------------

-- ----------------------------
-- Table structure for tao_platform_user_store
-- ----------------------------
DROP TABLE IF EXISTS `tao_platform_user_store`;
CREATE TABLE `tao_platform_user_store` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `platform_user_id` int(10) unsigned NOT NULL COMMENT '平台用户ID',
  `store_id` int(10) unsigned NOT NULL COMMENT '店铺ID',
  `store_user_id` int(10) unsigned NOT NULL COMMENT '对应 yoshop_user.user_id',
  `is_primary` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否主店铺',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_platform_store` (`platform_user_id`,`store_id`),
  KEY `idx_store_user` (`store_id`,`store_user_id`),
  KEY `idx_platform_user` (`platform_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='平台用户-店铺映射表';

-- ----------------------------
-- Records of tao_platform_user_store
-- ----------------------------

-- ----------------------------
-- Table structure for tao_region
-- ----------------------------
DROP TABLE IF EXISTS `tao_region`;
CREATE TABLE `tao_region` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '区划信息ID',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '区划名称',
  `pid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '父级ID',
  `code` varchar(255) NOT NULL DEFAULT '' COMMENT '区划编码',
  `level` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '层级(1省级 2市级 3区/县级)',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=3930 DEFAULT CHARSET=utf8 COMMENT='省市区数据表';

-- ----------------------------
-- Records of tao_region
-- ----------------------------
INSERT INTO `tao_region` VALUES ('1', '北京市', '0', '110000', '1');
INSERT INTO `tao_region` VALUES ('2', '北京市', '1', '110100', '2');
INSERT INTO `tao_region` VALUES ('3', '东城区', '2', '110101', '3');
INSERT INTO `tao_region` VALUES ('4', '西城区', '2', '110102', '3');
INSERT INTO `tao_region` VALUES ('5', '朝阳区', '2', '110105', '3');
INSERT INTO `tao_region` VALUES ('6', '丰台区', '2', '110106', '3');
INSERT INTO `tao_region` VALUES ('7', '石景山区', '2', '110107', '3');
INSERT INTO `tao_region` VALUES ('8', '海淀区', '2', '110108', '3');
INSERT INTO `tao_region` VALUES ('9', '门头沟区', '2', '110109', '3');
INSERT INTO `tao_region` VALUES ('10', '房山区', '2', '110111', '3');
INSERT INTO `tao_region` VALUES ('11', '通州区', '2', '110112', '3');
INSERT INTO `tao_region` VALUES ('12', '顺义区', '2', '110113', '3');
INSERT INTO `tao_region` VALUES ('13', '昌平区', '2', '110114', '3');
INSERT INTO `tao_region` VALUES ('14', '大兴区', '2', '110115', '3');
INSERT INTO `tao_region` VALUES ('15', '怀柔区', '2', '110116', '3');
INSERT INTO `tao_region` VALUES ('16', '平谷区', '2', '110117', '3');
INSERT INTO `tao_region` VALUES ('17', '密云区', '2', '110118', '3');
INSERT INTO `tao_region` VALUES ('18', '延庆区', '2', '110119', '3');
INSERT INTO `tao_region` VALUES ('19', '天津市', '0', '120000', '1');
INSERT INTO `tao_region` VALUES ('20', '天津市', '19', '120100', '2');
INSERT INTO `tao_region` VALUES ('21', '和平区', '20', '120101', '3');
INSERT INTO `tao_region` VALUES ('22', '河东区', '20', '120102', '3');
INSERT INTO `tao_region` VALUES ('23', '河西区', '20', '120103', '3');
INSERT INTO `tao_region` VALUES ('24', '南开区', '20', '120104', '3');
INSERT INTO `tao_region` VALUES ('25', '河北区', '20', '120105', '3');
INSERT INTO `tao_region` VALUES ('26', '红桥区', '20', '120106', '3');
INSERT INTO `tao_region` VALUES ('27', '东丽区', '20', '120110', '3');
INSERT INTO `tao_region` VALUES ('28', '西青区', '20', '120111', '3');
INSERT INTO `tao_region` VALUES ('29', '津南区', '20', '120112', '3');
INSERT INTO `tao_region` VALUES ('30', '北辰区', '20', '120113', '3');
INSERT INTO `tao_region` VALUES ('31', '武清区', '20', '120114', '3');
INSERT INTO `tao_region` VALUES ('32', '宝坻区', '20', '120115', '3');
INSERT INTO `tao_region` VALUES ('33', '滨海新区', '20', '120116', '3');
INSERT INTO `tao_region` VALUES ('34', '宁河区', '20', '120117', '3');
INSERT INTO `tao_region` VALUES ('35', '静海区', '20', '120118', '3');
INSERT INTO `tao_region` VALUES ('36', '蓟州区', '20', '120119', '3');
INSERT INTO `tao_region` VALUES ('37', '河北省', '0', '130000', '1');
INSERT INTO `tao_region` VALUES ('38', '石家庄市', '37', '130100', '2');
INSERT INTO `tao_region` VALUES ('39', '长安区', '38', '130102', '3');
INSERT INTO `tao_region` VALUES ('40', '桥西区', '38', '130104', '3');
INSERT INTO `tao_region` VALUES ('41', '新华区', '38', '130105', '3');
INSERT INTO `tao_region` VALUES ('42', '井陉矿区', '38', '130107', '3');
INSERT INTO `tao_region` VALUES ('43', '裕华区', '38', '130108', '3');
INSERT INTO `tao_region` VALUES ('44', '藁城区', '38', '130109', '3');
INSERT INTO `tao_region` VALUES ('45', '鹿泉区', '38', '130110', '3');
INSERT INTO `tao_region` VALUES ('46', '栾城区', '38', '130111', '3');
INSERT INTO `tao_region` VALUES ('47', '井陉县', '38', '130121', '3');
INSERT INTO `tao_region` VALUES ('48', '正定县', '38', '130123', '3');
INSERT INTO `tao_region` VALUES ('49', '行唐县', '38', '130125', '3');
INSERT INTO `tao_region` VALUES ('50', '灵寿县', '38', '130126', '3');
INSERT INTO `tao_region` VALUES ('51', '高邑县', '38', '130127', '3');
INSERT INTO `tao_region` VALUES ('52', '深泽县', '38', '130128', '3');
INSERT INTO `tao_region` VALUES ('53', '赞皇县', '38', '130129', '3');
INSERT INTO `tao_region` VALUES ('54', '无极县', '38', '130130', '3');
INSERT INTO `tao_region` VALUES ('55', '平山县', '38', '130131', '3');
INSERT INTO `tao_region` VALUES ('56', '元氏县', '38', '130132', '3');
INSERT INTO `tao_region` VALUES ('57', '赵县', '38', '130133', '3');
INSERT INTO `tao_region` VALUES ('58', '辛集市', '38', '130181', '3');
INSERT INTO `tao_region` VALUES ('59', '晋州市', '38', '130183', '3');
INSERT INTO `tao_region` VALUES ('60', '新乐市', '38', '130184', '3');
INSERT INTO `tao_region` VALUES ('61', '唐山市', '37', '130200', '2');
INSERT INTO `tao_region` VALUES ('62', '路南区', '61', '130202', '3');
INSERT INTO `tao_region` VALUES ('63', '路北区', '61', '130203', '3');
INSERT INTO `tao_region` VALUES ('64', '古冶区', '61', '130204', '3');
INSERT INTO `tao_region` VALUES ('65', '开平区', '61', '130205', '3');
INSERT INTO `tao_region` VALUES ('66', '丰南区', '61', '130207', '3');
INSERT INTO `tao_region` VALUES ('67', '丰润区', '61', '130208', '3');
INSERT INTO `tao_region` VALUES ('68', '曹妃甸区', '61', '130209', '3');
INSERT INTO `tao_region` VALUES ('69', '滦南县', '61', '130224', '3');
INSERT INTO `tao_region` VALUES ('70', '乐亭县', '61', '130225', '3');
INSERT INTO `tao_region` VALUES ('71', '迁西县', '61', '130227', '3');
INSERT INTO `tao_region` VALUES ('72', '玉田县', '61', '130229', '3');
INSERT INTO `tao_region` VALUES ('73', '遵化市', '61', '130281', '3');
INSERT INTO `tao_region` VALUES ('74', '迁安市', '61', '130283', '3');
INSERT INTO `tao_region` VALUES ('75', '滦州市', '61', '130284', '3');
INSERT INTO `tao_region` VALUES ('76', '秦皇岛市', '37', '130300', '2');
INSERT INTO `tao_region` VALUES ('77', '海港区', '76', '130302', '3');
INSERT INTO `tao_region` VALUES ('78', '山海关区', '76', '130303', '3');
INSERT INTO `tao_region` VALUES ('79', '北戴河区', '76', '130304', '3');
INSERT INTO `tao_region` VALUES ('80', '抚宁区', '76', '130306', '3');
INSERT INTO `tao_region` VALUES ('81', '青龙满族自治县', '76', '130321', '3');
INSERT INTO `tao_region` VALUES ('82', '昌黎县', '76', '130322', '3');
INSERT INTO `tao_region` VALUES ('83', '卢龙县', '76', '130324', '3');
INSERT INTO `tao_region` VALUES ('84', '邯郸市', '37', '130400', '2');
INSERT INTO `tao_region` VALUES ('85', '邯山区', '84', '130402', '3');
INSERT INTO `tao_region` VALUES ('86', '丛台区', '84', '130403', '3');
INSERT INTO `tao_region` VALUES ('87', '复兴区', '84', '130404', '3');
INSERT INTO `tao_region` VALUES ('88', '峰峰矿区', '84', '130406', '3');
INSERT INTO `tao_region` VALUES ('89', '肥乡区', '84', '130407', '3');
INSERT INTO `tao_region` VALUES ('90', '永年区', '84', '130408', '3');
INSERT INTO `tao_region` VALUES ('91', '临漳县', '84', '130423', '3');
INSERT INTO `tao_region` VALUES ('92', '成安县', '84', '130424', '3');
INSERT INTO `tao_region` VALUES ('93', '大名县', '84', '130425', '3');
INSERT INTO `tao_region` VALUES ('94', '涉县', '84', '130426', '3');
INSERT INTO `tao_region` VALUES ('95', '磁县', '84', '130427', '3');
INSERT INTO `tao_region` VALUES ('96', '邱县', '84', '130430', '3');
INSERT INTO `tao_region` VALUES ('97', '鸡泽县', '84', '130431', '3');
INSERT INTO `tao_region` VALUES ('98', '广平县', '84', '130432', '3');
INSERT INTO `tao_region` VALUES ('99', '馆陶县', '84', '130433', '3');
INSERT INTO `tao_region` VALUES ('100', '魏县', '84', '130434', '3');
INSERT INTO `tao_region` VALUES ('101', '曲周县', '84', '130435', '3');
INSERT INTO `tao_region` VALUES ('102', '武安市', '84', '130481', '3');
INSERT INTO `tao_region` VALUES ('103', '邢台市', '37', '130500', '2');
INSERT INTO `tao_region` VALUES ('107', '临城县', '103', '130522', '3');
INSERT INTO `tao_region` VALUES ('108', '内丘县', '103', '130523', '3');
INSERT INTO `tao_region` VALUES ('109', '柏乡县', '103', '130524', '3');
INSERT INTO `tao_region` VALUES ('110', '隆尧县', '103', '130525', '3');
INSERT INTO `tao_region` VALUES ('113', '宁晋县', '103', '130528', '3');
INSERT INTO `tao_region` VALUES ('114', '巨鹿县', '103', '130529', '3');
INSERT INTO `tao_region` VALUES ('115', '新河县', '103', '130530', '3');
INSERT INTO `tao_region` VALUES ('116', '广宗县', '103', '130531', '3');
INSERT INTO `tao_region` VALUES ('117', '平乡县', '103', '130532', '3');
INSERT INTO `tao_region` VALUES ('118', '威县', '103', '130533', '3');
INSERT INTO `tao_region` VALUES ('119', '清河县', '103', '130534', '3');
INSERT INTO `tao_region` VALUES ('120', '临西县', '103', '130535', '3');
INSERT INTO `tao_region` VALUES ('121', '南宫市', '103', '130581', '3');
INSERT INTO `tao_region` VALUES ('122', '沙河市', '103', '130582', '3');
INSERT INTO `tao_region` VALUES ('123', '保定市', '37', '130600', '2');
INSERT INTO `tao_region` VALUES ('124', '竞秀区', '123', '130602', '3');
INSERT INTO `tao_region` VALUES ('125', '莲池区', '123', '130606', '3');
INSERT INTO `tao_region` VALUES ('126', '满城区', '123', '130607', '3');
INSERT INTO `tao_region` VALUES ('127', '清苑区', '123', '130608', '3');
INSERT INTO `tao_region` VALUES ('128', '徐水区', '123', '130609', '3');
INSERT INTO `tao_region` VALUES ('129', '涞水县', '123', '130623', '3');
INSERT INTO `tao_region` VALUES ('130', '阜平县', '123', '130624', '3');
INSERT INTO `tao_region` VALUES ('131', '定兴县', '123', '130626', '3');
INSERT INTO `tao_region` VALUES ('132', '唐县', '123', '130627', '3');
INSERT INTO `tao_region` VALUES ('133', '高阳县', '123', '130628', '3');
INSERT INTO `tao_region` VALUES ('134', '容城县', '123', '130629', '3');
INSERT INTO `tao_region` VALUES ('135', '涞源县', '123', '130630', '3');
INSERT INTO `tao_region` VALUES ('136', '望都县', '123', '130631', '3');
INSERT INTO `tao_region` VALUES ('137', '安新县', '123', '130632', '3');
INSERT INTO `tao_region` VALUES ('138', '易县', '123', '130633', '3');
INSERT INTO `tao_region` VALUES ('139', '曲阳县', '123', '130634', '3');
INSERT INTO `tao_region` VALUES ('140', '蠡县', '123', '130635', '3');
INSERT INTO `tao_region` VALUES ('141', '顺平县', '123', '130636', '3');
INSERT INTO `tao_region` VALUES ('142', '博野县', '123', '130637', '3');
INSERT INTO `tao_region` VALUES ('143', '雄县', '123', '130638', '3');
INSERT INTO `tao_region` VALUES ('144', '涿州市', '123', '130681', '3');
INSERT INTO `tao_region` VALUES ('145', '定州市', '123', '130682', '3');
INSERT INTO `tao_region` VALUES ('146', '安国市', '123', '130683', '3');
INSERT INTO `tao_region` VALUES ('147', '高碑店市', '123', '130684', '3');
INSERT INTO `tao_region` VALUES ('148', '张家口市', '37', '130700', '2');
INSERT INTO `tao_region` VALUES ('149', '桥东区', '148', '130702', '3');
INSERT INTO `tao_region` VALUES ('150', '桥西区', '148', '130703', '3');
INSERT INTO `tao_region` VALUES ('151', '宣化区', '148', '130705', '3');
INSERT INTO `tao_region` VALUES ('152', '下花园区', '148', '130706', '3');
INSERT INTO `tao_region` VALUES ('153', '万全区', '148', '130708', '3');
INSERT INTO `tao_region` VALUES ('154', '崇礼区', '148', '130709', '3');
INSERT INTO `tao_region` VALUES ('155', '张北县', '148', '130722', '3');
INSERT INTO `tao_region` VALUES ('156', '康保县', '148', '130723', '3');
INSERT INTO `tao_region` VALUES ('157', '沽源县', '148', '130724', '3');
INSERT INTO `tao_region` VALUES ('158', '尚义县', '148', '130725', '3');
INSERT INTO `tao_region` VALUES ('159', '蔚县', '148', '130726', '3');
INSERT INTO `tao_region` VALUES ('160', '阳原县', '148', '130727', '3');
INSERT INTO `tao_region` VALUES ('161', '怀安县', '148', '130728', '3');
INSERT INTO `tao_region` VALUES ('162', '怀来县', '148', '130730', '3');
INSERT INTO `tao_region` VALUES ('163', '涿鹿县', '148', '130731', '3');
INSERT INTO `tao_region` VALUES ('164', '赤城县', '148', '130732', '3');
INSERT INTO `tao_region` VALUES ('165', '承德市', '37', '130800', '2');
INSERT INTO `tao_region` VALUES ('166', '双桥区', '165', '130802', '3');
INSERT INTO `tao_region` VALUES ('167', '双滦区', '165', '130803', '3');
INSERT INTO `tao_region` VALUES ('168', '鹰手营子矿区', '165', '130804', '3');
INSERT INTO `tao_region` VALUES ('169', '承德县', '165', '130821', '3');
INSERT INTO `tao_region` VALUES ('170', '兴隆县', '165', '130822', '3');
INSERT INTO `tao_region` VALUES ('171', '滦平县', '165', '130824', '3');
INSERT INTO `tao_region` VALUES ('172', '隆化县', '165', '130825', '3');
INSERT INTO `tao_region` VALUES ('173', '丰宁满族自治县', '165', '130826', '3');
INSERT INTO `tao_region` VALUES ('174', '宽城满族自治县', '165', '130827', '3');
INSERT INTO `tao_region` VALUES ('175', '围场满族蒙古族自治县', '165', '130828', '3');
INSERT INTO `tao_region` VALUES ('176', '平泉市', '165', '130881', '3');
INSERT INTO `tao_region` VALUES ('177', '沧州市', '37', '130900', '2');
INSERT INTO `tao_region` VALUES ('178', '新华区', '177', '130902', '3');
INSERT INTO `tao_region` VALUES ('179', '运河区', '177', '130903', '3');
INSERT INTO `tao_region` VALUES ('180', '沧县', '177', '130921', '3');
INSERT INTO `tao_region` VALUES ('181', '青县', '177', '130922', '3');
INSERT INTO `tao_region` VALUES ('182', '东光县', '177', '130923', '3');
INSERT INTO `tao_region` VALUES ('183', '海兴县', '177', '130924', '3');
INSERT INTO `tao_region` VALUES ('184', '盐山县', '177', '130925', '3');
INSERT INTO `tao_region` VALUES ('185', '肃宁县', '177', '130926', '3');
INSERT INTO `tao_region` VALUES ('186', '南皮县', '177', '130927', '3');
INSERT INTO `tao_region` VALUES ('187', '吴桥县', '177', '130928', '3');
INSERT INTO `tao_region` VALUES ('188', '献县', '177', '130929', '3');
INSERT INTO `tao_region` VALUES ('189', '孟村回族自治县', '177', '130930', '3');
INSERT INTO `tao_region` VALUES ('190', '泊头市', '177', '130981', '3');
INSERT INTO `tao_region` VALUES ('191', '任丘市', '177', '130982', '3');
INSERT INTO `tao_region` VALUES ('192', '黄骅市', '177', '130983', '3');
INSERT INTO `tao_region` VALUES ('193', '河间市', '177', '130984', '3');
INSERT INTO `tao_region` VALUES ('194', '廊坊市', '37', '131000', '2');
INSERT INTO `tao_region` VALUES ('195', '安次区', '194', '131002', '3');
INSERT INTO `tao_region` VALUES ('196', '广阳区', '194', '131003', '3');
INSERT INTO `tao_region` VALUES ('197', '固安县', '194', '131022', '3');
INSERT INTO `tao_region` VALUES ('198', '永清县', '194', '131023', '3');
INSERT INTO `tao_region` VALUES ('199', '香河县', '194', '131024', '3');
INSERT INTO `tao_region` VALUES ('200', '大城县', '194', '131025', '3');
INSERT INTO `tao_region` VALUES ('201', '文安县', '194', '131026', '3');
INSERT INTO `tao_region` VALUES ('202', '大厂回族自治县', '194', '131028', '3');
INSERT INTO `tao_region` VALUES ('203', '霸州市', '194', '131081', '3');
INSERT INTO `tao_region` VALUES ('204', '三河市', '194', '131082', '3');
INSERT INTO `tao_region` VALUES ('205', '衡水市', '37', '131100', '2');
INSERT INTO `tao_region` VALUES ('206', '桃城区', '205', '131102', '3');
INSERT INTO `tao_region` VALUES ('207', '冀州区', '205', '131103', '3');
INSERT INTO `tao_region` VALUES ('208', '枣强县', '205', '131121', '3');
INSERT INTO `tao_region` VALUES ('209', '武邑县', '205', '131122', '3');
INSERT INTO `tao_region` VALUES ('210', '武强县', '205', '131123', '3');
INSERT INTO `tao_region` VALUES ('211', '饶阳县', '205', '131124', '3');
INSERT INTO `tao_region` VALUES ('212', '安平县', '205', '131125', '3');
INSERT INTO `tao_region` VALUES ('213', '故城县', '205', '131126', '3');
INSERT INTO `tao_region` VALUES ('214', '景县', '205', '131127', '3');
INSERT INTO `tao_region` VALUES ('215', '阜城县', '205', '131128', '3');
INSERT INTO `tao_region` VALUES ('216', '深州市', '205', '131182', '3');
INSERT INTO `tao_region` VALUES ('217', '山西省', '0', '140000', '1');
INSERT INTO `tao_region` VALUES ('218', '太原市', '217', '140100', '2');
INSERT INTO `tao_region` VALUES ('219', '小店区', '218', '140105', '3');
INSERT INTO `tao_region` VALUES ('220', '迎泽区', '218', '140106', '3');
INSERT INTO `tao_region` VALUES ('221', '杏花岭区', '218', '140107', '3');
INSERT INTO `tao_region` VALUES ('222', '尖草坪区', '218', '140108', '3');
INSERT INTO `tao_region` VALUES ('223', '万柏林区', '218', '140109', '3');
INSERT INTO `tao_region` VALUES ('224', '晋源区', '218', '140110', '3');
INSERT INTO `tao_region` VALUES ('225', '清徐县', '218', '140121', '3');
INSERT INTO `tao_region` VALUES ('226', '阳曲县', '218', '140122', '3');
INSERT INTO `tao_region` VALUES ('227', '娄烦县', '218', '140123', '3');
INSERT INTO `tao_region` VALUES ('228', '古交市', '218', '140181', '3');
INSERT INTO `tao_region` VALUES ('229', '大同市', '217', '140200', '2');
INSERT INTO `tao_region` VALUES ('230', '新荣区', '229', '140212', '3');
INSERT INTO `tao_region` VALUES ('231', '平城区', '229', '140213', '3');
INSERT INTO `tao_region` VALUES ('232', '云冈区', '229', '140214', '3');
INSERT INTO `tao_region` VALUES ('233', '云州区', '229', '140215', '3');
INSERT INTO `tao_region` VALUES ('234', '阳高县', '229', '140221', '3');
INSERT INTO `tao_region` VALUES ('235', '天镇县', '229', '140222', '3');
INSERT INTO `tao_region` VALUES ('236', '广灵县', '229', '140223', '3');
INSERT INTO `tao_region` VALUES ('237', '灵丘县', '229', '140224', '3');
INSERT INTO `tao_region` VALUES ('238', '浑源县', '229', '140225', '3');
INSERT INTO `tao_region` VALUES ('239', '左云县', '229', '140226', '3');
INSERT INTO `tao_region` VALUES ('240', '阳泉市', '217', '140300', '2');
INSERT INTO `tao_region` VALUES ('241', '城区', '240', '140302', '3');
INSERT INTO `tao_region` VALUES ('242', '矿区', '240', '140303', '3');
INSERT INTO `tao_region` VALUES ('243', '郊区', '240', '140311', '3');
INSERT INTO `tao_region` VALUES ('244', '平定县', '240', '140321', '3');
INSERT INTO `tao_region` VALUES ('245', '盂县', '240', '140322', '3');
INSERT INTO `tao_region` VALUES ('246', '长治市', '217', '140400', '2');
INSERT INTO `tao_region` VALUES ('247', '潞州区', '246', '140403', '3');
INSERT INTO `tao_region` VALUES ('248', '上党区', '246', '140404', '3');
INSERT INTO `tao_region` VALUES ('249', '屯留区', '246', '140405', '3');
INSERT INTO `tao_region` VALUES ('250', '潞城区', '246', '140406', '3');
INSERT INTO `tao_region` VALUES ('251', '襄垣县', '246', '140423', '3');
INSERT INTO `tao_region` VALUES ('252', '平顺县', '246', '140425', '3');
INSERT INTO `tao_region` VALUES ('253', '黎城县', '246', '140426', '3');
INSERT INTO `tao_region` VALUES ('254', '壶关县', '246', '140427', '3');
INSERT INTO `tao_region` VALUES ('255', '长子县', '246', '140428', '3');
INSERT INTO `tao_region` VALUES ('256', '武乡县', '246', '140429', '3');
INSERT INTO `tao_region` VALUES ('257', '沁县', '246', '140430', '3');
INSERT INTO `tao_region` VALUES ('258', '沁源县', '246', '140431', '3');
INSERT INTO `tao_region` VALUES ('259', '晋城市', '217', '140500', '2');
INSERT INTO `tao_region` VALUES ('260', '城区', '259', '140502', '3');
INSERT INTO `tao_region` VALUES ('261', '沁水县', '259', '140521', '3');
INSERT INTO `tao_region` VALUES ('262', '阳城县', '259', '140522', '3');
INSERT INTO `tao_region` VALUES ('263', '陵川县', '259', '140524', '3');
INSERT INTO `tao_region` VALUES ('264', '泽州县', '259', '140525', '3');
INSERT INTO `tao_region` VALUES ('265', '高平市', '259', '140581', '3');
INSERT INTO `tao_region` VALUES ('266', '朔州市', '217', '140600', '2');
INSERT INTO `tao_region` VALUES ('267', '朔城区', '266', '140602', '3');
INSERT INTO `tao_region` VALUES ('268', '平鲁区', '266', '140603', '3');
INSERT INTO `tao_region` VALUES ('269', '山阴县', '266', '140621', '3');
INSERT INTO `tao_region` VALUES ('270', '应县', '266', '140622', '3');
INSERT INTO `tao_region` VALUES ('271', '右玉县', '266', '140623', '3');
INSERT INTO `tao_region` VALUES ('272', '怀仁市', '266', '140681', '3');
INSERT INTO `tao_region` VALUES ('273', '晋中市', '217', '140700', '2');
INSERT INTO `tao_region` VALUES ('274', '榆次区', '273', '140702', '3');
INSERT INTO `tao_region` VALUES ('275', '太谷区', '273', '140703', '3');
INSERT INTO `tao_region` VALUES ('276', '榆社县', '273', '140721', '3');
INSERT INTO `tao_region` VALUES ('277', '左权县', '273', '140722', '3');
INSERT INTO `tao_region` VALUES ('278', '和顺县', '273', '140723', '3');
INSERT INTO `tao_region` VALUES ('279', '昔阳县', '273', '140724', '3');
INSERT INTO `tao_region` VALUES ('280', '寿阳县', '273', '140725', '3');
INSERT INTO `tao_region` VALUES ('281', '祁县', '273', '140727', '3');
INSERT INTO `tao_region` VALUES ('282', '平遥县', '273', '140728', '3');
INSERT INTO `tao_region` VALUES ('283', '灵石县', '273', '140729', '3');
INSERT INTO `tao_region` VALUES ('284', '介休市', '273', '140781', '3');
INSERT INTO `tao_region` VALUES ('285', '运城市', '217', '140800', '2');
INSERT INTO `tao_region` VALUES ('286', '盐湖区', '285', '140802', '3');
INSERT INTO `tao_region` VALUES ('287', '临猗县', '285', '140821', '3');
INSERT INTO `tao_region` VALUES ('288', '万荣县', '285', '140822', '3');
INSERT INTO `tao_region` VALUES ('289', '闻喜县', '285', '140823', '3');
INSERT INTO `tao_region` VALUES ('290', '稷山县', '285', '140824', '3');
INSERT INTO `tao_region` VALUES ('291', '新绛县', '285', '140825', '3');
INSERT INTO `tao_region` VALUES ('292', '绛县', '285', '140826', '3');
INSERT INTO `tao_region` VALUES ('293', '垣曲县', '285', '140827', '3');
INSERT INTO `tao_region` VALUES ('294', '夏县', '285', '140828', '3');
INSERT INTO `tao_region` VALUES ('295', '平陆县', '285', '140829', '3');
INSERT INTO `tao_region` VALUES ('296', '芮城县', '285', '140830', '3');
INSERT INTO `tao_region` VALUES ('297', '永济市', '285', '140881', '3');
INSERT INTO `tao_region` VALUES ('298', '河津市', '285', '140882', '3');
INSERT INTO `tao_region` VALUES ('299', '忻州市', '217', '140900', '2');
INSERT INTO `tao_region` VALUES ('300', '忻府区', '299', '140902', '3');
INSERT INTO `tao_region` VALUES ('301', '定襄县', '299', '140921', '3');
INSERT INTO `tao_region` VALUES ('302', '五台县', '299', '140922', '3');
INSERT INTO `tao_region` VALUES ('303', '代县', '299', '140923', '3');
INSERT INTO `tao_region` VALUES ('304', '繁峙县', '299', '140924', '3');
INSERT INTO `tao_region` VALUES ('305', '宁武县', '299', '140925', '3');
INSERT INTO `tao_region` VALUES ('306', '静乐县', '299', '140926', '3');
INSERT INTO `tao_region` VALUES ('307', '神池县', '299', '140927', '3');
INSERT INTO `tao_region` VALUES ('308', '五寨县', '299', '140928', '3');
INSERT INTO `tao_region` VALUES ('309', '岢岚县', '299', '140929', '3');
INSERT INTO `tao_region` VALUES ('310', '河曲县', '299', '140930', '3');
INSERT INTO `tao_region` VALUES ('311', '保德县', '299', '140931', '3');
INSERT INTO `tao_region` VALUES ('312', '偏关县', '299', '140932', '3');
INSERT INTO `tao_region` VALUES ('313', '原平市', '299', '140981', '3');
INSERT INTO `tao_region` VALUES ('314', '临汾市', '217', '141000', '2');
INSERT INTO `tao_region` VALUES ('315', '尧都区', '314', '141002', '3');
INSERT INTO `tao_region` VALUES ('316', '曲沃县', '314', '141021', '3');
INSERT INTO `tao_region` VALUES ('317', '翼城县', '314', '141022', '3');
INSERT INTO `tao_region` VALUES ('318', '襄汾县', '314', '141023', '3');
INSERT INTO `tao_region` VALUES ('319', '洪洞县', '314', '141024', '3');
INSERT INTO `tao_region` VALUES ('320', '古县', '314', '141025', '3');
INSERT INTO `tao_region` VALUES ('321', '安泽县', '314', '141026', '3');
INSERT INTO `tao_region` VALUES ('322', '浮山县', '314', '141027', '3');
INSERT INTO `tao_region` VALUES ('323', '吉县', '314', '141028', '3');
INSERT INTO `tao_region` VALUES ('324', '乡宁县', '314', '141029', '3');
INSERT INTO `tao_region` VALUES ('325', '大宁县', '314', '141030', '3');
INSERT INTO `tao_region` VALUES ('326', '隰县', '314', '141031', '3');
INSERT INTO `tao_region` VALUES ('327', '永和县', '314', '141032', '3');
INSERT INTO `tao_region` VALUES ('328', '蒲县', '314', '141033', '3');
INSERT INTO `tao_region` VALUES ('329', '汾西县', '314', '141034', '3');
INSERT INTO `tao_region` VALUES ('330', '侯马市', '314', '141081', '3');
INSERT INTO `tao_region` VALUES ('331', '霍州市', '314', '141082', '3');
INSERT INTO `tao_region` VALUES ('332', '吕梁市', '217', '141100', '2');
INSERT INTO `tao_region` VALUES ('333', '离石区', '332', '141102', '3');
INSERT INTO `tao_region` VALUES ('334', '文水县', '332', '141121', '3');
INSERT INTO `tao_region` VALUES ('335', '交城县', '332', '141122', '3');
INSERT INTO `tao_region` VALUES ('336', '兴县', '332', '141123', '3');
INSERT INTO `tao_region` VALUES ('337', '临县', '332', '141124', '3');
INSERT INTO `tao_region` VALUES ('338', '柳林县', '332', '141125', '3');
INSERT INTO `tao_region` VALUES ('339', '石楼县', '332', '141126', '3');
INSERT INTO `tao_region` VALUES ('340', '岚县', '332', '141127', '3');
INSERT INTO `tao_region` VALUES ('341', '方山县', '332', '141128', '3');
INSERT INTO `tao_region` VALUES ('342', '中阳县', '332', '141129', '3');
INSERT INTO `tao_region` VALUES ('343', '交口县', '332', '141130', '3');
INSERT INTO `tao_region` VALUES ('344', '孝义市', '332', '141181', '3');
INSERT INTO `tao_region` VALUES ('345', '汾阳市', '332', '141182', '3');
INSERT INTO `tao_region` VALUES ('346', '内蒙古自治区', '0', '150000', '1');
INSERT INTO `tao_region` VALUES ('347', '呼和浩特市', '346', '150100', '2');
INSERT INTO `tao_region` VALUES ('348', '新城区', '347', '150102', '3');
INSERT INTO `tao_region` VALUES ('349', '回民区', '347', '150103', '3');
INSERT INTO `tao_region` VALUES ('350', '玉泉区', '347', '150104', '3');
INSERT INTO `tao_region` VALUES ('351', '赛罕区', '347', '150105', '3');
INSERT INTO `tao_region` VALUES ('352', '土默特左旗', '347', '150121', '3');
INSERT INTO `tao_region` VALUES ('353', '托克托县', '347', '150122', '3');
INSERT INTO `tao_region` VALUES ('354', '和林格尔县', '347', '150123', '3');
INSERT INTO `tao_region` VALUES ('355', '清水河县', '347', '150124', '3');
INSERT INTO `tao_region` VALUES ('356', '武川县', '347', '150125', '3');
INSERT INTO `tao_region` VALUES ('357', '包头市', '346', '150200', '2');
INSERT INTO `tao_region` VALUES ('358', '东河区', '357', '150202', '3');
INSERT INTO `tao_region` VALUES ('359', '昆都仑区', '357', '150203', '3');
INSERT INTO `tao_region` VALUES ('360', '青山区', '357', '150204', '3');
INSERT INTO `tao_region` VALUES ('361', '石拐区', '357', '150205', '3');
INSERT INTO `tao_region` VALUES ('362', '白云鄂博矿区', '357', '150206', '3');
INSERT INTO `tao_region` VALUES ('363', '九原区', '357', '150207', '3');
INSERT INTO `tao_region` VALUES ('364', '土默特右旗', '357', '150221', '3');
INSERT INTO `tao_region` VALUES ('365', '固阳县', '357', '150222', '3');
INSERT INTO `tao_region` VALUES ('366', '达尔罕茂明安联合旗', '357', '150223', '3');
INSERT INTO `tao_region` VALUES ('367', '乌海市', '346', '150300', '2');
INSERT INTO `tao_region` VALUES ('368', '海勃湾区', '367', '150302', '3');
INSERT INTO `tao_region` VALUES ('369', '海南区', '367', '150303', '3');
INSERT INTO `tao_region` VALUES ('370', '乌达区', '367', '150304', '3');
INSERT INTO `tao_region` VALUES ('371', '赤峰市', '346', '150400', '2');
INSERT INTO `tao_region` VALUES ('372', '红山区', '371', '150402', '3');
INSERT INTO `tao_region` VALUES ('373', '元宝山区', '371', '150403', '3');
INSERT INTO `tao_region` VALUES ('374', '松山区', '371', '150404', '3');
INSERT INTO `tao_region` VALUES ('375', '阿鲁科尔沁旗', '371', '150421', '3');
INSERT INTO `tao_region` VALUES ('376', '巴林左旗', '371', '150422', '3');
INSERT INTO `tao_region` VALUES ('377', '巴林右旗', '371', '150423', '3');
INSERT INTO `tao_region` VALUES ('378', '林西县', '371', '150424', '3');
INSERT INTO `tao_region` VALUES ('379', '克什克腾旗', '371', '150425', '3');
INSERT INTO `tao_region` VALUES ('380', '翁牛特旗', '371', '150426', '3');
INSERT INTO `tao_region` VALUES ('381', '喀喇沁旗', '371', '150428', '3');
INSERT INTO `tao_region` VALUES ('382', '宁城县', '371', '150429', '3');
INSERT INTO `tao_region` VALUES ('383', '敖汉旗', '371', '150430', '3');
INSERT INTO `tao_region` VALUES ('384', '通辽市', '346', '150500', '2');
INSERT INTO `tao_region` VALUES ('385', '科尔沁区', '384', '150502', '3');
INSERT INTO `tao_region` VALUES ('386', '科尔沁左翼中旗', '384', '150521', '3');
INSERT INTO `tao_region` VALUES ('387', '科尔沁左翼后旗', '384', '150522', '3');
INSERT INTO `tao_region` VALUES ('388', '开鲁县', '384', '150523', '3');
INSERT INTO `tao_region` VALUES ('389', '库伦旗', '384', '150524', '3');
INSERT INTO `tao_region` VALUES ('390', '奈曼旗', '384', '150525', '3');
INSERT INTO `tao_region` VALUES ('391', '扎鲁特旗', '384', '150526', '3');
INSERT INTO `tao_region` VALUES ('392', '霍林郭勒市', '384', '150581', '3');
INSERT INTO `tao_region` VALUES ('393', '鄂尔多斯市', '346', '150600', '2');
INSERT INTO `tao_region` VALUES ('394', '东胜区', '393', '150602', '3');
INSERT INTO `tao_region` VALUES ('395', '康巴什区', '393', '150603', '3');
INSERT INTO `tao_region` VALUES ('396', '达拉特旗', '393', '150621', '3');
INSERT INTO `tao_region` VALUES ('397', '准格尔旗', '393', '150622', '3');
INSERT INTO `tao_region` VALUES ('398', '鄂托克前旗', '393', '150623', '3');
INSERT INTO `tao_region` VALUES ('399', '鄂托克旗', '393', '150624', '3');
INSERT INTO `tao_region` VALUES ('400', '杭锦旗', '393', '150625', '3');
INSERT INTO `tao_region` VALUES ('401', '乌审旗', '393', '150626', '3');
INSERT INTO `tao_region` VALUES ('402', '伊金霍洛旗', '393', '150627', '3');
INSERT INTO `tao_region` VALUES ('403', '呼伦贝尔市', '346', '150700', '2');
INSERT INTO `tao_region` VALUES ('404', '海拉尔区', '403', '150702', '3');
INSERT INTO `tao_region` VALUES ('405', '扎赉诺尔区', '403', '150703', '3');
INSERT INTO `tao_region` VALUES ('406', '阿荣旗', '403', '150721', '3');
INSERT INTO `tao_region` VALUES ('407', '莫力达瓦达斡尔族自治旗', '403', '150722', '3');
INSERT INTO `tao_region` VALUES ('408', '鄂伦春自治旗', '403', '150723', '3');
INSERT INTO `tao_region` VALUES ('409', '鄂温克族自治旗', '403', '150724', '3');
INSERT INTO `tao_region` VALUES ('410', '陈巴尔虎旗', '403', '150725', '3');
INSERT INTO `tao_region` VALUES ('411', '新巴尔虎左旗', '403', '150726', '3');
INSERT INTO `tao_region` VALUES ('412', '新巴尔虎右旗', '403', '150727', '3');
INSERT INTO `tao_region` VALUES ('413', '满洲里市', '403', '150781', '3');
INSERT INTO `tao_region` VALUES ('414', '牙克石市', '403', '150782', '3');
INSERT INTO `tao_region` VALUES ('415', '扎兰屯市', '403', '150783', '3');
INSERT INTO `tao_region` VALUES ('416', '额尔古纳市', '403', '150784', '3');
INSERT INTO `tao_region` VALUES ('417', '根河市', '403', '150785', '3');
INSERT INTO `tao_region` VALUES ('418', '巴彦淖尔市', '346', '150800', '2');
INSERT INTO `tao_region` VALUES ('419', '临河区', '418', '150802', '3');
INSERT INTO `tao_region` VALUES ('420', '五原县', '418', '150821', '3');
INSERT INTO `tao_region` VALUES ('421', '磴口县', '418', '150822', '3');
INSERT INTO `tao_region` VALUES ('422', '乌拉特前旗', '418', '150823', '3');
INSERT INTO `tao_region` VALUES ('423', '乌拉特中旗', '418', '150824', '3');
INSERT INTO `tao_region` VALUES ('424', '乌拉特后旗', '418', '150825', '3');
INSERT INTO `tao_region` VALUES ('425', '杭锦后旗', '418', '150826', '3');
INSERT INTO `tao_region` VALUES ('426', '乌兰察布市', '346', '150900', '2');
INSERT INTO `tao_region` VALUES ('427', '集宁区', '426', '150902', '3');
INSERT INTO `tao_region` VALUES ('428', '卓资县', '426', '150921', '3');
INSERT INTO `tao_region` VALUES ('429', '化德县', '426', '150922', '3');
INSERT INTO `tao_region` VALUES ('430', '商都县', '426', '150923', '3');
INSERT INTO `tao_region` VALUES ('431', '兴和县', '426', '150924', '3');
INSERT INTO `tao_region` VALUES ('432', '凉城县', '426', '150925', '3');
INSERT INTO `tao_region` VALUES ('433', '察哈尔右翼前旗', '426', '150926', '3');
INSERT INTO `tao_region` VALUES ('434', '察哈尔右翼中旗', '426', '150927', '3');
INSERT INTO `tao_region` VALUES ('435', '察哈尔右翼后旗', '426', '150928', '3');
INSERT INTO `tao_region` VALUES ('436', '四子王旗', '426', '150929', '3');
INSERT INTO `tao_region` VALUES ('437', '丰镇市', '426', '150981', '3');
INSERT INTO `tao_region` VALUES ('438', '兴安盟', '346', '152200', '2');
INSERT INTO `tao_region` VALUES ('439', '乌兰浩特市', '438', '152201', '3');
INSERT INTO `tao_region` VALUES ('440', '阿尔山市', '438', '152202', '3');
INSERT INTO `tao_region` VALUES ('441', '科尔沁右翼前旗', '438', '152221', '3');
INSERT INTO `tao_region` VALUES ('442', '科尔沁右翼中旗', '438', '152222', '3');
INSERT INTO `tao_region` VALUES ('443', '扎赉特旗', '438', '152223', '3');
INSERT INTO `tao_region` VALUES ('444', '突泉县', '438', '152224', '3');
INSERT INTO `tao_region` VALUES ('445', '锡林郭勒盟', '346', '152500', '2');
INSERT INTO `tao_region` VALUES ('446', '二连浩特市', '445', '152501', '3');
INSERT INTO `tao_region` VALUES ('447', '锡林浩特市', '445', '152502', '3');
INSERT INTO `tao_region` VALUES ('448', '阿巴嘎旗', '445', '152522', '3');
INSERT INTO `tao_region` VALUES ('449', '苏尼特左旗', '445', '152523', '3');
INSERT INTO `tao_region` VALUES ('450', '苏尼特右旗', '445', '152524', '3');
INSERT INTO `tao_region` VALUES ('451', '东乌珠穆沁旗', '445', '152525', '3');
INSERT INTO `tao_region` VALUES ('452', '西乌珠穆沁旗', '445', '152526', '3');
INSERT INTO `tao_region` VALUES ('453', '太仆寺旗', '445', '152527', '3');
INSERT INTO `tao_region` VALUES ('454', '镶黄旗', '445', '152528', '3');
INSERT INTO `tao_region` VALUES ('455', '正镶白旗', '445', '152529', '3');
INSERT INTO `tao_region` VALUES ('456', '正蓝旗', '445', '152530', '3');
INSERT INTO `tao_region` VALUES ('457', '多伦县', '445', '152531', '3');
INSERT INTO `tao_region` VALUES ('458', '阿拉善盟', '346', '152900', '2');
INSERT INTO `tao_region` VALUES ('459', '阿拉善左旗', '458', '152921', '3');
INSERT INTO `tao_region` VALUES ('460', '阿拉善右旗', '458', '152922', '3');
INSERT INTO `tao_region` VALUES ('461', '额济纳旗', '458', '152923', '3');
INSERT INTO `tao_region` VALUES ('462', '辽宁省', '0', '210000', '1');
INSERT INTO `tao_region` VALUES ('463', '沈阳市', '462', '210100', '2');
INSERT INTO `tao_region` VALUES ('464', '和平区', '463', '210102', '3');
INSERT INTO `tao_region` VALUES ('465', '沈河区', '463', '210103', '3');
INSERT INTO `tao_region` VALUES ('466', '大东区', '463', '210104', '3');
INSERT INTO `tao_region` VALUES ('467', '皇姑区', '463', '210105', '3');
INSERT INTO `tao_region` VALUES ('468', '铁西区', '463', '210106', '3');
INSERT INTO `tao_region` VALUES ('469', '苏家屯区', '463', '210111', '3');
INSERT INTO `tao_region` VALUES ('470', '浑南区', '463', '210112', '3');
INSERT INTO `tao_region` VALUES ('471', '沈北新区', '463', '210113', '3');
INSERT INTO `tao_region` VALUES ('472', '于洪区', '463', '210114', '3');
INSERT INTO `tao_region` VALUES ('473', '辽中区', '463', '210115', '3');
INSERT INTO `tao_region` VALUES ('474', '康平县', '463', '210123', '3');
INSERT INTO `tao_region` VALUES ('475', '法库县', '463', '210124', '3');
INSERT INTO `tao_region` VALUES ('476', '新民市', '463', '210181', '3');
INSERT INTO `tao_region` VALUES ('477', '大连市', '462', '210200', '2');
INSERT INTO `tao_region` VALUES ('478', '中山区', '477', '210202', '3');
INSERT INTO `tao_region` VALUES ('479', '西岗区', '477', '210203', '3');
INSERT INTO `tao_region` VALUES ('480', '沙河口区', '477', '210204', '3');
INSERT INTO `tao_region` VALUES ('481', '甘井子区', '477', '210211', '3');
INSERT INTO `tao_region` VALUES ('482', '旅顺口区', '477', '210212', '3');
INSERT INTO `tao_region` VALUES ('483', '金州区', '477', '210213', '3');
INSERT INTO `tao_region` VALUES ('484', '普兰店区', '477', '210214', '3');
INSERT INTO `tao_region` VALUES ('485', '长海县', '477', '210224', '3');
INSERT INTO `tao_region` VALUES ('486', '瓦房店市', '477', '210281', '3');
INSERT INTO `tao_region` VALUES ('487', '庄河市', '477', '210283', '3');
INSERT INTO `tao_region` VALUES ('488', '鞍山市', '462', '210300', '2');
INSERT INTO `tao_region` VALUES ('489', '铁东区', '488', '210302', '3');
INSERT INTO `tao_region` VALUES ('490', '铁西区', '488', '210303', '3');
INSERT INTO `tao_region` VALUES ('491', '立山区', '488', '210304', '3');
INSERT INTO `tao_region` VALUES ('492', '千山区', '488', '210311', '3');
INSERT INTO `tao_region` VALUES ('493', '台安县', '488', '210321', '3');
INSERT INTO `tao_region` VALUES ('494', '岫岩满族自治县', '488', '210323', '3');
INSERT INTO `tao_region` VALUES ('495', '海城市', '488', '210381', '3');
INSERT INTO `tao_region` VALUES ('496', '抚顺市', '462', '210400', '2');
INSERT INTO `tao_region` VALUES ('497', '新抚区', '496', '210402', '3');
INSERT INTO `tao_region` VALUES ('498', '东洲区', '496', '210403', '3');
INSERT INTO `tao_region` VALUES ('499', '望花区', '496', '210404', '3');
INSERT INTO `tao_region` VALUES ('500', '顺城区', '496', '210411', '3');
INSERT INTO `tao_region` VALUES ('501', '抚顺县', '496', '210421', '3');
INSERT INTO `tao_region` VALUES ('502', '新宾满族自治县', '496', '210422', '3');
INSERT INTO `tao_region` VALUES ('503', '清原满族自治县', '496', '210423', '3');
INSERT INTO `tao_region` VALUES ('504', '本溪市', '462', '210500', '2');
INSERT INTO `tao_region` VALUES ('505', '平山区', '504', '210502', '3');
INSERT INTO `tao_region` VALUES ('506', '溪湖区', '504', '210503', '3');
INSERT INTO `tao_region` VALUES ('507', '明山区', '504', '210504', '3');
INSERT INTO `tao_region` VALUES ('508', '南芬区', '504', '210505', '3');
INSERT INTO `tao_region` VALUES ('509', '本溪满族自治县', '504', '210521', '3');
INSERT INTO `tao_region` VALUES ('510', '桓仁满族自治县', '504', '210522', '3');
INSERT INTO `tao_region` VALUES ('511', '丹东市', '462', '210600', '2');
INSERT INTO `tao_region` VALUES ('512', '元宝区', '511', '210602', '3');
INSERT INTO `tao_region` VALUES ('513', '振兴区', '511', '210603', '3');
INSERT INTO `tao_region` VALUES ('514', '振安区', '511', '210604', '3');
INSERT INTO `tao_region` VALUES ('515', '宽甸满族自治县', '511', '210624', '3');
INSERT INTO `tao_region` VALUES ('516', '东港市', '511', '210681', '3');
INSERT INTO `tao_region` VALUES ('517', '凤城市', '511', '210682', '3');
INSERT INTO `tao_region` VALUES ('518', '锦州市', '462', '210700', '2');
INSERT INTO `tao_region` VALUES ('519', '古塔区', '518', '210702', '3');
INSERT INTO `tao_region` VALUES ('520', '凌河区', '518', '210703', '3');
INSERT INTO `tao_region` VALUES ('521', '太和区', '518', '210711', '3');
INSERT INTO `tao_region` VALUES ('522', '黑山县', '518', '210726', '3');
INSERT INTO `tao_region` VALUES ('523', '义县', '518', '210727', '3');
INSERT INTO `tao_region` VALUES ('524', '凌海市', '518', '210781', '3');
INSERT INTO `tao_region` VALUES ('525', '北镇市', '518', '210782', '3');
INSERT INTO `tao_region` VALUES ('526', '营口市', '462', '210800', '2');
INSERT INTO `tao_region` VALUES ('527', '站前区', '526', '210802', '3');
INSERT INTO `tao_region` VALUES ('528', '西市区', '526', '210803', '3');
INSERT INTO `tao_region` VALUES ('529', '鲅鱼圈区', '526', '210804', '3');
INSERT INTO `tao_region` VALUES ('530', '老边区', '526', '210811', '3');
INSERT INTO `tao_region` VALUES ('531', '盖州市', '526', '210881', '3');
INSERT INTO `tao_region` VALUES ('532', '大石桥市', '526', '210882', '3');
INSERT INTO `tao_region` VALUES ('533', '阜新市', '462', '210900', '2');
INSERT INTO `tao_region` VALUES ('534', '海州区', '533', '210902', '3');
INSERT INTO `tao_region` VALUES ('535', '新邱区', '533', '210903', '3');
INSERT INTO `tao_region` VALUES ('536', '太平区', '533', '210904', '3');
INSERT INTO `tao_region` VALUES ('537', '清河门区', '533', '210905', '3');
INSERT INTO `tao_region` VALUES ('538', '细河区', '533', '210911', '3');
INSERT INTO `tao_region` VALUES ('539', '阜新蒙古族自治县', '533', '210921', '3');
INSERT INTO `tao_region` VALUES ('540', '彰武县', '533', '210922', '3');
INSERT INTO `tao_region` VALUES ('541', '辽阳市', '462', '211000', '2');
INSERT INTO `tao_region` VALUES ('542', '白塔区', '541', '211002', '3');
INSERT INTO `tao_region` VALUES ('543', '文圣区', '541', '211003', '3');
INSERT INTO `tao_region` VALUES ('544', '宏伟区', '541', '211004', '3');
INSERT INTO `tao_region` VALUES ('545', '弓长岭区', '541', '211005', '3');
INSERT INTO `tao_region` VALUES ('546', '太子河区', '541', '211011', '3');
INSERT INTO `tao_region` VALUES ('547', '辽阳县', '541', '211021', '3');
INSERT INTO `tao_region` VALUES ('548', '灯塔市', '541', '211081', '3');
INSERT INTO `tao_region` VALUES ('549', '盘锦市', '462', '211100', '2');
INSERT INTO `tao_region` VALUES ('550', '双台子区', '549', '211102', '3');
INSERT INTO `tao_region` VALUES ('551', '兴隆台区', '549', '211103', '3');
INSERT INTO `tao_region` VALUES ('552', '大洼区', '549', '211104', '3');
INSERT INTO `tao_region` VALUES ('553', '盘山县', '549', '211122', '3');
INSERT INTO `tao_region` VALUES ('554', '铁岭市', '462', '211200', '2');
INSERT INTO `tao_region` VALUES ('555', '银州区', '554', '211202', '3');
INSERT INTO `tao_region` VALUES ('556', '清河区', '554', '211204', '3');
INSERT INTO `tao_region` VALUES ('557', '铁岭县', '554', '211221', '3');
INSERT INTO `tao_region` VALUES ('558', '西丰县', '554', '211223', '3');
INSERT INTO `tao_region` VALUES ('559', '昌图县', '554', '211224', '3');
INSERT INTO `tao_region` VALUES ('560', '调兵山市', '554', '211281', '3');
INSERT INTO `tao_region` VALUES ('561', '开原市', '554', '211282', '3');
INSERT INTO `tao_region` VALUES ('562', '朝阳市', '462', '211300', '2');
INSERT INTO `tao_region` VALUES ('563', '双塔区', '562', '211302', '3');
INSERT INTO `tao_region` VALUES ('564', '龙城区', '562', '211303', '3');
INSERT INTO `tao_region` VALUES ('565', '朝阳县', '562', '211321', '3');
INSERT INTO `tao_region` VALUES ('566', '建平县', '562', '211322', '3');
INSERT INTO `tao_region` VALUES ('567', '喀喇沁左翼蒙古族自治县', '562', '211324', '3');
INSERT INTO `tao_region` VALUES ('568', '北票市', '562', '211381', '3');
INSERT INTO `tao_region` VALUES ('569', '凌源市', '562', '211382', '3');
INSERT INTO `tao_region` VALUES ('570', '葫芦岛市', '462', '211400', '2');
INSERT INTO `tao_region` VALUES ('571', '连山区', '570', '211402', '3');
INSERT INTO `tao_region` VALUES ('572', '龙港区', '570', '211403', '3');
INSERT INTO `tao_region` VALUES ('573', '南票区', '570', '211404', '3');
INSERT INTO `tao_region` VALUES ('574', '绥中县', '570', '211421', '3');
INSERT INTO `tao_region` VALUES ('575', '建昌县', '570', '211422', '3');
INSERT INTO `tao_region` VALUES ('576', '兴城市', '570', '211481', '3');
INSERT INTO `tao_region` VALUES ('577', '吉林省', '0', '220000', '1');
INSERT INTO `tao_region` VALUES ('578', '长春市', '577', '220100', '2');
INSERT INTO `tao_region` VALUES ('579', '南关区', '578', '220102', '3');
INSERT INTO `tao_region` VALUES ('580', '宽城区', '578', '220103', '3');
INSERT INTO `tao_region` VALUES ('581', '朝阳区', '578', '220104', '3');
INSERT INTO `tao_region` VALUES ('582', '二道区', '578', '220105', '3');
INSERT INTO `tao_region` VALUES ('583', '绿园区', '578', '220106', '3');
INSERT INTO `tao_region` VALUES ('584', '双阳区', '578', '220112', '3');
INSERT INTO `tao_region` VALUES ('585', '九台区', '578', '220113', '3');
INSERT INTO `tao_region` VALUES ('586', '农安县', '578', '220122', '3');
INSERT INTO `tao_region` VALUES ('587', '榆树市', '578', '220182', '3');
INSERT INTO `tao_region` VALUES ('588', '德惠市', '578', '220183', '3');
INSERT INTO `tao_region` VALUES ('589', '吉林市', '577', '220200', '2');
INSERT INTO `tao_region` VALUES ('590', '昌邑区', '589', '220202', '3');
INSERT INTO `tao_region` VALUES ('591', '龙潭区', '589', '220203', '3');
INSERT INTO `tao_region` VALUES ('592', '船营区', '589', '220204', '3');
INSERT INTO `tao_region` VALUES ('593', '丰满区', '589', '220211', '3');
INSERT INTO `tao_region` VALUES ('594', '永吉县', '589', '220221', '3');
INSERT INTO `tao_region` VALUES ('595', '蛟河市', '589', '220281', '3');
INSERT INTO `tao_region` VALUES ('596', '桦甸市', '589', '220282', '3');
INSERT INTO `tao_region` VALUES ('597', '舒兰市', '589', '220283', '3');
INSERT INTO `tao_region` VALUES ('598', '磐石市', '589', '220284', '3');
INSERT INTO `tao_region` VALUES ('599', '四平市', '577', '220300', '2');
INSERT INTO `tao_region` VALUES ('600', '铁西区', '599', '220302', '3');
INSERT INTO `tao_region` VALUES ('601', '铁东区', '599', '220303', '3');
INSERT INTO `tao_region` VALUES ('602', '梨树县', '599', '220322', '3');
INSERT INTO `tao_region` VALUES ('603', '伊通满族自治县', '599', '220323', '3');
INSERT INTO `tao_region` VALUES ('605', '双辽市', '599', '220382', '3');
INSERT INTO `tao_region` VALUES ('606', '辽源市', '577', '220400', '2');
INSERT INTO `tao_region` VALUES ('607', '龙山区', '606', '220402', '3');
INSERT INTO `tao_region` VALUES ('608', '西安区', '606', '220403', '3');
INSERT INTO `tao_region` VALUES ('609', '东丰县', '606', '220421', '3');
INSERT INTO `tao_region` VALUES ('610', '东辽县', '606', '220422', '3');
INSERT INTO `tao_region` VALUES ('611', '通化市', '577', '220500', '2');
INSERT INTO `tao_region` VALUES ('612', '东昌区', '611', '220502', '3');
INSERT INTO `tao_region` VALUES ('613', '二道江区', '611', '220503', '3');
INSERT INTO `tao_region` VALUES ('614', '通化县', '611', '220521', '3');
INSERT INTO `tao_region` VALUES ('615', '辉南县', '611', '220523', '3');
INSERT INTO `tao_region` VALUES ('616', '柳河县', '611', '220524', '3');
INSERT INTO `tao_region` VALUES ('617', '梅河口市', '611', '220581', '3');
INSERT INTO `tao_region` VALUES ('618', '集安市', '611', '220582', '3');
INSERT INTO `tao_region` VALUES ('619', '白山市', '577', '220600', '2');
INSERT INTO `tao_region` VALUES ('620', '浑江区', '619', '220602', '3');
INSERT INTO `tao_region` VALUES ('621', '江源区', '619', '220605', '3');
INSERT INTO `tao_region` VALUES ('622', '抚松县', '619', '220621', '3');
INSERT INTO `tao_region` VALUES ('623', '靖宇县', '619', '220622', '3');
INSERT INTO `tao_region` VALUES ('624', '长白朝鲜族自治县', '619', '220623', '3');
INSERT INTO `tao_region` VALUES ('625', '临江市', '619', '220681', '3');
INSERT INTO `tao_region` VALUES ('626', '松原市', '577', '220700', '2');
INSERT INTO `tao_region` VALUES ('627', '宁江区', '626', '220702', '3');
INSERT INTO `tao_region` VALUES ('628', '前郭尔罗斯蒙古族自治县', '626', '220721', '3');
INSERT INTO `tao_region` VALUES ('629', '长岭县', '626', '220722', '3');
INSERT INTO `tao_region` VALUES ('630', '乾安县', '626', '220723', '3');
INSERT INTO `tao_region` VALUES ('631', '扶余市', '626', '220781', '3');
INSERT INTO `tao_region` VALUES ('632', '白城市', '577', '220800', '2');
INSERT INTO `tao_region` VALUES ('633', '洮北区', '632', '220802', '3');
INSERT INTO `tao_region` VALUES ('634', '镇赉县', '632', '220821', '3');
INSERT INTO `tao_region` VALUES ('635', '通榆县', '632', '220822', '3');
INSERT INTO `tao_region` VALUES ('636', '洮南市', '632', '220881', '3');
INSERT INTO `tao_region` VALUES ('637', '大安市', '632', '220882', '3');
INSERT INTO `tao_region` VALUES ('638', '延边朝鲜族自治州', '577', '222400', '2');
INSERT INTO `tao_region` VALUES ('639', '延吉市', '638', '222401', '3');
INSERT INTO `tao_region` VALUES ('640', '图们市', '638', '222402', '3');
INSERT INTO `tao_region` VALUES ('641', '敦化市', '638', '222403', '3');
INSERT INTO `tao_region` VALUES ('642', '珲春市', '638', '222404', '3');
INSERT INTO `tao_region` VALUES ('643', '龙井市', '638', '222405', '3');
INSERT INTO `tao_region` VALUES ('644', '和龙市', '638', '222406', '3');
INSERT INTO `tao_region` VALUES ('645', '汪清县', '638', '222424', '3');
INSERT INTO `tao_region` VALUES ('646', '安图县', '638', '222426', '3');
INSERT INTO `tao_region` VALUES ('647', '黑龙江省', '0', '230000', '1');
INSERT INTO `tao_region` VALUES ('648', '哈尔滨市', '647', '230100', '2');
INSERT INTO `tao_region` VALUES ('649', '道里区', '648', '230102', '3');
INSERT INTO `tao_region` VALUES ('650', '南岗区', '648', '230103', '3');
INSERT INTO `tao_region` VALUES ('651', '道外区', '648', '230104', '3');
INSERT INTO `tao_region` VALUES ('652', '平房区', '648', '230108', '3');
INSERT INTO `tao_region` VALUES ('653', '松北区', '648', '230109', '3');
INSERT INTO `tao_region` VALUES ('654', '香坊区', '648', '230110', '3');
INSERT INTO `tao_region` VALUES ('655', '呼兰区', '648', '230111', '3');
INSERT INTO `tao_region` VALUES ('656', '阿城区', '648', '230112', '3');
INSERT INTO `tao_region` VALUES ('657', '双城区', '648', '230113', '3');
INSERT INTO `tao_region` VALUES ('658', '依兰县', '648', '230123', '3');
INSERT INTO `tao_region` VALUES ('659', '方正县', '648', '230124', '3');
INSERT INTO `tao_region` VALUES ('660', '宾县', '648', '230125', '3');
INSERT INTO `tao_region` VALUES ('661', '巴彦县', '648', '230126', '3');
INSERT INTO `tao_region` VALUES ('662', '木兰县', '648', '230127', '3');
INSERT INTO `tao_region` VALUES ('663', '通河县', '648', '230128', '3');
INSERT INTO `tao_region` VALUES ('664', '延寿县', '648', '230129', '3');
INSERT INTO `tao_region` VALUES ('665', '尚志市', '648', '230183', '3');
INSERT INTO `tao_region` VALUES ('666', '五常市', '648', '230184', '3');
INSERT INTO `tao_region` VALUES ('667', '齐齐哈尔市', '647', '230200', '2');
INSERT INTO `tao_region` VALUES ('668', '龙沙区', '667', '230202', '3');
INSERT INTO `tao_region` VALUES ('669', '建华区', '667', '230203', '3');
INSERT INTO `tao_region` VALUES ('670', '铁锋区', '667', '230204', '3');
INSERT INTO `tao_region` VALUES ('671', '昂昂溪区', '667', '230205', '3');
INSERT INTO `tao_region` VALUES ('672', '富拉尔基区', '667', '230206', '3');
INSERT INTO `tao_region` VALUES ('673', '碾子山区', '667', '230207', '3');
INSERT INTO `tao_region` VALUES ('674', '梅里斯达斡尔族区', '667', '230208', '3');
INSERT INTO `tao_region` VALUES ('675', '龙江县', '667', '230221', '3');
INSERT INTO `tao_region` VALUES ('676', '依安县', '667', '230223', '3');
INSERT INTO `tao_region` VALUES ('677', '泰来县', '667', '230224', '3');
INSERT INTO `tao_region` VALUES ('678', '甘南县', '667', '230225', '3');
INSERT INTO `tao_region` VALUES ('679', '富裕县', '667', '230227', '3');
INSERT INTO `tao_region` VALUES ('680', '克山县', '667', '230229', '3');
INSERT INTO `tao_region` VALUES ('681', '克东县', '667', '230230', '3');
INSERT INTO `tao_region` VALUES ('682', '拜泉县', '667', '230231', '3');
INSERT INTO `tao_region` VALUES ('683', '讷河市', '667', '230281', '3');
INSERT INTO `tao_region` VALUES ('684', '鸡西市', '647', '230300', '2');
INSERT INTO `tao_region` VALUES ('685', '鸡冠区', '684', '230302', '3');
INSERT INTO `tao_region` VALUES ('686', '恒山区', '684', '230303', '3');
INSERT INTO `tao_region` VALUES ('687', '滴道区', '684', '230304', '3');
INSERT INTO `tao_region` VALUES ('688', '梨树区', '684', '230305', '3');
INSERT INTO `tao_region` VALUES ('689', '城子河区', '684', '230306', '3');
INSERT INTO `tao_region` VALUES ('690', '麻山区', '684', '230307', '3');
INSERT INTO `tao_region` VALUES ('691', '鸡东县', '684', '230321', '3');
INSERT INTO `tao_region` VALUES ('692', '虎林市', '684', '230381', '3');
INSERT INTO `tao_region` VALUES ('693', '密山市', '684', '230382', '3');
INSERT INTO `tao_region` VALUES ('694', '鹤岗市', '647', '230400', '2');
INSERT INTO `tao_region` VALUES ('695', '向阳区', '694', '230402', '3');
INSERT INTO `tao_region` VALUES ('696', '工农区', '694', '230403', '3');
INSERT INTO `tao_region` VALUES ('697', '南山区', '694', '230404', '3');
INSERT INTO `tao_region` VALUES ('698', '兴安区', '694', '230405', '3');
INSERT INTO `tao_region` VALUES ('699', '东山区', '694', '230406', '3');
INSERT INTO `tao_region` VALUES ('700', '兴山区', '694', '230407', '3');
INSERT INTO `tao_region` VALUES ('701', '萝北县', '694', '230421', '3');
INSERT INTO `tao_region` VALUES ('702', '绥滨县', '694', '230422', '3');
INSERT INTO `tao_region` VALUES ('703', '双鸭山市', '647', '230500', '2');
INSERT INTO `tao_region` VALUES ('704', '尖山区', '703', '230502', '3');
INSERT INTO `tao_region` VALUES ('705', '岭东区', '703', '230503', '3');
INSERT INTO `tao_region` VALUES ('706', '四方台区', '703', '230505', '3');
INSERT INTO `tao_region` VALUES ('707', '宝山区', '703', '230506', '3');
INSERT INTO `tao_region` VALUES ('708', '集贤县', '703', '230521', '3');
INSERT INTO `tao_region` VALUES ('709', '友谊县', '703', '230522', '3');
INSERT INTO `tao_region` VALUES ('710', '宝清县', '703', '230523', '3');
INSERT INTO `tao_region` VALUES ('711', '饶河县', '703', '230524', '3');
INSERT INTO `tao_region` VALUES ('712', '大庆市', '647', '230600', '2');
INSERT INTO `tao_region` VALUES ('713', '萨尔图区', '712', '230602', '3');
INSERT INTO `tao_region` VALUES ('714', '龙凤区', '712', '230603', '3');
INSERT INTO `tao_region` VALUES ('715', '让胡路区', '712', '230604', '3');
INSERT INTO `tao_region` VALUES ('716', '红岗区', '712', '230605', '3');
INSERT INTO `tao_region` VALUES ('717', '大同区', '712', '230606', '3');
INSERT INTO `tao_region` VALUES ('718', '肇州县', '712', '230621', '3');
INSERT INTO `tao_region` VALUES ('719', '肇源县', '712', '230622', '3');
INSERT INTO `tao_region` VALUES ('720', '林甸县', '712', '230623', '3');
INSERT INTO `tao_region` VALUES ('721', '杜尔伯特蒙古族自治县', '712', '230624', '3');
INSERT INTO `tao_region` VALUES ('722', '伊春市', '647', '230700', '2');
INSERT INTO `tao_region` VALUES ('723', '伊美区', '722', '230717', '3');
INSERT INTO `tao_region` VALUES ('724', '乌翠区', '722', '230718', '3');
INSERT INTO `tao_region` VALUES ('725', '友好区', '722', '230719', '3');
INSERT INTO `tao_region` VALUES ('726', '嘉荫县', '722', '230722', '3');
INSERT INTO `tao_region` VALUES ('727', '汤旺县', '722', '230723', '3');
INSERT INTO `tao_region` VALUES ('728', '丰林县', '722', '230724', '3');
INSERT INTO `tao_region` VALUES ('729', '大箐山县', '722', '230725', '3');
INSERT INTO `tao_region` VALUES ('730', '南岔县', '722', '230726', '3');
INSERT INTO `tao_region` VALUES ('731', '金林区', '722', '230751', '3');
INSERT INTO `tao_region` VALUES ('732', '铁力市', '722', '230781', '3');
INSERT INTO `tao_region` VALUES ('733', '佳木斯市', '647', '230800', '2');
INSERT INTO `tao_region` VALUES ('734', '向阳区', '733', '230803', '3');
INSERT INTO `tao_region` VALUES ('735', '前进区', '733', '230804', '3');
INSERT INTO `tao_region` VALUES ('736', '东风区', '733', '230805', '3');
INSERT INTO `tao_region` VALUES ('737', '郊区', '733', '230811', '3');
INSERT INTO `tao_region` VALUES ('738', '桦南县', '733', '230822', '3');
INSERT INTO `tao_region` VALUES ('739', '桦川县', '733', '230826', '3');
INSERT INTO `tao_region` VALUES ('740', '汤原县', '733', '230828', '3');
INSERT INTO `tao_region` VALUES ('741', '同江市', '733', '230881', '3');
INSERT INTO `tao_region` VALUES ('742', '富锦市', '733', '230882', '3');
INSERT INTO `tao_region` VALUES ('743', '抚远市', '733', '230883', '3');
INSERT INTO `tao_region` VALUES ('744', '七台河市', '647', '230900', '2');
INSERT INTO `tao_region` VALUES ('745', '新兴区', '744', '230902', '3');
INSERT INTO `tao_region` VALUES ('746', '桃山区', '744', '230903', '3');
INSERT INTO `tao_region` VALUES ('747', '茄子河区', '744', '230904', '3');
INSERT INTO `tao_region` VALUES ('748', '勃利县', '744', '230921', '3');
INSERT INTO `tao_region` VALUES ('749', '牡丹江市', '647', '231000', '2');
INSERT INTO `tao_region` VALUES ('750', '东安区', '749', '231002', '3');
INSERT INTO `tao_region` VALUES ('751', '阳明区', '749', '231003', '3');
INSERT INTO `tao_region` VALUES ('752', '爱民区', '749', '231004', '3');
INSERT INTO `tao_region` VALUES ('753', '西安区', '749', '231005', '3');
INSERT INTO `tao_region` VALUES ('754', '林口县', '749', '231025', '3');
INSERT INTO `tao_region` VALUES ('755', '绥芬河市', '749', '231081', '3');
INSERT INTO `tao_region` VALUES ('756', '海林市', '749', '231083', '3');
INSERT INTO `tao_region` VALUES ('757', '宁安市', '749', '231084', '3');
INSERT INTO `tao_region` VALUES ('758', '穆棱市', '749', '231085', '3');
INSERT INTO `tao_region` VALUES ('759', '东宁市', '749', '231086', '3');
INSERT INTO `tao_region` VALUES ('760', '黑河市', '647', '231100', '2');
INSERT INTO `tao_region` VALUES ('761', '爱辉区', '760', '231102', '3');
INSERT INTO `tao_region` VALUES ('762', '逊克县', '760', '231123', '3');
INSERT INTO `tao_region` VALUES ('763', '孙吴县', '760', '231124', '3');
INSERT INTO `tao_region` VALUES ('764', '北安市', '760', '231181', '3');
INSERT INTO `tao_region` VALUES ('765', '五大连池市', '760', '231182', '3');
INSERT INTO `tao_region` VALUES ('766', '嫩江市', '760', '231183', '3');
INSERT INTO `tao_region` VALUES ('767', '绥化市', '647', '231200', '2');
INSERT INTO `tao_region` VALUES ('768', '北林区', '767', '231202', '3');
INSERT INTO `tao_region` VALUES ('769', '望奎县', '767', '231221', '3');
INSERT INTO `tao_region` VALUES ('770', '兰西县', '767', '231222', '3');
INSERT INTO `tao_region` VALUES ('771', '青冈县', '767', '231223', '3');
INSERT INTO `tao_region` VALUES ('772', '庆安县', '767', '231224', '3');
INSERT INTO `tao_region` VALUES ('773', '明水县', '767', '231225', '3');
INSERT INTO `tao_region` VALUES ('774', '绥棱县', '767', '231226', '3');
INSERT INTO `tao_region` VALUES ('775', '安达市', '767', '231281', '3');
INSERT INTO `tao_region` VALUES ('776', '肇东市', '767', '231282', '3');
INSERT INTO `tao_region` VALUES ('777', '海伦市', '767', '231283', '3');
INSERT INTO `tao_region` VALUES ('778', '大兴安岭地区', '647', '232700', '2');
INSERT INTO `tao_region` VALUES ('779', '漠河市', '778', '232701', '3');
INSERT INTO `tao_region` VALUES ('780', '呼玛县', '778', '232721', '3');
INSERT INTO `tao_region` VALUES ('781', '塔河县', '778', '232722', '3');
INSERT INTO `tao_region` VALUES ('782', '上海市', '0', '310000', '1');
INSERT INTO `tao_region` VALUES ('783', '上海市', '782', '310100', '2');
INSERT INTO `tao_region` VALUES ('784', '黄浦区', '783', '310101', '3');
INSERT INTO `tao_region` VALUES ('785', '徐汇区', '783', '310104', '3');
INSERT INTO `tao_region` VALUES ('786', '长宁区', '783', '310105', '3');
INSERT INTO `tao_region` VALUES ('787', '静安区', '783', '310106', '3');
INSERT INTO `tao_region` VALUES ('788', '普陀区', '783', '310107', '3');
INSERT INTO `tao_region` VALUES ('789', '虹口区', '783', '310109', '3');
INSERT INTO `tao_region` VALUES ('790', '杨浦区', '783', '310110', '3');
INSERT INTO `tao_region` VALUES ('791', '闵行区', '783', '310112', '3');
INSERT INTO `tao_region` VALUES ('792', '宝山区', '783', '310113', '3');
INSERT INTO `tao_region` VALUES ('793', '嘉定区', '783', '310114', '3');
INSERT INTO `tao_region` VALUES ('794', '浦东新区', '783', '310115', '3');
INSERT INTO `tao_region` VALUES ('795', '金山区', '783', '310116', '3');
INSERT INTO `tao_region` VALUES ('796', '松江区', '783', '310117', '3');
INSERT INTO `tao_region` VALUES ('797', '青浦区', '783', '310118', '3');
INSERT INTO `tao_region` VALUES ('798', '奉贤区', '783', '310120', '3');
INSERT INTO `tao_region` VALUES ('799', '崇明区', '783', '310151', '3');
INSERT INTO `tao_region` VALUES ('800', '江苏省', '0', '320000', '1');
INSERT INTO `tao_region` VALUES ('801', '南京市', '800', '320100', '2');
INSERT INTO `tao_region` VALUES ('802', '玄武区', '801', '320102', '3');
INSERT INTO `tao_region` VALUES ('803', '秦淮区', '801', '320104', '3');
INSERT INTO `tao_region` VALUES ('804', '建邺区', '801', '320105', '3');
INSERT INTO `tao_region` VALUES ('805', '鼓楼区', '801', '320106', '3');
INSERT INTO `tao_region` VALUES ('806', '浦口区', '801', '320111', '3');
INSERT INTO `tao_region` VALUES ('807', '栖霞区', '801', '320113', '3');
INSERT INTO `tao_region` VALUES ('808', '雨花台区', '801', '320114', '3');
INSERT INTO `tao_region` VALUES ('809', '江宁区', '801', '320115', '3');
INSERT INTO `tao_region` VALUES ('810', '六合区', '801', '320116', '3');
INSERT INTO `tao_region` VALUES ('811', '溧水区', '801', '320117', '3');
INSERT INTO `tao_region` VALUES ('812', '高淳区', '801', '320118', '3');
INSERT INTO `tao_region` VALUES ('813', '无锡市', '800', '320200', '2');
INSERT INTO `tao_region` VALUES ('814', '锡山区', '813', '320205', '3');
INSERT INTO `tao_region` VALUES ('815', '惠山区', '813', '320206', '3');
INSERT INTO `tao_region` VALUES ('816', '滨湖区', '813', '320211', '3');
INSERT INTO `tao_region` VALUES ('817', '梁溪区', '813', '320213', '3');
INSERT INTO `tao_region` VALUES ('818', '新吴区', '813', '320214', '3');
INSERT INTO `tao_region` VALUES ('819', '江阴市', '813', '320281', '3');
INSERT INTO `tao_region` VALUES ('820', '宜兴市', '813', '320282', '3');
INSERT INTO `tao_region` VALUES ('821', '徐州市', '800', '320300', '2');
INSERT INTO `tao_region` VALUES ('822', '鼓楼区', '821', '320302', '3');
INSERT INTO `tao_region` VALUES ('823', '云龙区', '821', '320303', '3');
INSERT INTO `tao_region` VALUES ('824', '贾汪区', '821', '320305', '3');
INSERT INTO `tao_region` VALUES ('825', '泉山区', '821', '320311', '3');
INSERT INTO `tao_region` VALUES ('826', '铜山区', '821', '320312', '3');
INSERT INTO `tao_region` VALUES ('827', '丰县', '821', '320321', '3');
INSERT INTO `tao_region` VALUES ('828', '沛县', '821', '320322', '3');
INSERT INTO `tao_region` VALUES ('829', '睢宁县', '821', '320324', '3');
INSERT INTO `tao_region` VALUES ('830', '新沂市', '821', '320381', '3');
INSERT INTO `tao_region` VALUES ('831', '邳州市', '821', '320382', '3');
INSERT INTO `tao_region` VALUES ('832', '常州市', '800', '320400', '2');
INSERT INTO `tao_region` VALUES ('833', '天宁区', '832', '320402', '3');
INSERT INTO `tao_region` VALUES ('834', '钟楼区', '832', '320404', '3');
INSERT INTO `tao_region` VALUES ('835', '新北区', '832', '320411', '3');
INSERT INTO `tao_region` VALUES ('836', '武进区', '832', '320412', '3');
INSERT INTO `tao_region` VALUES ('837', '金坛区', '832', '320413', '3');
INSERT INTO `tao_region` VALUES ('838', '溧阳市', '832', '320481', '3');
INSERT INTO `tao_region` VALUES ('839', '苏州市', '800', '320500', '2');
INSERT INTO `tao_region` VALUES ('840', '虎丘区', '839', '320505', '3');
INSERT INTO `tao_region` VALUES ('841', '吴中区', '839', '320506', '3');
INSERT INTO `tao_region` VALUES ('842', '相城区', '839', '320507', '3');
INSERT INTO `tao_region` VALUES ('843', '姑苏区', '839', '320508', '3');
INSERT INTO `tao_region` VALUES ('844', '吴江区', '839', '320509', '3');
INSERT INTO `tao_region` VALUES ('845', '常熟市', '839', '320581', '3');
INSERT INTO `tao_region` VALUES ('846', '张家港市', '839', '320582', '3');
INSERT INTO `tao_region` VALUES ('847', '昆山市', '839', '320583', '3');
INSERT INTO `tao_region` VALUES ('848', '太仓市', '839', '320585', '3');
INSERT INTO `tao_region` VALUES ('849', '南通市', '800', '320600', '2');
INSERT INTO `tao_region` VALUES ('852', '通州区', '849', '320612', '3');
INSERT INTO `tao_region` VALUES ('853', '如东县', '849', '320623', '3');
INSERT INTO `tao_region` VALUES ('854', '启东市', '849', '320681', '3');
INSERT INTO `tao_region` VALUES ('855', '如皋市', '849', '320682', '3');
INSERT INTO `tao_region` VALUES ('857', '海安市', '849', '320685', '3');
INSERT INTO `tao_region` VALUES ('858', '连云港市', '800', '320700', '2');
INSERT INTO `tao_region` VALUES ('859', '连云区', '858', '320703', '3');
INSERT INTO `tao_region` VALUES ('860', '海州区', '858', '320706', '3');
INSERT INTO `tao_region` VALUES ('861', '赣榆区', '858', '320707', '3');
INSERT INTO `tao_region` VALUES ('862', '东海县', '858', '320722', '3');
INSERT INTO `tao_region` VALUES ('863', '灌云县', '858', '320723', '3');
INSERT INTO `tao_region` VALUES ('864', '灌南县', '858', '320724', '3');
INSERT INTO `tao_region` VALUES ('865', '淮安市', '800', '320800', '2');
INSERT INTO `tao_region` VALUES ('866', '淮安区', '865', '320803', '3');
INSERT INTO `tao_region` VALUES ('867', '淮阴区', '865', '320804', '3');
INSERT INTO `tao_region` VALUES ('868', '清江浦区', '865', '320812', '3');
INSERT INTO `tao_region` VALUES ('869', '洪泽区', '865', '320813', '3');
INSERT INTO `tao_region` VALUES ('870', '涟水县', '865', '320826', '3');
INSERT INTO `tao_region` VALUES ('871', '盱眙县', '865', '320830', '3');
INSERT INTO `tao_region` VALUES ('872', '金湖县', '865', '320831', '3');
INSERT INTO `tao_region` VALUES ('873', '盐城市', '800', '320900', '2');
INSERT INTO `tao_region` VALUES ('874', '亭湖区', '873', '320902', '3');
INSERT INTO `tao_region` VALUES ('875', '盐都区', '873', '320903', '3');
INSERT INTO `tao_region` VALUES ('876', '大丰区', '873', '320904', '3');
INSERT INTO `tao_region` VALUES ('877', '响水县', '873', '320921', '3');
INSERT INTO `tao_region` VALUES ('878', '滨海县', '873', '320922', '3');
INSERT INTO `tao_region` VALUES ('879', '阜宁县', '873', '320923', '3');
INSERT INTO `tao_region` VALUES ('880', '射阳县', '873', '320924', '3');
INSERT INTO `tao_region` VALUES ('881', '建湖县', '873', '320925', '3');
INSERT INTO `tao_region` VALUES ('882', '东台市', '873', '320981', '3');
INSERT INTO `tao_region` VALUES ('883', '扬州市', '800', '321000', '2');
INSERT INTO `tao_region` VALUES ('884', '广陵区', '883', '321002', '3');
INSERT INTO `tao_region` VALUES ('885', '邗江区', '883', '321003', '3');
INSERT INTO `tao_region` VALUES ('886', '江都区', '883', '321012', '3');
INSERT INTO `tao_region` VALUES ('887', '宝应县', '883', '321023', '3');
INSERT INTO `tao_region` VALUES ('888', '仪征市', '883', '321081', '3');
INSERT INTO `tao_region` VALUES ('889', '高邮市', '883', '321084', '3');
INSERT INTO `tao_region` VALUES ('890', '镇江市', '800', '321100', '2');
INSERT INTO `tao_region` VALUES ('891', '京口区', '890', '321102', '3');
INSERT INTO `tao_region` VALUES ('892', '润州区', '890', '321111', '3');
INSERT INTO `tao_region` VALUES ('893', '丹徒区', '890', '321112', '3');
INSERT INTO `tao_region` VALUES ('894', '丹阳市', '890', '321181', '3');
INSERT INTO `tao_region` VALUES ('895', '扬中市', '890', '321182', '3');
INSERT INTO `tao_region` VALUES ('896', '句容市', '890', '321183', '3');
INSERT INTO `tao_region` VALUES ('897', '泰州市', '800', '321200', '2');
INSERT INTO `tao_region` VALUES ('898', '海陵区', '897', '321202', '3');
INSERT INTO `tao_region` VALUES ('899', '高港区', '897', '321203', '3');
INSERT INTO `tao_region` VALUES ('900', '姜堰区', '897', '321204', '3');
INSERT INTO `tao_region` VALUES ('901', '兴化市', '897', '321281', '3');
INSERT INTO `tao_region` VALUES ('902', '靖江市', '897', '321282', '3');
INSERT INTO `tao_region` VALUES ('903', '泰兴市', '897', '321283', '3');
INSERT INTO `tao_region` VALUES ('904', '宿迁市', '800', '321300', '2');
INSERT INTO `tao_region` VALUES ('905', '宿城区', '904', '321302', '3');
INSERT INTO `tao_region` VALUES ('906', '宿豫区', '904', '321311', '3');
INSERT INTO `tao_region` VALUES ('907', '沭阳县', '904', '321322', '3');
INSERT INTO `tao_region` VALUES ('908', '泗阳县', '904', '321323', '3');
INSERT INTO `tao_region` VALUES ('909', '泗洪县', '904', '321324', '3');
INSERT INTO `tao_region` VALUES ('910', '浙江省', '0', '330000', '1');
INSERT INTO `tao_region` VALUES ('911', '杭州市', '910', '330100', '2');
INSERT INTO `tao_region` VALUES ('912', '上城区', '911', '330102', '3');
INSERT INTO `tao_region` VALUES ('915', '拱墅区', '911', '330105', '3');
INSERT INTO `tao_region` VALUES ('916', '西湖区', '911', '330106', '3');
INSERT INTO `tao_region` VALUES ('917', '滨江区', '911', '330108', '3');
INSERT INTO `tao_region` VALUES ('918', '萧山区', '911', '330109', '3');
INSERT INTO `tao_region` VALUES ('919', '余杭区', '911', '330110', '3');
INSERT INTO `tao_region` VALUES ('920', '富阳区', '911', '330111', '3');
INSERT INTO `tao_region` VALUES ('921', '临安区', '911', '330112', '3');
INSERT INTO `tao_region` VALUES ('922', '桐庐县', '911', '330122', '3');
INSERT INTO `tao_region` VALUES ('923', '淳安县', '911', '330127', '3');
INSERT INTO `tao_region` VALUES ('924', '建德市', '911', '330182', '3');
INSERT INTO `tao_region` VALUES ('925', '宁波市', '910', '330200', '2');
INSERT INTO `tao_region` VALUES ('926', '海曙区', '925', '330203', '3');
INSERT INTO `tao_region` VALUES ('927', '江北区', '925', '330205', '3');
INSERT INTO `tao_region` VALUES ('928', '北仑区', '925', '330206', '3');
INSERT INTO `tao_region` VALUES ('929', '镇海区', '925', '330211', '3');
INSERT INTO `tao_region` VALUES ('930', '鄞州区', '925', '330212', '3');
INSERT INTO `tao_region` VALUES ('931', '奉化区', '925', '330213', '3');
INSERT INTO `tao_region` VALUES ('932', '象山县', '925', '330225', '3');
INSERT INTO `tao_region` VALUES ('933', '宁海县', '925', '330226', '3');
INSERT INTO `tao_region` VALUES ('934', '余姚市', '925', '330281', '3');
INSERT INTO `tao_region` VALUES ('935', '慈溪市', '925', '330282', '3');
INSERT INTO `tao_region` VALUES ('936', '温州市', '910', '330300', '2');
INSERT INTO `tao_region` VALUES ('937', '鹿城区', '936', '330302', '3');
INSERT INTO `tao_region` VALUES ('938', '龙湾区', '936', '330303', '3');
INSERT INTO `tao_region` VALUES ('939', '瓯海区', '936', '330304', '3');
INSERT INTO `tao_region` VALUES ('940', '洞头区', '936', '330305', '3');
INSERT INTO `tao_region` VALUES ('941', '永嘉县', '936', '330324', '3');
INSERT INTO `tao_region` VALUES ('942', '平阳县', '936', '330326', '3');
INSERT INTO `tao_region` VALUES ('943', '苍南县', '936', '330327', '3');
INSERT INTO `tao_region` VALUES ('944', '文成县', '936', '330328', '3');
INSERT INTO `tao_region` VALUES ('945', '泰顺县', '936', '330329', '3');
INSERT INTO `tao_region` VALUES ('946', '瑞安市', '936', '330381', '3');
INSERT INTO `tao_region` VALUES ('947', '乐清市', '936', '330382', '3');
INSERT INTO `tao_region` VALUES ('948', '龙港市', '936', '330383', '3');
INSERT INTO `tao_region` VALUES ('949', '嘉兴市', '910', '330400', '2');
INSERT INTO `tao_region` VALUES ('950', '南湖区', '949', '330402', '3');
INSERT INTO `tao_region` VALUES ('951', '秀洲区', '949', '330411', '3');
INSERT INTO `tao_region` VALUES ('952', '嘉善县', '949', '330421', '3');
INSERT INTO `tao_region` VALUES ('953', '海盐县', '949', '330424', '3');
INSERT INTO `tao_region` VALUES ('954', '海宁市', '949', '330481', '3');
INSERT INTO `tao_region` VALUES ('955', '平湖市', '949', '330482', '3');
INSERT INTO `tao_region` VALUES ('956', '桐乡市', '949', '330483', '3');
INSERT INTO `tao_region` VALUES ('957', '湖州市', '910', '330500', '2');
INSERT INTO `tao_region` VALUES ('958', '吴兴区', '957', '330502', '3');
INSERT INTO `tao_region` VALUES ('959', '南浔区', '957', '330503', '3');
INSERT INTO `tao_region` VALUES ('960', '德清县', '957', '330521', '3');
INSERT INTO `tao_region` VALUES ('961', '长兴县', '957', '330522', '3');
INSERT INTO `tao_region` VALUES ('962', '安吉县', '957', '330523', '3');
INSERT INTO `tao_region` VALUES ('963', '绍兴市', '910', '330600', '2');
INSERT INTO `tao_region` VALUES ('964', '越城区', '963', '330602', '3');
INSERT INTO `tao_region` VALUES ('965', '柯桥区', '963', '330603', '3');
INSERT INTO `tao_region` VALUES ('966', '上虞区', '963', '330604', '3');
INSERT INTO `tao_region` VALUES ('967', '新昌县', '963', '330624', '3');
INSERT INTO `tao_region` VALUES ('968', '诸暨市', '963', '330681', '3');
INSERT INTO `tao_region` VALUES ('969', '嵊州市', '963', '330683', '3');
INSERT INTO `tao_region` VALUES ('970', '金华市', '910', '330700', '2');
INSERT INTO `tao_region` VALUES ('971', '婺城区', '970', '330702', '3');
INSERT INTO `tao_region` VALUES ('972', '金东区', '970', '330703', '3');
INSERT INTO `tao_region` VALUES ('973', '武义县', '970', '330723', '3');
INSERT INTO `tao_region` VALUES ('974', '浦江县', '970', '330726', '3');
INSERT INTO `tao_region` VALUES ('975', '磐安县', '970', '330727', '3');
INSERT INTO `tao_region` VALUES ('976', '兰溪市', '970', '330781', '3');
INSERT INTO `tao_region` VALUES ('977', '义乌市', '970', '330782', '3');
INSERT INTO `tao_region` VALUES ('978', '东阳市', '970', '330783', '3');
INSERT INTO `tao_region` VALUES ('979', '永康市', '970', '330784', '3');
INSERT INTO `tao_region` VALUES ('980', '衢州市', '910', '330800', '2');
INSERT INTO `tao_region` VALUES ('981', '柯城区', '980', '330802', '3');
INSERT INTO `tao_region` VALUES ('982', '衢江区', '980', '330803', '3');
INSERT INTO `tao_region` VALUES ('983', '常山县', '980', '330822', '3');
INSERT INTO `tao_region` VALUES ('984', '开化县', '980', '330824', '3');
INSERT INTO `tao_region` VALUES ('985', '龙游县', '980', '330825', '3');
INSERT INTO `tao_region` VALUES ('986', '江山市', '980', '330881', '3');
INSERT INTO `tao_region` VALUES ('987', '舟山市', '910', '330900', '2');
INSERT INTO `tao_region` VALUES ('988', '定海区', '987', '330902', '3');
INSERT INTO `tao_region` VALUES ('989', '普陀区', '987', '330903', '3');
INSERT INTO `tao_region` VALUES ('990', '岱山县', '987', '330921', '3');
INSERT INTO `tao_region` VALUES ('991', '嵊泗县', '987', '330922', '3');
INSERT INTO `tao_region` VALUES ('992', '台州市', '910', '331000', '2');
INSERT INTO `tao_region` VALUES ('993', '椒江区', '992', '331002', '3');
INSERT INTO `tao_region` VALUES ('994', '黄岩区', '992', '331003', '3');
INSERT INTO `tao_region` VALUES ('995', '路桥区', '992', '331004', '3');
INSERT INTO `tao_region` VALUES ('996', '三门县', '992', '331022', '3');
INSERT INTO `tao_region` VALUES ('997', '天台县', '992', '331023', '3');
INSERT INTO `tao_region` VALUES ('998', '仙居县', '992', '331024', '3');
INSERT INTO `tao_region` VALUES ('999', '温岭市', '992', '331081', '3');
INSERT INTO `tao_region` VALUES ('1000', '临海市', '992', '331082', '3');
INSERT INTO `tao_region` VALUES ('1001', '玉环市', '992', '331083', '3');
INSERT INTO `tao_region` VALUES ('1002', '丽水市', '910', '331100', '2');
INSERT INTO `tao_region` VALUES ('1003', '莲都区', '1002', '331102', '3');
INSERT INTO `tao_region` VALUES ('1004', '青田县', '1002', '331121', '3');
INSERT INTO `tao_region` VALUES ('1005', '缙云县', '1002', '331122', '3');
INSERT INTO `tao_region` VALUES ('1006', '遂昌县', '1002', '331123', '3');
INSERT INTO `tao_region` VALUES ('1007', '松阳县', '1002', '331124', '3');
INSERT INTO `tao_region` VALUES ('1008', '云和县', '1002', '331125', '3');
INSERT INTO `tao_region` VALUES ('1009', '庆元县', '1002', '331126', '3');
INSERT INTO `tao_region` VALUES ('1010', '景宁畲族自治县', '1002', '331127', '3');
INSERT INTO `tao_region` VALUES ('1011', '龙泉市', '1002', '331181', '3');
INSERT INTO `tao_region` VALUES ('1012', '安徽省', '0', '340000', '1');
INSERT INTO `tao_region` VALUES ('1013', '合肥市', '1012', '340100', '2');
INSERT INTO `tao_region` VALUES ('1014', '瑶海区', '1013', '340102', '3');
INSERT INTO `tao_region` VALUES ('1015', '庐阳区', '1013', '340103', '3');
INSERT INTO `tao_region` VALUES ('1016', '蜀山区', '1013', '340104', '3');
INSERT INTO `tao_region` VALUES ('1017', '包河区', '1013', '340111', '3');
INSERT INTO `tao_region` VALUES ('1018', '长丰县', '1013', '340121', '3');
INSERT INTO `tao_region` VALUES ('1019', '肥东县', '1013', '340122', '3');
INSERT INTO `tao_region` VALUES ('1020', '肥西县', '1013', '340123', '3');
INSERT INTO `tao_region` VALUES ('1021', '庐江县', '1013', '340124', '3');
INSERT INTO `tao_region` VALUES ('1022', '巢湖市', '1013', '340181', '3');
INSERT INTO `tao_region` VALUES ('1023', '芜湖市', '1012', '340200', '2');
INSERT INTO `tao_region` VALUES ('1024', '镜湖区', '1023', '340202', '3');
INSERT INTO `tao_region` VALUES ('1026', '鸠江区', '1023', '340207', '3');
INSERT INTO `tao_region` VALUES ('1030', '南陵县', '1023', '340223', '3');
INSERT INTO `tao_region` VALUES ('1031', '无为市', '1023', '340281', '3');
INSERT INTO `tao_region` VALUES ('1032', '蚌埠市', '1012', '340300', '2');
INSERT INTO `tao_region` VALUES ('1033', '龙子湖区', '1032', '340302', '3');
INSERT INTO `tao_region` VALUES ('1034', '蚌山区', '1032', '340303', '3');
INSERT INTO `tao_region` VALUES ('1035', '禹会区', '1032', '340304', '3');
INSERT INTO `tao_region` VALUES ('1036', '淮上区', '1032', '340311', '3');
INSERT INTO `tao_region` VALUES ('1037', '怀远县', '1032', '340321', '3');
INSERT INTO `tao_region` VALUES ('1038', '五河县', '1032', '340322', '3');
INSERT INTO `tao_region` VALUES ('1039', '固镇县', '1032', '340323', '3');
INSERT INTO `tao_region` VALUES ('1040', '淮南市', '1012', '340400', '2');
INSERT INTO `tao_region` VALUES ('1041', '大通区', '1040', '340402', '3');
INSERT INTO `tao_region` VALUES ('1042', '田家庵区', '1040', '340403', '3');
INSERT INTO `tao_region` VALUES ('1043', '谢家集区', '1040', '340404', '3');
INSERT INTO `tao_region` VALUES ('1044', '八公山区', '1040', '340405', '3');
INSERT INTO `tao_region` VALUES ('1045', '潘集区', '1040', '340406', '3');
INSERT INTO `tao_region` VALUES ('1046', '凤台县', '1040', '340421', '3');
INSERT INTO `tao_region` VALUES ('1047', '寿县', '1040', '340422', '3');
INSERT INTO `tao_region` VALUES ('1048', '马鞍山市', '1012', '340500', '2');
INSERT INTO `tao_region` VALUES ('1049', '花山区', '1048', '340503', '3');
INSERT INTO `tao_region` VALUES ('1050', '雨山区', '1048', '340504', '3');
INSERT INTO `tao_region` VALUES ('1051', '博望区', '1048', '340506', '3');
INSERT INTO `tao_region` VALUES ('1052', '当涂县', '1048', '340521', '3');
INSERT INTO `tao_region` VALUES ('1053', '含山县', '1048', '340522', '3');
INSERT INTO `tao_region` VALUES ('1054', '和县', '1048', '340523', '3');
INSERT INTO `tao_region` VALUES ('1055', '淮北市', '1012', '340600', '2');
INSERT INTO `tao_region` VALUES ('1056', '杜集区', '1055', '340602', '3');
INSERT INTO `tao_region` VALUES ('1057', '相山区', '1055', '340603', '3');
INSERT INTO `tao_region` VALUES ('1058', '烈山区', '1055', '340604', '3');
INSERT INTO `tao_region` VALUES ('1059', '濉溪县', '1055', '340621', '3');
INSERT INTO `tao_region` VALUES ('1060', '铜陵市', '1012', '340700', '2');
INSERT INTO `tao_region` VALUES ('1061', '铜官区', '1060', '340705', '3');
INSERT INTO `tao_region` VALUES ('1062', '义安区', '1060', '340706', '3');
INSERT INTO `tao_region` VALUES ('1063', '郊区', '1060', '340711', '3');
INSERT INTO `tao_region` VALUES ('1064', '枞阳县', '1060', '340722', '3');
INSERT INTO `tao_region` VALUES ('1065', '安庆市', '1012', '340800', '2');
INSERT INTO `tao_region` VALUES ('1066', '迎江区', '1065', '340802', '3');
INSERT INTO `tao_region` VALUES ('1067', '大观区', '1065', '340803', '3');
INSERT INTO `tao_region` VALUES ('1068', '宜秀区', '1065', '340811', '3');
INSERT INTO `tao_region` VALUES ('1069', '怀宁县', '1065', '340822', '3');
INSERT INTO `tao_region` VALUES ('1070', '太湖县', '1065', '340825', '3');
INSERT INTO `tao_region` VALUES ('1071', '宿松县', '1065', '340826', '3');
INSERT INTO `tao_region` VALUES ('1072', '望江县', '1065', '340827', '3');
INSERT INTO `tao_region` VALUES ('1073', '岳西县', '1065', '340828', '3');
INSERT INTO `tao_region` VALUES ('1074', '桐城市', '1065', '340881', '3');
INSERT INTO `tao_region` VALUES ('1075', '潜山市', '1065', '340882', '3');
INSERT INTO `tao_region` VALUES ('1076', '黄山市', '1012', '341000', '2');
INSERT INTO `tao_region` VALUES ('1077', '屯溪区', '1076', '341002', '3');
INSERT INTO `tao_region` VALUES ('1078', '黄山区', '1076', '341003', '3');
INSERT INTO `tao_region` VALUES ('1079', '徽州区', '1076', '341004', '3');
INSERT INTO `tao_region` VALUES ('1080', '歙县', '1076', '341021', '3');
INSERT INTO `tao_region` VALUES ('1081', '休宁县', '1076', '341022', '3');
INSERT INTO `tao_region` VALUES ('1082', '黟县', '1076', '341023', '3');
INSERT INTO `tao_region` VALUES ('1083', '祁门县', '1076', '341024', '3');
INSERT INTO `tao_region` VALUES ('1084', '滁州市', '1012', '341100', '2');
INSERT INTO `tao_region` VALUES ('1085', '琅琊区', '1084', '341102', '3');
INSERT INTO `tao_region` VALUES ('1086', '南谯区', '1084', '341103', '3');
INSERT INTO `tao_region` VALUES ('1087', '来安县', '1084', '341122', '3');
INSERT INTO `tao_region` VALUES ('1088', '全椒县', '1084', '341124', '3');
INSERT INTO `tao_region` VALUES ('1089', '定远县', '1084', '341125', '3');
INSERT INTO `tao_region` VALUES ('1090', '凤阳县', '1084', '341126', '3');
INSERT INTO `tao_region` VALUES ('1091', '天长市', '1084', '341181', '3');
INSERT INTO `tao_region` VALUES ('1092', '明光市', '1084', '341182', '3');
INSERT INTO `tao_region` VALUES ('1093', '阜阳市', '1012', '341200', '2');
INSERT INTO `tao_region` VALUES ('1094', '颍州区', '1093', '341202', '3');
INSERT INTO `tao_region` VALUES ('1095', '颍东区', '1093', '341203', '3');
INSERT INTO `tao_region` VALUES ('1096', '颍泉区', '1093', '341204', '3');
INSERT INTO `tao_region` VALUES ('1097', '临泉县', '1093', '341221', '3');
INSERT INTO `tao_region` VALUES ('1098', '太和县', '1093', '341222', '3');
INSERT INTO `tao_region` VALUES ('1099', '阜南县', '1093', '341225', '3');
INSERT INTO `tao_region` VALUES ('1100', '颍上县', '1093', '341226', '3');
INSERT INTO `tao_region` VALUES ('1101', '界首市', '1093', '341282', '3');
INSERT INTO `tao_region` VALUES ('1102', '宿州市', '1012', '341300', '2');
INSERT INTO `tao_region` VALUES ('1103', '埇桥区', '1102', '341302', '3');
INSERT INTO `tao_region` VALUES ('1104', '砀山县', '1102', '341321', '3');
INSERT INTO `tao_region` VALUES ('1105', '萧县', '1102', '341322', '3');
INSERT INTO `tao_region` VALUES ('1106', '灵璧县', '1102', '341323', '3');
INSERT INTO `tao_region` VALUES ('1107', '泗县', '1102', '341324', '3');
INSERT INTO `tao_region` VALUES ('1108', '六安市', '1012', '341500', '2');
INSERT INTO `tao_region` VALUES ('1109', '金安区', '1108', '341502', '3');
INSERT INTO `tao_region` VALUES ('1110', '裕安区', '1108', '341503', '3');
INSERT INTO `tao_region` VALUES ('1111', '叶集区', '1108', '341504', '3');
INSERT INTO `tao_region` VALUES ('1112', '霍邱县', '1108', '341522', '3');
INSERT INTO `tao_region` VALUES ('1113', '舒城县', '1108', '341523', '3');
INSERT INTO `tao_region` VALUES ('1114', '金寨县', '1108', '341524', '3');
INSERT INTO `tao_region` VALUES ('1115', '霍山县', '1108', '341525', '3');
INSERT INTO `tao_region` VALUES ('1116', '亳州市', '1012', '341600', '2');
INSERT INTO `tao_region` VALUES ('1117', '谯城区', '1116', '341602', '3');
INSERT INTO `tao_region` VALUES ('1118', '涡阳县', '1116', '341621', '3');
INSERT INTO `tao_region` VALUES ('1119', '蒙城县', '1116', '341622', '3');
INSERT INTO `tao_region` VALUES ('1120', '利辛县', '1116', '341623', '3');
INSERT INTO `tao_region` VALUES ('1121', '池州市', '1012', '341700', '2');
INSERT INTO `tao_region` VALUES ('1122', '贵池区', '1121', '341702', '3');
INSERT INTO `tao_region` VALUES ('1123', '东至县', '1121', '341721', '3');
INSERT INTO `tao_region` VALUES ('1124', '石台县', '1121', '341722', '3');
INSERT INTO `tao_region` VALUES ('1125', '青阳县', '1121', '341723', '3');
INSERT INTO `tao_region` VALUES ('1126', '宣城市', '1012', '341800', '2');
INSERT INTO `tao_region` VALUES ('1127', '宣州区', '1126', '341802', '3');
INSERT INTO `tao_region` VALUES ('1128', '郎溪县', '1126', '341821', '3');
INSERT INTO `tao_region` VALUES ('1129', '泾县', '1126', '341823', '3');
INSERT INTO `tao_region` VALUES ('1130', '绩溪县', '1126', '341824', '3');
INSERT INTO `tao_region` VALUES ('1131', '旌德县', '1126', '341825', '3');
INSERT INTO `tao_region` VALUES ('1132', '宁国市', '1126', '341881', '3');
INSERT INTO `tao_region` VALUES ('1133', '广德市', '1126', '341882', '3');
INSERT INTO `tao_region` VALUES ('1134', '福建省', '0', '350000', '1');
INSERT INTO `tao_region` VALUES ('1135', '福州市', '1134', '350100', '2');
INSERT INTO `tao_region` VALUES ('1136', '鼓楼区', '1135', '350102', '3');
INSERT INTO `tao_region` VALUES ('1137', '台江区', '1135', '350103', '3');
INSERT INTO `tao_region` VALUES ('1138', '仓山区', '1135', '350104', '3');
INSERT INTO `tao_region` VALUES ('1139', '马尾区', '1135', '350105', '3');
INSERT INTO `tao_region` VALUES ('1140', '晋安区', '1135', '350111', '3');
INSERT INTO `tao_region` VALUES ('1141', '长乐区', '1135', '350112', '3');
INSERT INTO `tao_region` VALUES ('1142', '闽侯县', '1135', '350121', '3');
INSERT INTO `tao_region` VALUES ('1143', '连江县', '1135', '350122', '3');
INSERT INTO `tao_region` VALUES ('1144', '罗源县', '1135', '350123', '3');
INSERT INTO `tao_region` VALUES ('1145', '闽清县', '1135', '350124', '3');
INSERT INTO `tao_region` VALUES ('1146', '永泰县', '1135', '350125', '3');
INSERT INTO `tao_region` VALUES ('1147', '平潭县', '1135', '350128', '3');
INSERT INTO `tao_region` VALUES ('1148', '福清市', '1135', '350181', '3');
INSERT INTO `tao_region` VALUES ('1149', '厦门市', '1134', '350200', '2');
INSERT INTO `tao_region` VALUES ('1150', '思明区', '1149', '350203', '3');
INSERT INTO `tao_region` VALUES ('1151', '海沧区', '1149', '350205', '3');
INSERT INTO `tao_region` VALUES ('1152', '湖里区', '1149', '350206', '3');
INSERT INTO `tao_region` VALUES ('1153', '集美区', '1149', '350211', '3');
INSERT INTO `tao_region` VALUES ('1154', '同安区', '1149', '350212', '3');
INSERT INTO `tao_region` VALUES ('1155', '翔安区', '1149', '350213', '3');
INSERT INTO `tao_region` VALUES ('1156', '莆田市', '1134', '350300', '2');
INSERT INTO `tao_region` VALUES ('1157', '城厢区', '1156', '350302', '3');
INSERT INTO `tao_region` VALUES ('1158', '涵江区', '1156', '350303', '3');
INSERT INTO `tao_region` VALUES ('1159', '荔城区', '1156', '350304', '3');
INSERT INTO `tao_region` VALUES ('1160', '秀屿区', '1156', '350305', '3');
INSERT INTO `tao_region` VALUES ('1161', '仙游县', '1156', '350322', '3');
INSERT INTO `tao_region` VALUES ('1162', '三明市', '1134', '350400', '2');
INSERT INTO `tao_region` VALUES ('1165', '明溪县', '1162', '350421', '3');
INSERT INTO `tao_region` VALUES ('1166', '清流县', '1162', '350423', '3');
INSERT INTO `tao_region` VALUES ('1167', '宁化县', '1162', '350424', '3');
INSERT INTO `tao_region` VALUES ('1168', '大田县', '1162', '350425', '3');
INSERT INTO `tao_region` VALUES ('1169', '尤溪县', '1162', '350426', '3');
INSERT INTO `tao_region` VALUES ('1171', '将乐县', '1162', '350428', '3');
INSERT INTO `tao_region` VALUES ('1172', '泰宁县', '1162', '350429', '3');
INSERT INTO `tao_region` VALUES ('1173', '建宁县', '1162', '350430', '3');
INSERT INTO `tao_region` VALUES ('1174', '永安市', '1162', '350481', '3');
INSERT INTO `tao_region` VALUES ('1175', '泉州市', '1134', '350500', '2');
INSERT INTO `tao_region` VALUES ('1176', '鲤城区', '1175', '350502', '3');
INSERT INTO `tao_region` VALUES ('1177', '丰泽区', '1175', '350503', '3');
INSERT INTO `tao_region` VALUES ('1178', '洛江区', '1175', '350504', '3');
INSERT INTO `tao_region` VALUES ('1179', '泉港区', '1175', '350505', '3');
INSERT INTO `tao_region` VALUES ('1180', '惠安县', '1175', '350521', '3');
INSERT INTO `tao_region` VALUES ('1181', '安溪县', '1175', '350524', '3');
INSERT INTO `tao_region` VALUES ('1182', '永春县', '1175', '350525', '3');
INSERT INTO `tao_region` VALUES ('1183', '德化县', '1175', '350526', '3');
INSERT INTO `tao_region` VALUES ('1185', '石狮市', '1175', '350581', '3');
INSERT INTO `tao_region` VALUES ('1186', '晋江市', '1175', '350582', '3');
INSERT INTO `tao_region` VALUES ('1187', '南安市', '1175', '350583', '3');
INSERT INTO `tao_region` VALUES ('1188', '漳州市', '1134', '350600', '2');
INSERT INTO `tao_region` VALUES ('1189', '芗城区', '1188', '350602', '3');
INSERT INTO `tao_region` VALUES ('1190', '龙文区', '1188', '350603', '3');
INSERT INTO `tao_region` VALUES ('1191', '云霄县', '1188', '350622', '3');
INSERT INTO `tao_region` VALUES ('1192', '漳浦县', '1188', '350623', '3');
INSERT INTO `tao_region` VALUES ('1193', '诏安县', '1188', '350624', '3');
INSERT INTO `tao_region` VALUES ('1195', '东山县', '1188', '350626', '3');
INSERT INTO `tao_region` VALUES ('1196', '南靖县', '1188', '350627', '3');
INSERT INTO `tao_region` VALUES ('1197', '平和县', '1188', '350628', '3');
INSERT INTO `tao_region` VALUES ('1198', '华安县', '1188', '350629', '3');
INSERT INTO `tao_region` VALUES ('1200', '南平市', '1134', '350700', '2');
INSERT INTO `tao_region` VALUES ('1201', '延平区', '1200', '350702', '3');
INSERT INTO `tao_region` VALUES ('1202', '建阳区', '1200', '350703', '3');
INSERT INTO `tao_region` VALUES ('1203', '顺昌县', '1200', '350721', '3');
INSERT INTO `tao_region` VALUES ('1204', '浦城县', '1200', '350722', '3');
INSERT INTO `tao_region` VALUES ('1205', '光泽县', '1200', '350723', '3');
INSERT INTO `tao_region` VALUES ('1206', '松溪县', '1200', '350724', '3');
INSERT INTO `tao_region` VALUES ('1207', '政和县', '1200', '350725', '3');
INSERT INTO `tao_region` VALUES ('1208', '邵武市', '1200', '350781', '3');
INSERT INTO `tao_region` VALUES ('1209', '武夷山市', '1200', '350782', '3');
INSERT INTO `tao_region` VALUES ('1210', '建瓯市', '1200', '350783', '3');
INSERT INTO `tao_region` VALUES ('1211', '龙岩市', '1134', '350800', '2');
INSERT INTO `tao_region` VALUES ('1212', '新罗区', '1211', '350802', '3');
INSERT INTO `tao_region` VALUES ('1213', '永定区', '1211', '350803', '3');
INSERT INTO `tao_region` VALUES ('1214', '长汀县', '1211', '350821', '3');
INSERT INTO `tao_region` VALUES ('1215', '上杭县', '1211', '350823', '3');
INSERT INTO `tao_region` VALUES ('1216', '武平县', '1211', '350824', '3');
INSERT INTO `tao_region` VALUES ('1217', '连城县', '1211', '350825', '3');
INSERT INTO `tao_region` VALUES ('1218', '漳平市', '1211', '350881', '3');
INSERT INTO `tao_region` VALUES ('1219', '宁德市', '1134', '350900', '2');
INSERT INTO `tao_region` VALUES ('1220', '蕉城区', '1219', '350902', '3');
INSERT INTO `tao_region` VALUES ('1221', '霞浦县', '1219', '350921', '3');
INSERT INTO `tao_region` VALUES ('1222', '古田县', '1219', '350922', '3');
INSERT INTO `tao_region` VALUES ('1223', '屏南县', '1219', '350923', '3');
INSERT INTO `tao_region` VALUES ('1224', '寿宁县', '1219', '350924', '3');
INSERT INTO `tao_region` VALUES ('1225', '周宁县', '1219', '350925', '3');
INSERT INTO `tao_region` VALUES ('1226', '柘荣县', '1219', '350926', '3');
INSERT INTO `tao_region` VALUES ('1227', '福安市', '1219', '350981', '3');
INSERT INTO `tao_region` VALUES ('1228', '福鼎市', '1219', '350982', '3');
INSERT INTO `tao_region` VALUES ('1229', '江西省', '0', '360000', '1');
INSERT INTO `tao_region` VALUES ('1230', '南昌市', '1229', '360100', '2');
INSERT INTO `tao_region` VALUES ('1231', '东湖区', '1230', '360102', '3');
INSERT INTO `tao_region` VALUES ('1232', '西湖区', '1230', '360103', '3');
INSERT INTO `tao_region` VALUES ('1233', '青云谱区', '1230', '360104', '3');
INSERT INTO `tao_region` VALUES ('1234', '青山湖区', '1230', '360111', '3');
INSERT INTO `tao_region` VALUES ('1235', '新建区', '1230', '360112', '3');
INSERT INTO `tao_region` VALUES ('1236', '红谷滩区', '1230', '360113', '3');
INSERT INTO `tao_region` VALUES ('1237', '南昌县', '1230', '360121', '3');
INSERT INTO `tao_region` VALUES ('1238', '安义县', '1230', '360123', '3');
INSERT INTO `tao_region` VALUES ('1239', '进贤县', '1230', '360124', '3');
INSERT INTO `tao_region` VALUES ('1240', '景德镇市', '1229', '360200', '2');
INSERT INTO `tao_region` VALUES ('1241', '昌江区', '1240', '360202', '3');
INSERT INTO `tao_region` VALUES ('1242', '珠山区', '1240', '360203', '3');
INSERT INTO `tao_region` VALUES ('1243', '浮梁县', '1240', '360222', '3');
INSERT INTO `tao_region` VALUES ('1244', '乐平市', '1240', '360281', '3');
INSERT INTO `tao_region` VALUES ('1245', '萍乡市', '1229', '360300', '2');
INSERT INTO `tao_region` VALUES ('1246', '安源区', '1245', '360302', '3');
INSERT INTO `tao_region` VALUES ('1247', '湘东区', '1245', '360313', '3');
INSERT INTO `tao_region` VALUES ('1248', '莲花县', '1245', '360321', '3');
INSERT INTO `tao_region` VALUES ('1249', '上栗县', '1245', '360322', '3');
INSERT INTO `tao_region` VALUES ('1250', '芦溪县', '1245', '360323', '3');
INSERT INTO `tao_region` VALUES ('1251', '九江市', '1229', '360400', '2');
INSERT INTO `tao_region` VALUES ('1252', '濂溪区', '1251', '360402', '3');
INSERT INTO `tao_region` VALUES ('1253', '浔阳区', '1251', '360403', '3');
INSERT INTO `tao_region` VALUES ('1254', '柴桑区', '1251', '360404', '3');
INSERT INTO `tao_region` VALUES ('1255', '武宁县', '1251', '360423', '3');
INSERT INTO `tao_region` VALUES ('1256', '修水县', '1251', '360424', '3');
INSERT INTO `tao_region` VALUES ('1257', '永修县', '1251', '360425', '3');
INSERT INTO `tao_region` VALUES ('1258', '德安县', '1251', '360426', '3');
INSERT INTO `tao_region` VALUES ('1259', '都昌县', '1251', '360428', '3');
INSERT INTO `tao_region` VALUES ('1260', '湖口县', '1251', '360429', '3');
INSERT INTO `tao_region` VALUES ('1261', '彭泽县', '1251', '360430', '3');
INSERT INTO `tao_region` VALUES ('1262', '瑞昌市', '1251', '360481', '3');
INSERT INTO `tao_region` VALUES ('1263', '共青城市', '1251', '360482', '3');
INSERT INTO `tao_region` VALUES ('1264', '庐山市', '1251', '360483', '3');
INSERT INTO `tao_region` VALUES ('1265', '新余市', '1229', '360500', '2');
INSERT INTO `tao_region` VALUES ('1266', '渝水区', '1265', '360502', '3');
INSERT INTO `tao_region` VALUES ('1267', '分宜县', '1265', '360521', '3');
INSERT INTO `tao_region` VALUES ('1268', '鹰潭市', '1229', '360600', '2');
INSERT INTO `tao_region` VALUES ('1269', '月湖区', '1268', '360602', '3');
INSERT INTO `tao_region` VALUES ('1270', '余江区', '1268', '360603', '3');
INSERT INTO `tao_region` VALUES ('1271', '贵溪市', '1268', '360681', '3');
INSERT INTO `tao_region` VALUES ('1272', '赣州市', '1229', '360700', '2');
INSERT INTO `tao_region` VALUES ('1273', '章贡区', '1272', '360702', '3');
INSERT INTO `tao_region` VALUES ('1274', '南康区', '1272', '360703', '3');
INSERT INTO `tao_region` VALUES ('1275', '赣县区', '1272', '360704', '3');
INSERT INTO `tao_region` VALUES ('1276', '信丰县', '1272', '360722', '3');
INSERT INTO `tao_region` VALUES ('1277', '大余县', '1272', '360723', '3');
INSERT INTO `tao_region` VALUES ('1278', '上犹县', '1272', '360724', '3');
INSERT INTO `tao_region` VALUES ('1279', '崇义县', '1272', '360725', '3');
INSERT INTO `tao_region` VALUES ('1280', '安远县', '1272', '360726', '3');
INSERT INTO `tao_region` VALUES ('1282', '定南县', '1272', '360728', '3');
INSERT INTO `tao_region` VALUES ('1283', '全南县', '1272', '360729', '3');
INSERT INTO `tao_region` VALUES ('1284', '宁都县', '1272', '360730', '3');
INSERT INTO `tao_region` VALUES ('1285', '于都县', '1272', '360731', '3');
INSERT INTO `tao_region` VALUES ('1286', '兴国县', '1272', '360732', '3');
INSERT INTO `tao_region` VALUES ('1287', '会昌县', '1272', '360733', '3');
INSERT INTO `tao_region` VALUES ('1288', '寻乌县', '1272', '360734', '3');
INSERT INTO `tao_region` VALUES ('1289', '石城县', '1272', '360735', '3');
INSERT INTO `tao_region` VALUES ('1290', '瑞金市', '1272', '360781', '3');
INSERT INTO `tao_region` VALUES ('1291', '吉安市', '1229', '360800', '2');
INSERT INTO `tao_region` VALUES ('1292', '吉州区', '1291', '360802', '3');
INSERT INTO `tao_region` VALUES ('1293', '青原区', '1291', '360803', '3');
INSERT INTO `tao_region` VALUES ('1294', '吉安县', '1291', '360821', '3');
INSERT INTO `tao_region` VALUES ('1295', '吉水县', '1291', '360822', '3');
INSERT INTO `tao_region` VALUES ('1296', '峡江县', '1291', '360823', '3');
INSERT INTO `tao_region` VALUES ('1297', '新干县', '1291', '360824', '3');
INSERT INTO `tao_region` VALUES ('1298', '永丰县', '1291', '360825', '3');
INSERT INTO `tao_region` VALUES ('1299', '泰和县', '1291', '360826', '3');
INSERT INTO `tao_region` VALUES ('1300', '遂川县', '1291', '360827', '3');
INSERT INTO `tao_region` VALUES ('1301', '万安县', '1291', '360828', '3');
INSERT INTO `tao_region` VALUES ('1302', '安福县', '1291', '360829', '3');
INSERT INTO `tao_region` VALUES ('1303', '永新县', '1291', '360830', '3');
INSERT INTO `tao_region` VALUES ('1304', '井冈山市', '1291', '360881', '3');
INSERT INTO `tao_region` VALUES ('1305', '宜春市', '1229', '360900', '2');
INSERT INTO `tao_region` VALUES ('1306', '袁州区', '1305', '360902', '3');
INSERT INTO `tao_region` VALUES ('1307', '奉新县', '1305', '360921', '3');
INSERT INTO `tao_region` VALUES ('1308', '万载县', '1305', '360922', '3');
INSERT INTO `tao_region` VALUES ('1309', '上高县', '1305', '360923', '3');
INSERT INTO `tao_region` VALUES ('1310', '宜丰县', '1305', '360924', '3');
INSERT INTO `tao_region` VALUES ('1311', '靖安县', '1305', '360925', '3');
INSERT INTO `tao_region` VALUES ('1312', '铜鼓县', '1305', '360926', '3');
INSERT INTO `tao_region` VALUES ('1313', '丰城市', '1305', '360981', '3');
INSERT INTO `tao_region` VALUES ('1314', '樟树市', '1305', '360982', '3');
INSERT INTO `tao_region` VALUES ('1315', '高安市', '1305', '360983', '3');
INSERT INTO `tao_region` VALUES ('1316', '抚州市', '1229', '361000', '2');
INSERT INTO `tao_region` VALUES ('1317', '临川区', '1316', '361002', '3');
INSERT INTO `tao_region` VALUES ('1318', '东乡区', '1316', '361003', '3');
INSERT INTO `tao_region` VALUES ('1319', '南城县', '1316', '361021', '3');
INSERT INTO `tao_region` VALUES ('1320', '黎川县', '1316', '361022', '3');
INSERT INTO `tao_region` VALUES ('1321', '南丰县', '1316', '361023', '3');
INSERT INTO `tao_region` VALUES ('1322', '崇仁县', '1316', '361024', '3');
INSERT INTO `tao_region` VALUES ('1323', '乐安县', '1316', '361025', '3');
INSERT INTO `tao_region` VALUES ('1324', '宜黄县', '1316', '361026', '3');
INSERT INTO `tao_region` VALUES ('1325', '金溪县', '1316', '361027', '3');
INSERT INTO `tao_region` VALUES ('1326', '资溪县', '1316', '361028', '3');
INSERT INTO `tao_region` VALUES ('1327', '广昌县', '1316', '361030', '3');
INSERT INTO `tao_region` VALUES ('1328', '上饶市', '1229', '361100', '2');
INSERT INTO `tao_region` VALUES ('1329', '信州区', '1328', '361102', '3');
INSERT INTO `tao_region` VALUES ('1330', '广丰区', '1328', '361103', '3');
INSERT INTO `tao_region` VALUES ('1331', '广信区', '1328', '361104', '3');
INSERT INTO `tao_region` VALUES ('1332', '玉山县', '1328', '361123', '3');
INSERT INTO `tao_region` VALUES ('1333', '铅山县', '1328', '361124', '3');
INSERT INTO `tao_region` VALUES ('1334', '横峰县', '1328', '361125', '3');
INSERT INTO `tao_region` VALUES ('1335', '弋阳县', '1328', '361126', '3');
INSERT INTO `tao_region` VALUES ('1336', '余干县', '1328', '361127', '3');
INSERT INTO `tao_region` VALUES ('1337', '鄱阳县', '1328', '361128', '3');
INSERT INTO `tao_region` VALUES ('1338', '万年县', '1328', '361129', '3');
INSERT INTO `tao_region` VALUES ('1339', '婺源县', '1328', '361130', '3');
INSERT INTO `tao_region` VALUES ('1340', '德兴市', '1328', '361181', '3');
INSERT INTO `tao_region` VALUES ('1341', '山东省', '0', '370000', '1');
INSERT INTO `tao_region` VALUES ('1342', '济南市', '1341', '370100', '2');
INSERT INTO `tao_region` VALUES ('1343', '历下区', '1342', '370102', '3');
INSERT INTO `tao_region` VALUES ('1344', '市中区', '1342', '370103', '3');
INSERT INTO `tao_region` VALUES ('1345', '槐荫区', '1342', '370104', '3');
INSERT INTO `tao_region` VALUES ('1346', '天桥区', '1342', '370105', '3');
INSERT INTO `tao_region` VALUES ('1347', '历城区', '1342', '370112', '3');
INSERT INTO `tao_region` VALUES ('1348', '长清区', '1342', '370113', '3');
INSERT INTO `tao_region` VALUES ('1349', '章丘区', '1342', '370114', '3');
INSERT INTO `tao_region` VALUES ('1350', '济阳区', '1342', '370115', '3');
INSERT INTO `tao_region` VALUES ('1351', '莱芜区', '1342', '370116', '3');
INSERT INTO `tao_region` VALUES ('1352', '钢城区', '1342', '370117', '3');
INSERT INTO `tao_region` VALUES ('1353', '平阴县', '1342', '370124', '3');
INSERT INTO `tao_region` VALUES ('1354', '商河县', '1342', '370126', '3');
INSERT INTO `tao_region` VALUES ('1355', '青岛市', '1341', '370200', '2');
INSERT INTO `tao_region` VALUES ('1356', '市南区', '1355', '370202', '3');
INSERT INTO `tao_region` VALUES ('1357', '市北区', '1355', '370203', '3');
INSERT INTO `tao_region` VALUES ('1358', '黄岛区', '1355', '370211', '3');
INSERT INTO `tao_region` VALUES ('1359', '崂山区', '1355', '370212', '3');
INSERT INTO `tao_region` VALUES ('1360', '李沧区', '1355', '370213', '3');
INSERT INTO `tao_region` VALUES ('1361', '城阳区', '1355', '370214', '3');
INSERT INTO `tao_region` VALUES ('1362', '即墨区', '1355', '370215', '3');
INSERT INTO `tao_region` VALUES ('1363', '胶州市', '1355', '370281', '3');
INSERT INTO `tao_region` VALUES ('1364', '平度市', '1355', '370283', '3');
INSERT INTO `tao_region` VALUES ('1365', '莱西市', '1355', '370285', '3');
INSERT INTO `tao_region` VALUES ('1366', '淄博市', '1341', '370300', '2');
INSERT INTO `tao_region` VALUES ('1367', '淄川区', '1366', '370302', '3');
INSERT INTO `tao_region` VALUES ('1368', '张店区', '1366', '370303', '3');
INSERT INTO `tao_region` VALUES ('1369', '博山区', '1366', '370304', '3');
INSERT INTO `tao_region` VALUES ('1370', '临淄区', '1366', '370305', '3');
INSERT INTO `tao_region` VALUES ('1371', '周村区', '1366', '370306', '3');
INSERT INTO `tao_region` VALUES ('1372', '桓台县', '1366', '370321', '3');
INSERT INTO `tao_region` VALUES ('1373', '高青县', '1366', '370322', '3');
INSERT INTO `tao_region` VALUES ('1374', '沂源县', '1366', '370323', '3');
INSERT INTO `tao_region` VALUES ('1375', '枣庄市', '1341', '370400', '2');
INSERT INTO `tao_region` VALUES ('1376', '市中区', '1375', '370402', '3');
INSERT INTO `tao_region` VALUES ('1377', '薛城区', '1375', '370403', '3');
INSERT INTO `tao_region` VALUES ('1378', '峄城区', '1375', '370404', '3');
INSERT INTO `tao_region` VALUES ('1379', '台儿庄区', '1375', '370405', '3');
INSERT INTO `tao_region` VALUES ('1380', '山亭区', '1375', '370406', '3');
INSERT INTO `tao_region` VALUES ('1381', '滕州市', '1375', '370481', '3');
INSERT INTO `tao_region` VALUES ('1382', '东营市', '1341', '370500', '2');
INSERT INTO `tao_region` VALUES ('1383', '东营区', '1382', '370502', '3');
INSERT INTO `tao_region` VALUES ('1384', '河口区', '1382', '370503', '3');
INSERT INTO `tao_region` VALUES ('1385', '垦利区', '1382', '370505', '3');
INSERT INTO `tao_region` VALUES ('1386', '利津县', '1382', '370522', '3');
INSERT INTO `tao_region` VALUES ('1387', '广饶县', '1382', '370523', '3');
INSERT INTO `tao_region` VALUES ('1388', '烟台市', '1341', '370600', '2');
INSERT INTO `tao_region` VALUES ('1389', '芝罘区', '1388', '370602', '3');
INSERT INTO `tao_region` VALUES ('1390', '福山区', '1388', '370611', '3');
INSERT INTO `tao_region` VALUES ('1391', '牟平区', '1388', '370612', '3');
INSERT INTO `tao_region` VALUES ('1392', '莱山区', '1388', '370613', '3');
INSERT INTO `tao_region` VALUES ('1394', '龙口市', '1388', '370681', '3');
INSERT INTO `tao_region` VALUES ('1395', '莱阳市', '1388', '370682', '3');
INSERT INTO `tao_region` VALUES ('1396', '莱州市', '1388', '370683', '3');
INSERT INTO `tao_region` VALUES ('1398', '招远市', '1388', '370685', '3');
INSERT INTO `tao_region` VALUES ('1399', '栖霞市', '1388', '370686', '3');
INSERT INTO `tao_region` VALUES ('1400', '海阳市', '1388', '370687', '3');
INSERT INTO `tao_region` VALUES ('1401', '潍坊市', '1341', '370700', '2');
INSERT INTO `tao_region` VALUES ('1402', '潍城区', '1401', '370702', '3');
INSERT INTO `tao_region` VALUES ('1403', '寒亭区', '1401', '370703', '3');
INSERT INTO `tao_region` VALUES ('1404', '坊子区', '1401', '370704', '3');
INSERT INTO `tao_region` VALUES ('1405', '奎文区', '1401', '370705', '3');
INSERT INTO `tao_region` VALUES ('1406', '临朐县', '1401', '370724', '3');
INSERT INTO `tao_region` VALUES ('1407', '昌乐县', '1401', '370725', '3');
INSERT INTO `tao_region` VALUES ('1408', '青州市', '1401', '370781', '3');
INSERT INTO `tao_region` VALUES ('1409', '诸城市', '1401', '370782', '3');
INSERT INTO `tao_region` VALUES ('1410', '寿光市', '1401', '370783', '3');
INSERT INTO `tao_region` VALUES ('1411', '安丘市', '1401', '370784', '3');
INSERT INTO `tao_region` VALUES ('1412', '高密市', '1401', '370785', '3');
INSERT INTO `tao_region` VALUES ('1413', '昌邑市', '1401', '370786', '3');
INSERT INTO `tao_region` VALUES ('1414', '济宁市', '1341', '370800', '2');
INSERT INTO `tao_region` VALUES ('1415', '任城区', '1414', '370811', '3');
INSERT INTO `tao_region` VALUES ('1416', '兖州区', '1414', '370812', '3');
INSERT INTO `tao_region` VALUES ('1417', '微山县', '1414', '370826', '3');
INSERT INTO `tao_region` VALUES ('1418', '鱼台县', '1414', '370827', '3');
INSERT INTO `tao_region` VALUES ('1419', '金乡县', '1414', '370828', '3');
INSERT INTO `tao_region` VALUES ('1420', '嘉祥县', '1414', '370829', '3');
INSERT INTO `tao_region` VALUES ('1421', '汶上县', '1414', '370830', '3');
INSERT INTO `tao_region` VALUES ('1422', '泗水县', '1414', '370831', '3');
INSERT INTO `tao_region` VALUES ('1423', '梁山县', '1414', '370832', '3');
INSERT INTO `tao_region` VALUES ('1424', '曲阜市', '1414', '370881', '3');
INSERT INTO `tao_region` VALUES ('1425', '邹城市', '1414', '370883', '3');
INSERT INTO `tao_region` VALUES ('1426', '泰安市', '1341', '370900', '2');
INSERT INTO `tao_region` VALUES ('1427', '泰山区', '1426', '370902', '3');
INSERT INTO `tao_region` VALUES ('1428', '岱岳区', '1426', '370911', '3');
INSERT INTO `tao_region` VALUES ('1429', '宁阳县', '1426', '370921', '3');
INSERT INTO `tao_region` VALUES ('1430', '东平县', '1426', '370923', '3');
INSERT INTO `tao_region` VALUES ('1431', '新泰市', '1426', '370982', '3');
INSERT INTO `tao_region` VALUES ('1432', '肥城市', '1426', '370983', '3');
INSERT INTO `tao_region` VALUES ('1433', '威海市', '1341', '371000', '2');
INSERT INTO `tao_region` VALUES ('1434', '环翠区', '1433', '371002', '3');
INSERT INTO `tao_region` VALUES ('1435', '文登区', '1433', '371003', '3');
INSERT INTO `tao_region` VALUES ('1436', '荣成市', '1433', '371082', '3');
INSERT INTO `tao_region` VALUES ('1437', '乳山市', '1433', '371083', '3');
INSERT INTO `tao_region` VALUES ('1438', '日照市', '1341', '371100', '2');
INSERT INTO `tao_region` VALUES ('1439', '东港区', '1438', '371102', '3');
INSERT INTO `tao_region` VALUES ('1440', '岚山区', '1438', '371103', '3');
INSERT INTO `tao_region` VALUES ('1441', '五莲县', '1438', '371121', '3');
INSERT INTO `tao_region` VALUES ('1442', '莒县', '1438', '371122', '3');
INSERT INTO `tao_region` VALUES ('1443', '临沂市', '1341', '371300', '2');
INSERT INTO `tao_region` VALUES ('1444', '兰山区', '1443', '371302', '3');
INSERT INTO `tao_region` VALUES ('1445', '罗庄区', '1443', '371311', '3');
INSERT INTO `tao_region` VALUES ('1446', '河东区', '1443', '371312', '3');
INSERT INTO `tao_region` VALUES ('1447', '沂南县', '1443', '371321', '3');
INSERT INTO `tao_region` VALUES ('1448', '郯城县', '1443', '371322', '3');
INSERT INTO `tao_region` VALUES ('1449', '沂水县', '1443', '371323', '3');
INSERT INTO `tao_region` VALUES ('1450', '兰陵县', '1443', '371324', '3');
INSERT INTO `tao_region` VALUES ('1451', '费县', '1443', '371325', '3');
INSERT INTO `tao_region` VALUES ('1452', '平邑县', '1443', '371326', '3');
INSERT INTO `tao_region` VALUES ('1453', '莒南县', '1443', '371327', '3');
INSERT INTO `tao_region` VALUES ('1454', '蒙阴县', '1443', '371328', '3');
INSERT INTO `tao_region` VALUES ('1455', '临沭县', '1443', '371329', '3');
INSERT INTO `tao_region` VALUES ('1456', '德州市', '1341', '371400', '2');
INSERT INTO `tao_region` VALUES ('1457', '德城区', '1456', '371402', '3');
INSERT INTO `tao_region` VALUES ('1458', '陵城区', '1456', '371403', '3');
INSERT INTO `tao_region` VALUES ('1459', '宁津县', '1456', '371422', '3');
INSERT INTO `tao_region` VALUES ('1460', '庆云县', '1456', '371423', '3');
INSERT INTO `tao_region` VALUES ('1461', '临邑县', '1456', '371424', '3');
INSERT INTO `tao_region` VALUES ('1462', '齐河县', '1456', '371425', '3');
INSERT INTO `tao_region` VALUES ('1463', '平原县', '1456', '371426', '3');
INSERT INTO `tao_region` VALUES ('1464', '夏津县', '1456', '371427', '3');
INSERT INTO `tao_region` VALUES ('1465', '武城县', '1456', '371428', '3');
INSERT INTO `tao_region` VALUES ('1466', '乐陵市', '1456', '371481', '3');
INSERT INTO `tao_region` VALUES ('1467', '禹城市', '1456', '371482', '3');
INSERT INTO `tao_region` VALUES ('1468', '聊城市', '1341', '371500', '2');
INSERT INTO `tao_region` VALUES ('1469', '东昌府区', '1468', '371502', '3');
INSERT INTO `tao_region` VALUES ('1470', '茌平区', '1468', '371503', '3');
INSERT INTO `tao_region` VALUES ('1471', '阳谷县', '1468', '371521', '3');
INSERT INTO `tao_region` VALUES ('1472', '莘县', '1468', '371522', '3');
INSERT INTO `tao_region` VALUES ('1473', '东阿县', '1468', '371524', '3');
INSERT INTO `tao_region` VALUES ('1474', '冠县', '1468', '371525', '3');
INSERT INTO `tao_region` VALUES ('1475', '高唐县', '1468', '371526', '3');
INSERT INTO `tao_region` VALUES ('1476', '临清市', '1468', '371581', '3');
INSERT INTO `tao_region` VALUES ('1477', '滨州市', '1341', '371600', '2');
INSERT INTO `tao_region` VALUES ('1478', '滨城区', '1477', '371602', '3');
INSERT INTO `tao_region` VALUES ('1479', '沾化区', '1477', '371603', '3');
INSERT INTO `tao_region` VALUES ('1480', '惠民县', '1477', '371621', '3');
INSERT INTO `tao_region` VALUES ('1481', '阳信县', '1477', '371622', '3');
INSERT INTO `tao_region` VALUES ('1482', '无棣县', '1477', '371623', '3');
INSERT INTO `tao_region` VALUES ('1483', '博兴县', '1477', '371625', '3');
INSERT INTO `tao_region` VALUES ('1484', '邹平市', '1477', '371681', '3');
INSERT INTO `tao_region` VALUES ('1485', '菏泽市', '1341', '371700', '2');
INSERT INTO `tao_region` VALUES ('1486', '牡丹区', '1485', '371702', '3');
INSERT INTO `tao_region` VALUES ('1487', '定陶区', '1485', '371703', '3');
INSERT INTO `tao_region` VALUES ('1488', '曹县', '1485', '371721', '3');
INSERT INTO `tao_region` VALUES ('1489', '单县', '1485', '371722', '3');
INSERT INTO `tao_region` VALUES ('1490', '成武县', '1485', '371723', '3');
INSERT INTO `tao_region` VALUES ('1491', '巨野县', '1485', '371724', '3');
INSERT INTO `tao_region` VALUES ('1492', '郓城县', '1485', '371725', '3');
INSERT INTO `tao_region` VALUES ('1493', '鄄城县', '1485', '371726', '3');
INSERT INTO `tao_region` VALUES ('1494', '东明县', '1485', '371728', '3');
INSERT INTO `tao_region` VALUES ('1495', '河南省', '0', '410000', '1');
INSERT INTO `tao_region` VALUES ('1496', '郑州市', '1495', '410100', '2');
INSERT INTO `tao_region` VALUES ('1497', '中原区', '1496', '410102', '3');
INSERT INTO `tao_region` VALUES ('1498', '二七区', '1496', '410103', '3');
INSERT INTO `tao_region` VALUES ('1499', '管城回族区', '1496', '410104', '3');
INSERT INTO `tao_region` VALUES ('1500', '金水区', '1496', '410105', '3');
INSERT INTO `tao_region` VALUES ('1501', '上街区', '1496', '410106', '3');
INSERT INTO `tao_region` VALUES ('1502', '惠济区', '1496', '410108', '3');
INSERT INTO `tao_region` VALUES ('1503', '中牟县', '1496', '410122', '3');
INSERT INTO `tao_region` VALUES ('1504', '巩义市', '1496', '410181', '3');
INSERT INTO `tao_region` VALUES ('1505', '荥阳市', '1496', '410182', '3');
INSERT INTO `tao_region` VALUES ('1506', '新密市', '1496', '410183', '3');
INSERT INTO `tao_region` VALUES ('1507', '新郑市', '1496', '410184', '3');
INSERT INTO `tao_region` VALUES ('1508', '登封市', '1496', '410185', '3');
INSERT INTO `tao_region` VALUES ('1509', '开封市', '1495', '410200', '2');
INSERT INTO `tao_region` VALUES ('1510', '龙亭区', '1509', '410202', '3');
INSERT INTO `tao_region` VALUES ('1511', '顺河回族区', '1509', '410203', '3');
INSERT INTO `tao_region` VALUES ('1512', '鼓楼区', '1509', '410204', '3');
INSERT INTO `tao_region` VALUES ('1513', '禹王台区', '1509', '410205', '3');
INSERT INTO `tao_region` VALUES ('1514', '祥符区', '1509', '410212', '3');
INSERT INTO `tao_region` VALUES ('1515', '杞县', '1509', '410221', '3');
INSERT INTO `tao_region` VALUES ('1516', '通许县', '1509', '410222', '3');
INSERT INTO `tao_region` VALUES ('1517', '尉氏县', '1509', '410223', '3');
INSERT INTO `tao_region` VALUES ('1518', '兰考县', '1509', '410225', '3');
INSERT INTO `tao_region` VALUES ('1519', '洛阳市', '1495', '410300', '2');
INSERT INTO `tao_region` VALUES ('1520', '老城区', '1519', '410302', '3');
INSERT INTO `tao_region` VALUES ('1521', '西工区', '1519', '410303', '3');
INSERT INTO `tao_region` VALUES ('1522', '瀍河回族区', '1519', '410304', '3');
INSERT INTO `tao_region` VALUES ('1523', '涧西区', '1519', '410305', '3');
INSERT INTO `tao_region` VALUES ('1525', '洛龙区', '1519', '410311', '3');
INSERT INTO `tao_region` VALUES ('1527', '新安县', '1519', '410323', '3');
INSERT INTO `tao_region` VALUES ('1528', '栾川县', '1519', '410324', '3');
INSERT INTO `tao_region` VALUES ('1529', '嵩县', '1519', '410325', '3');
INSERT INTO `tao_region` VALUES ('1530', '汝阳县', '1519', '410326', '3');
INSERT INTO `tao_region` VALUES ('1531', '宜阳县', '1519', '410327', '3');
INSERT INTO `tao_region` VALUES ('1532', '洛宁县', '1519', '410328', '3');
INSERT INTO `tao_region` VALUES ('1533', '伊川县', '1519', '410329', '3');
INSERT INTO `tao_region` VALUES ('1535', '平顶山市', '1495', '410400', '2');
INSERT INTO `tao_region` VALUES ('1536', '新华区', '1535', '410402', '3');
INSERT INTO `tao_region` VALUES ('1537', '卫东区', '1535', '410403', '3');
INSERT INTO `tao_region` VALUES ('1538', '石龙区', '1535', '410404', '3');
INSERT INTO `tao_region` VALUES ('1539', '湛河区', '1535', '410411', '3');
INSERT INTO `tao_region` VALUES ('1540', '宝丰县', '1535', '410421', '3');
INSERT INTO `tao_region` VALUES ('1541', '叶县', '1535', '410422', '3');
INSERT INTO `tao_region` VALUES ('1542', '鲁山县', '1535', '410423', '3');
INSERT INTO `tao_region` VALUES ('1543', '郏县', '1535', '410425', '3');
INSERT INTO `tao_region` VALUES ('1544', '舞钢市', '1535', '410481', '3');
INSERT INTO `tao_region` VALUES ('1545', '汝州市', '1535', '410482', '3');
INSERT INTO `tao_region` VALUES ('1546', '安阳市', '1495', '410500', '2');
INSERT INTO `tao_region` VALUES ('1547', '文峰区', '1546', '410502', '3');
INSERT INTO `tao_region` VALUES ('1548', '北关区', '1546', '410503', '3');
INSERT INTO `tao_region` VALUES ('1549', '殷都区', '1546', '410505', '3');
INSERT INTO `tao_region` VALUES ('1550', '龙安区', '1546', '410506', '3');
INSERT INTO `tao_region` VALUES ('1551', '安阳县', '1546', '410522', '3');
INSERT INTO `tao_region` VALUES ('1552', '汤阴县', '1546', '410523', '3');
INSERT INTO `tao_region` VALUES ('1553', '滑县', '1546', '410526', '3');
INSERT INTO `tao_region` VALUES ('1554', '内黄县', '1546', '410527', '3');
INSERT INTO `tao_region` VALUES ('1555', '林州市', '1546', '410581', '3');
INSERT INTO `tao_region` VALUES ('1556', '鹤壁市', '1495', '410600', '2');
INSERT INTO `tao_region` VALUES ('1557', '鹤山区', '1556', '410602', '3');
INSERT INTO `tao_region` VALUES ('1558', '山城区', '1556', '410603', '3');
INSERT INTO `tao_region` VALUES ('1559', '淇滨区', '1556', '410611', '3');
INSERT INTO `tao_region` VALUES ('1560', '浚县', '1556', '410621', '3');
INSERT INTO `tao_region` VALUES ('1561', '淇县', '1556', '410622', '3');
INSERT INTO `tao_region` VALUES ('1562', '新乡市', '1495', '410700', '2');
INSERT INTO `tao_region` VALUES ('1563', '红旗区', '1562', '410702', '3');
INSERT INTO `tao_region` VALUES ('1564', '卫滨区', '1562', '410703', '3');
INSERT INTO `tao_region` VALUES ('1565', '凤泉区', '1562', '410704', '3');
INSERT INTO `tao_region` VALUES ('1566', '牧野区', '1562', '410711', '3');
INSERT INTO `tao_region` VALUES ('1567', '新乡县', '1562', '410721', '3');
INSERT INTO `tao_region` VALUES ('1568', '获嘉县', '1562', '410724', '3');
INSERT INTO `tao_region` VALUES ('1569', '原阳县', '1562', '410725', '3');
INSERT INTO `tao_region` VALUES ('1570', '延津县', '1562', '410726', '3');
INSERT INTO `tao_region` VALUES ('1571', '封丘县', '1562', '410727', '3');
INSERT INTO `tao_region` VALUES ('1572', '卫辉市', '1562', '410781', '3');
INSERT INTO `tao_region` VALUES ('1573', '辉县市', '1562', '410782', '3');
INSERT INTO `tao_region` VALUES ('1574', '长垣市', '1562', '410783', '3');
INSERT INTO `tao_region` VALUES ('1575', '焦作市', '1495', '410800', '2');
INSERT INTO `tao_region` VALUES ('1576', '解放区', '1575', '410802', '3');
INSERT INTO `tao_region` VALUES ('1577', '中站区', '1575', '410803', '3');
INSERT INTO `tao_region` VALUES ('1578', '马村区', '1575', '410804', '3');
INSERT INTO `tao_region` VALUES ('1579', '山阳区', '1575', '410811', '3');
INSERT INTO `tao_region` VALUES ('1580', '修武县', '1575', '410821', '3');
INSERT INTO `tao_region` VALUES ('1581', '博爱县', '1575', '410822', '3');
INSERT INTO `tao_region` VALUES ('1582', '武陟县', '1575', '410823', '3');
INSERT INTO `tao_region` VALUES ('1583', '温县', '1575', '410825', '3');
INSERT INTO `tao_region` VALUES ('1584', '沁阳市', '1575', '410882', '3');
INSERT INTO `tao_region` VALUES ('1585', '孟州市', '1575', '410883', '3');
INSERT INTO `tao_region` VALUES ('1586', '濮阳市', '1495', '410900', '2');
INSERT INTO `tao_region` VALUES ('1587', '华龙区', '1586', '410902', '3');
INSERT INTO `tao_region` VALUES ('1588', '清丰县', '1586', '410922', '3');
INSERT INTO `tao_region` VALUES ('1589', '南乐县', '1586', '410923', '3');
INSERT INTO `tao_region` VALUES ('1590', '范县', '1586', '410926', '3');
INSERT INTO `tao_region` VALUES ('1591', '台前县', '1586', '410927', '3');
INSERT INTO `tao_region` VALUES ('1592', '濮阳县', '1586', '410928', '3');
INSERT INTO `tao_region` VALUES ('1593', '许昌市', '1495', '411000', '2');
INSERT INTO `tao_region` VALUES ('1594', '魏都区', '1593', '411002', '3');
INSERT INTO `tao_region` VALUES ('1595', '建安区', '1593', '411003', '3');
INSERT INTO `tao_region` VALUES ('1596', '鄢陵县', '1593', '411024', '3');
INSERT INTO `tao_region` VALUES ('1597', '襄城县', '1593', '411025', '3');
INSERT INTO `tao_region` VALUES ('1598', '禹州市', '1593', '411081', '3');
INSERT INTO `tao_region` VALUES ('1599', '长葛市', '1593', '411082', '3');
INSERT INTO `tao_region` VALUES ('1600', '漯河市', '1495', '411100', '2');
INSERT INTO `tao_region` VALUES ('1601', '源汇区', '1600', '411102', '3');
INSERT INTO `tao_region` VALUES ('1602', '郾城区', '1600', '411103', '3');
INSERT INTO `tao_region` VALUES ('1603', '召陵区', '1600', '411104', '3');
INSERT INTO `tao_region` VALUES ('1604', '舞阳县', '1600', '411121', '3');
INSERT INTO `tao_region` VALUES ('1605', '临颍县', '1600', '411122', '3');
INSERT INTO `tao_region` VALUES ('1606', '三门峡市', '1495', '411200', '2');
INSERT INTO `tao_region` VALUES ('1607', '湖滨区', '1606', '411202', '3');
INSERT INTO `tao_region` VALUES ('1608', '陕州区', '1606', '411203', '3');
INSERT INTO `tao_region` VALUES ('1609', '渑池县', '1606', '411221', '3');
INSERT INTO `tao_region` VALUES ('1610', '卢氏县', '1606', '411224', '3');
INSERT INTO `tao_region` VALUES ('1611', '义马市', '1606', '411281', '3');
INSERT INTO `tao_region` VALUES ('1612', '灵宝市', '1606', '411282', '3');
INSERT INTO `tao_region` VALUES ('1613', '南阳市', '1495', '411300', '2');
INSERT INTO `tao_region` VALUES ('1614', '宛城区', '1613', '411302', '3');
INSERT INTO `tao_region` VALUES ('1615', '卧龙区', '1613', '411303', '3');
INSERT INTO `tao_region` VALUES ('1616', '南召县', '1613', '411321', '3');
INSERT INTO `tao_region` VALUES ('1617', '方城县', '1613', '411322', '3');
INSERT INTO `tao_region` VALUES ('1618', '西峡县', '1613', '411323', '3');
INSERT INTO `tao_region` VALUES ('1619', '镇平县', '1613', '411324', '3');
INSERT INTO `tao_region` VALUES ('1620', '内乡县', '1613', '411325', '3');
INSERT INTO `tao_region` VALUES ('1621', '淅川县', '1613', '411326', '3');
INSERT INTO `tao_region` VALUES ('1622', '社旗县', '1613', '411327', '3');
INSERT INTO `tao_region` VALUES ('1623', '唐河县', '1613', '411328', '3');
INSERT INTO `tao_region` VALUES ('1624', '新野县', '1613', '411329', '3');
INSERT INTO `tao_region` VALUES ('1625', '桐柏县', '1613', '411330', '3');
INSERT INTO `tao_region` VALUES ('1626', '邓州市', '1613', '411381', '3');
INSERT INTO `tao_region` VALUES ('1627', '商丘市', '1495', '411400', '2');
INSERT INTO `tao_region` VALUES ('1628', '梁园区', '1627', '411402', '3');
INSERT INTO `tao_region` VALUES ('1629', '睢阳区', '1627', '411403', '3');
INSERT INTO `tao_region` VALUES ('1630', '民权县', '1627', '411421', '3');
INSERT INTO `tao_region` VALUES ('1631', '睢县', '1627', '411422', '3');
INSERT INTO `tao_region` VALUES ('1632', '宁陵县', '1627', '411423', '3');
INSERT INTO `tao_region` VALUES ('1633', '柘城县', '1627', '411424', '3');
INSERT INTO `tao_region` VALUES ('1634', '虞城县', '1627', '411425', '3');
INSERT INTO `tao_region` VALUES ('1635', '夏邑县', '1627', '411426', '3');
INSERT INTO `tao_region` VALUES ('1636', '永城市', '1627', '411481', '3');
INSERT INTO `tao_region` VALUES ('1637', '信阳市', '1495', '411500', '2');
INSERT INTO `tao_region` VALUES ('1638', '浉河区', '1637', '411502', '3');
INSERT INTO `tao_region` VALUES ('1639', '平桥区', '1637', '411503', '3');
INSERT INTO `tao_region` VALUES ('1640', '罗山县', '1637', '411521', '3');
INSERT INTO `tao_region` VALUES ('1641', '光山县', '1637', '411522', '3');
INSERT INTO `tao_region` VALUES ('1642', '新县', '1637', '411523', '3');
INSERT INTO `tao_region` VALUES ('1643', '商城县', '1637', '411524', '3');
INSERT INTO `tao_region` VALUES ('1644', '固始县', '1637', '411525', '3');
INSERT INTO `tao_region` VALUES ('1645', '潢川县', '1637', '411526', '3');
INSERT INTO `tao_region` VALUES ('1646', '淮滨县', '1637', '411527', '3');
INSERT INTO `tao_region` VALUES ('1647', '息县', '1637', '411528', '3');
INSERT INTO `tao_region` VALUES ('1648', '周口市', '1495', '411600', '2');
INSERT INTO `tao_region` VALUES ('1649', '川汇区', '1648', '411602', '3');
INSERT INTO `tao_region` VALUES ('1650', '淮阳区', '1648', '411603', '3');
INSERT INTO `tao_region` VALUES ('1651', '扶沟县', '1648', '411621', '3');
INSERT INTO `tao_region` VALUES ('1652', '西华县', '1648', '411622', '3');
INSERT INTO `tao_region` VALUES ('1653', '商水县', '1648', '411623', '3');
INSERT INTO `tao_region` VALUES ('1654', '沈丘县', '1648', '411624', '3');
INSERT INTO `tao_region` VALUES ('1655', '郸城县', '1648', '411625', '3');
INSERT INTO `tao_region` VALUES ('1656', '太康县', '1648', '411627', '3');
INSERT INTO `tao_region` VALUES ('1657', '鹿邑县', '1648', '411628', '3');
INSERT INTO `tao_region` VALUES ('1658', '项城市', '1648', '411681', '3');
INSERT INTO `tao_region` VALUES ('1659', '驻马店市', '1495', '411700', '2');
INSERT INTO `tao_region` VALUES ('1660', '驿城区', '1659', '411702', '3');
INSERT INTO `tao_region` VALUES ('1661', '西平县', '1659', '411721', '3');
INSERT INTO `tao_region` VALUES ('1662', '上蔡县', '1659', '411722', '3');
INSERT INTO `tao_region` VALUES ('1663', '平舆县', '1659', '411723', '3');
INSERT INTO `tao_region` VALUES ('1664', '正阳县', '1659', '411724', '3');
INSERT INTO `tao_region` VALUES ('1665', '确山县', '1659', '411725', '3');
INSERT INTO `tao_region` VALUES ('1666', '泌阳县', '1659', '411726', '3');
INSERT INTO `tao_region` VALUES ('1667', '汝南县', '1659', '411727', '3');
INSERT INTO `tao_region` VALUES ('1668', '遂平县', '1659', '411728', '3');
INSERT INTO `tao_region` VALUES ('1669', '新蔡县', '1659', '411729', '3');
INSERT INTO `tao_region` VALUES ('1671', '湖北省', '0', '420000', '1');
INSERT INTO `tao_region` VALUES ('1672', '武汉市', '1671', '420100', '2');
INSERT INTO `tao_region` VALUES ('1673', '江岸区', '1672', '420102', '3');
INSERT INTO `tao_region` VALUES ('1674', '江汉区', '1672', '420103', '3');
INSERT INTO `tao_region` VALUES ('1675', '硚口区', '1672', '420104', '3');
INSERT INTO `tao_region` VALUES ('1676', '汉阳区', '1672', '420105', '3');
INSERT INTO `tao_region` VALUES ('1677', '武昌区', '1672', '420106', '3');
INSERT INTO `tao_region` VALUES ('1678', '青山区', '1672', '420107', '3');
INSERT INTO `tao_region` VALUES ('1679', '洪山区', '1672', '420111', '3');
INSERT INTO `tao_region` VALUES ('1680', '东西湖区', '1672', '420112', '3');
INSERT INTO `tao_region` VALUES ('1681', '汉南区', '1672', '420113', '3');
INSERT INTO `tao_region` VALUES ('1682', '蔡甸区', '1672', '420114', '3');
INSERT INTO `tao_region` VALUES ('1683', '江夏区', '1672', '420115', '3');
INSERT INTO `tao_region` VALUES ('1684', '黄陂区', '1672', '420116', '3');
INSERT INTO `tao_region` VALUES ('1685', '新洲区', '1672', '420117', '3');
INSERT INTO `tao_region` VALUES ('1686', '黄石市', '1671', '420200', '2');
INSERT INTO `tao_region` VALUES ('1687', '黄石港区', '1686', '420202', '3');
INSERT INTO `tao_region` VALUES ('1688', '西塞山区', '1686', '420203', '3');
INSERT INTO `tao_region` VALUES ('1689', '下陆区', '1686', '420204', '3');
INSERT INTO `tao_region` VALUES ('1690', '铁山区', '1686', '420205', '3');
INSERT INTO `tao_region` VALUES ('1691', '阳新县', '1686', '420222', '3');
INSERT INTO `tao_region` VALUES ('1692', '大冶市', '1686', '420281', '3');
INSERT INTO `tao_region` VALUES ('1693', '十堰市', '1671', '420300', '2');
INSERT INTO `tao_region` VALUES ('1694', '茅箭区', '1693', '420302', '3');
INSERT INTO `tao_region` VALUES ('1695', '张湾区', '1693', '420303', '3');
INSERT INTO `tao_region` VALUES ('1696', '郧阳区', '1693', '420304', '3');
INSERT INTO `tao_region` VALUES ('1697', '郧西县', '1693', '420322', '3');
INSERT INTO `tao_region` VALUES ('1698', '竹山县', '1693', '420323', '3');
INSERT INTO `tao_region` VALUES ('1699', '竹溪县', '1693', '420324', '3');
INSERT INTO `tao_region` VALUES ('1700', '房县', '1693', '420325', '3');
INSERT INTO `tao_region` VALUES ('1701', '丹江口市', '1693', '420381', '3');
INSERT INTO `tao_region` VALUES ('1702', '宜昌市', '1671', '420500', '2');
INSERT INTO `tao_region` VALUES ('1703', '西陵区', '1702', '420502', '3');
INSERT INTO `tao_region` VALUES ('1704', '伍家岗区', '1702', '420503', '3');
INSERT INTO `tao_region` VALUES ('1705', '点军区', '1702', '420504', '3');
INSERT INTO `tao_region` VALUES ('1706', '猇亭区', '1702', '420505', '3');
INSERT INTO `tao_region` VALUES ('1707', '夷陵区', '1702', '420506', '3');
INSERT INTO `tao_region` VALUES ('1708', '远安县', '1702', '420525', '3');
INSERT INTO `tao_region` VALUES ('1709', '兴山县', '1702', '420526', '3');
INSERT INTO `tao_region` VALUES ('1710', '秭归县', '1702', '420527', '3');
INSERT INTO `tao_region` VALUES ('1711', '长阳土家族自治县', '1702', '420528', '3');
INSERT INTO `tao_region` VALUES ('1712', '五峰土家族自治县', '1702', '420529', '3');
INSERT INTO `tao_region` VALUES ('1713', '宜都市', '1702', '420581', '3');
INSERT INTO `tao_region` VALUES ('1714', '当阳市', '1702', '420582', '3');
INSERT INTO `tao_region` VALUES ('1715', '枝江市', '1702', '420583', '3');
INSERT INTO `tao_region` VALUES ('1716', '襄阳市', '1671', '420600', '2');
INSERT INTO `tao_region` VALUES ('1717', '襄城区', '1716', '420602', '3');
INSERT INTO `tao_region` VALUES ('1718', '樊城区', '1716', '420606', '3');
INSERT INTO `tao_region` VALUES ('1719', '襄州区', '1716', '420607', '3');
INSERT INTO `tao_region` VALUES ('1720', '南漳县', '1716', '420624', '3');
INSERT INTO `tao_region` VALUES ('1721', '谷城县', '1716', '420625', '3');
INSERT INTO `tao_region` VALUES ('1722', '保康县', '1716', '420626', '3');
INSERT INTO `tao_region` VALUES ('1723', '老河口市', '1716', '420682', '3');
INSERT INTO `tao_region` VALUES ('1724', '枣阳市', '1716', '420683', '3');
INSERT INTO `tao_region` VALUES ('1725', '宜城市', '1716', '420684', '3');
INSERT INTO `tao_region` VALUES ('1726', '鄂州市', '1671', '420700', '2');
INSERT INTO `tao_region` VALUES ('1727', '梁子湖区', '1726', '420702', '3');
INSERT INTO `tao_region` VALUES ('1728', '华容区', '1726', '420703', '3');
INSERT INTO `tao_region` VALUES ('1729', '鄂城区', '1726', '420704', '3');
INSERT INTO `tao_region` VALUES ('1730', '荆门市', '1671', '420800', '2');
INSERT INTO `tao_region` VALUES ('1731', '东宝区', '1730', '420802', '3');
INSERT INTO `tao_region` VALUES ('1732', '掇刀区', '1730', '420804', '3');
INSERT INTO `tao_region` VALUES ('1733', '沙洋县', '1730', '420822', '3');
INSERT INTO `tao_region` VALUES ('1734', '钟祥市', '1730', '420881', '3');
INSERT INTO `tao_region` VALUES ('1735', '京山市', '1730', '420882', '3');
INSERT INTO `tao_region` VALUES ('1736', '孝感市', '1671', '420900', '2');
INSERT INTO `tao_region` VALUES ('1737', '孝南区', '1736', '420902', '3');
INSERT INTO `tao_region` VALUES ('1738', '孝昌县', '1736', '420921', '3');
INSERT INTO `tao_region` VALUES ('1739', '大悟县', '1736', '420922', '3');
INSERT INTO `tao_region` VALUES ('1740', '云梦县', '1736', '420923', '3');
INSERT INTO `tao_region` VALUES ('1741', '应城市', '1736', '420981', '3');
INSERT INTO `tao_region` VALUES ('1742', '安陆市', '1736', '420982', '3');
INSERT INTO `tao_region` VALUES ('1743', '汉川市', '1736', '420984', '3');
INSERT INTO `tao_region` VALUES ('1744', '荆州市', '1671', '421000', '2');
INSERT INTO `tao_region` VALUES ('1745', '沙市区', '1744', '421002', '3');
INSERT INTO `tao_region` VALUES ('1746', '荆州区', '1744', '421003', '3');
INSERT INTO `tao_region` VALUES ('1747', '公安县', '1744', '421022', '3');
INSERT INTO `tao_region` VALUES ('1749', '江陵县', '1744', '421024', '3');
INSERT INTO `tao_region` VALUES ('1750', '石首市', '1744', '421081', '3');
INSERT INTO `tao_region` VALUES ('1751', '洪湖市', '1744', '421083', '3');
INSERT INTO `tao_region` VALUES ('1752', '松滋市', '1744', '421087', '3');
INSERT INTO `tao_region` VALUES ('1753', '黄冈市', '1671', '421100', '2');
INSERT INTO `tao_region` VALUES ('1754', '黄州区', '1753', '421102', '3');
INSERT INTO `tao_region` VALUES ('1755', '团风县', '1753', '421121', '3');
INSERT INTO `tao_region` VALUES ('1756', '红安县', '1753', '421122', '3');
INSERT INTO `tao_region` VALUES ('1757', '罗田县', '1753', '421123', '3');
INSERT INTO `tao_region` VALUES ('1758', '英山县', '1753', '421124', '3');
INSERT INTO `tao_region` VALUES ('1759', '浠水县', '1753', '421125', '3');
INSERT INTO `tao_region` VALUES ('1760', '蕲春县', '1753', '421126', '3');
INSERT INTO `tao_region` VALUES ('1761', '黄梅县', '1753', '421127', '3');
INSERT INTO `tao_region` VALUES ('1762', '麻城市', '1753', '421181', '3');
INSERT INTO `tao_region` VALUES ('1763', '武穴市', '1753', '421182', '3');
INSERT INTO `tao_region` VALUES ('1764', '咸宁市', '1671', '421200', '2');
INSERT INTO `tao_region` VALUES ('1765', '咸安区', '1764', '421202', '3');
INSERT INTO `tao_region` VALUES ('1766', '嘉鱼县', '1764', '421221', '3');
INSERT INTO `tao_region` VALUES ('1767', '通城县', '1764', '421222', '3');
INSERT INTO `tao_region` VALUES ('1768', '崇阳县', '1764', '421223', '3');
INSERT INTO `tao_region` VALUES ('1769', '通山县', '1764', '421224', '3');
INSERT INTO `tao_region` VALUES ('1770', '赤壁市', '1764', '421281', '3');
INSERT INTO `tao_region` VALUES ('1771', '随州市', '1671', '421300', '2');
INSERT INTO `tao_region` VALUES ('1772', '曾都区', '1771', '421303', '3');
INSERT INTO `tao_region` VALUES ('1773', '随县', '1771', '421321', '3');
INSERT INTO `tao_region` VALUES ('1774', '广水市', '1771', '421381', '3');
INSERT INTO `tao_region` VALUES ('1775', '恩施土家族苗族自治州', '1671', '422800', '2');
INSERT INTO `tao_region` VALUES ('1776', '恩施市', '1775', '422801', '3');
INSERT INTO `tao_region` VALUES ('1777', '利川市', '1775', '422802', '3');
INSERT INTO `tao_region` VALUES ('1778', '建始县', '1775', '422822', '3');
INSERT INTO `tao_region` VALUES ('1779', '巴东县', '1775', '422823', '3');
INSERT INTO `tao_region` VALUES ('1780', '宣恩县', '1775', '422825', '3');
INSERT INTO `tao_region` VALUES ('1781', '咸丰县', '1775', '422826', '3');
INSERT INTO `tao_region` VALUES ('1782', '来凤县', '1775', '422827', '3');
INSERT INTO `tao_region` VALUES ('1783', '鹤峰县', '1775', '422828', '3');
INSERT INTO `tao_region` VALUES ('1788', '湖南省', '0', '430000', '1');
INSERT INTO `tao_region` VALUES ('1789', '长沙市', '1788', '430100', '2');
INSERT INTO `tao_region` VALUES ('1790', '芙蓉区', '1789', '430102', '3');
INSERT INTO `tao_region` VALUES ('1791', '天心区', '1789', '430103', '3');
INSERT INTO `tao_region` VALUES ('1792', '岳麓区', '1789', '430104', '3');
INSERT INTO `tao_region` VALUES ('1793', '开福区', '1789', '430105', '3');
INSERT INTO `tao_region` VALUES ('1794', '雨花区', '1789', '430111', '3');
INSERT INTO `tao_region` VALUES ('1795', '望城区', '1789', '430112', '3');
INSERT INTO `tao_region` VALUES ('1796', '长沙县', '1789', '430121', '3');
INSERT INTO `tao_region` VALUES ('1797', '浏阳市', '1789', '430181', '3');
INSERT INTO `tao_region` VALUES ('1798', '宁乡市', '1789', '430182', '3');
INSERT INTO `tao_region` VALUES ('1799', '株洲市', '1788', '430200', '2');
INSERT INTO `tao_region` VALUES ('1800', '荷塘区', '1799', '430202', '3');
INSERT INTO `tao_region` VALUES ('1801', '芦淞区', '1799', '430203', '3');
INSERT INTO `tao_region` VALUES ('1802', '石峰区', '1799', '430204', '3');
INSERT INTO `tao_region` VALUES ('1803', '天元区', '1799', '430211', '3');
INSERT INTO `tao_region` VALUES ('1804', '渌口区', '1799', '430212', '3');
INSERT INTO `tao_region` VALUES ('1805', '攸县', '1799', '430223', '3');
INSERT INTO `tao_region` VALUES ('1806', '茶陵县', '1799', '430224', '3');
INSERT INTO `tao_region` VALUES ('1807', '炎陵县', '1799', '430225', '3');
INSERT INTO `tao_region` VALUES ('1808', '醴陵市', '1799', '430281', '3');
INSERT INTO `tao_region` VALUES ('1809', '湘潭市', '1788', '430300', '2');
INSERT INTO `tao_region` VALUES ('1810', '雨湖区', '1809', '430302', '3');
INSERT INTO `tao_region` VALUES ('1811', '岳塘区', '1809', '430304', '3');
INSERT INTO `tao_region` VALUES ('1812', '湘潭县', '1809', '430321', '3');
INSERT INTO `tao_region` VALUES ('1813', '湘乡市', '1809', '430381', '3');
INSERT INTO `tao_region` VALUES ('1814', '韶山市', '1809', '430382', '3');
INSERT INTO `tao_region` VALUES ('1815', '衡阳市', '1788', '430400', '2');
INSERT INTO `tao_region` VALUES ('1816', '珠晖区', '1815', '430405', '3');
INSERT INTO `tao_region` VALUES ('1817', '雁峰区', '1815', '430406', '3');
INSERT INTO `tao_region` VALUES ('1818', '石鼓区', '1815', '430407', '3');
INSERT INTO `tao_region` VALUES ('1819', '蒸湘区', '1815', '430408', '3');
INSERT INTO `tao_region` VALUES ('1820', '南岳区', '1815', '430412', '3');
INSERT INTO `tao_region` VALUES ('1821', '衡阳县', '1815', '430421', '3');
INSERT INTO `tao_region` VALUES ('1822', '衡南县', '1815', '430422', '3');
INSERT INTO `tao_region` VALUES ('1823', '衡山县', '1815', '430423', '3');
INSERT INTO `tao_region` VALUES ('1824', '衡东县', '1815', '430424', '3');
INSERT INTO `tao_region` VALUES ('1825', '祁东县', '1815', '430426', '3');
INSERT INTO `tao_region` VALUES ('1826', '耒阳市', '1815', '430481', '3');
INSERT INTO `tao_region` VALUES ('1827', '常宁市', '1815', '430482', '3');
INSERT INTO `tao_region` VALUES ('1828', '邵阳市', '1788', '430500', '2');
INSERT INTO `tao_region` VALUES ('1829', '双清区', '1828', '430502', '3');
INSERT INTO `tao_region` VALUES ('1830', '大祥区', '1828', '430503', '3');
INSERT INTO `tao_region` VALUES ('1831', '北塔区', '1828', '430511', '3');
INSERT INTO `tao_region` VALUES ('1832', '新邵县', '1828', '430522', '3');
INSERT INTO `tao_region` VALUES ('1833', '邵阳县', '1828', '430523', '3');
INSERT INTO `tao_region` VALUES ('1834', '隆回县', '1828', '430524', '3');
INSERT INTO `tao_region` VALUES ('1835', '洞口县', '1828', '430525', '3');
INSERT INTO `tao_region` VALUES ('1836', '绥宁县', '1828', '430527', '3');
INSERT INTO `tao_region` VALUES ('1837', '新宁县', '1828', '430528', '3');
INSERT INTO `tao_region` VALUES ('1838', '城步苗族自治县', '1828', '430529', '3');
INSERT INTO `tao_region` VALUES ('1839', '武冈市', '1828', '430581', '3');
INSERT INTO `tao_region` VALUES ('1840', '邵东市', '1828', '430582', '3');
INSERT INTO `tao_region` VALUES ('1841', '岳阳市', '1788', '430600', '2');
INSERT INTO `tao_region` VALUES ('1842', '岳阳楼区', '1841', '430602', '3');
INSERT INTO `tao_region` VALUES ('1843', '云溪区', '1841', '430603', '3');
INSERT INTO `tao_region` VALUES ('1844', '君山区', '1841', '430611', '3');
INSERT INTO `tao_region` VALUES ('1845', '岳阳县', '1841', '430621', '3');
INSERT INTO `tao_region` VALUES ('1846', '华容县', '1841', '430623', '3');
INSERT INTO `tao_region` VALUES ('1847', '湘阴县', '1841', '430624', '3');
INSERT INTO `tao_region` VALUES ('1848', '平江县', '1841', '430626', '3');
INSERT INTO `tao_region` VALUES ('1849', '汨罗市', '1841', '430681', '3');
INSERT INTO `tao_region` VALUES ('1850', '临湘市', '1841', '430682', '3');
INSERT INTO `tao_region` VALUES ('1851', '常德市', '1788', '430700', '2');
INSERT INTO `tao_region` VALUES ('1852', '武陵区', '1851', '430702', '3');
INSERT INTO `tao_region` VALUES ('1853', '鼎城区', '1851', '430703', '3');
INSERT INTO `tao_region` VALUES ('1854', '安乡县', '1851', '430721', '3');
INSERT INTO `tao_region` VALUES ('1855', '汉寿县', '1851', '430722', '3');
INSERT INTO `tao_region` VALUES ('1856', '澧县', '1851', '430723', '3');
INSERT INTO `tao_region` VALUES ('1857', '临澧县', '1851', '430724', '3');
INSERT INTO `tao_region` VALUES ('1858', '桃源县', '1851', '430725', '3');
INSERT INTO `tao_region` VALUES ('1859', '石门县', '1851', '430726', '3');
INSERT INTO `tao_region` VALUES ('1860', '津市市', '1851', '430781', '3');
INSERT INTO `tao_region` VALUES ('1861', '张家界市', '1788', '430800', '2');
INSERT INTO `tao_region` VALUES ('1862', '永定区', '1861', '430802', '3');
INSERT INTO `tao_region` VALUES ('1863', '武陵源区', '1861', '430811', '3');
INSERT INTO `tao_region` VALUES ('1864', '慈利县', '1861', '430821', '3');
INSERT INTO `tao_region` VALUES ('1865', '桑植县', '1861', '430822', '3');
INSERT INTO `tao_region` VALUES ('1866', '益阳市', '1788', '430900', '2');
INSERT INTO `tao_region` VALUES ('1867', '资阳区', '1866', '430902', '3');
INSERT INTO `tao_region` VALUES ('1868', '赫山区', '1866', '430903', '3');
INSERT INTO `tao_region` VALUES ('1869', '南县', '1866', '430921', '3');
INSERT INTO `tao_region` VALUES ('1870', '桃江县', '1866', '430922', '3');
INSERT INTO `tao_region` VALUES ('1871', '安化县', '1866', '430923', '3');
INSERT INTO `tao_region` VALUES ('1872', '沅江市', '1866', '430981', '3');
INSERT INTO `tao_region` VALUES ('1873', '郴州市', '1788', '431000', '2');
INSERT INTO `tao_region` VALUES ('1874', '北湖区', '1873', '431002', '3');
INSERT INTO `tao_region` VALUES ('1875', '苏仙区', '1873', '431003', '3');
INSERT INTO `tao_region` VALUES ('1876', '桂阳县', '1873', '431021', '3');
INSERT INTO `tao_region` VALUES ('1877', '宜章县', '1873', '431022', '3');
INSERT INTO `tao_region` VALUES ('1878', '永兴县', '1873', '431023', '3');
INSERT INTO `tao_region` VALUES ('1879', '嘉禾县', '1873', '431024', '3');
INSERT INTO `tao_region` VALUES ('1880', '临武县', '1873', '431025', '3');
INSERT INTO `tao_region` VALUES ('1881', '汝城县', '1873', '431026', '3');
INSERT INTO `tao_region` VALUES ('1882', '桂东县', '1873', '431027', '3');
INSERT INTO `tao_region` VALUES ('1883', '安仁县', '1873', '431028', '3');
INSERT INTO `tao_region` VALUES ('1884', '资兴市', '1873', '431081', '3');
INSERT INTO `tao_region` VALUES ('1885', '永州市', '1788', '431100', '2');
INSERT INTO `tao_region` VALUES ('1886', '零陵区', '1885', '431102', '3');
INSERT INTO `tao_region` VALUES ('1887', '冷水滩区', '1885', '431103', '3');
INSERT INTO `tao_region` VALUES ('1889', '东安县', '1885', '431122', '3');
INSERT INTO `tao_region` VALUES ('1890', '双牌县', '1885', '431123', '3');
INSERT INTO `tao_region` VALUES ('1891', '道县', '1885', '431124', '3');
INSERT INTO `tao_region` VALUES ('1892', '江永县', '1885', '431125', '3');
INSERT INTO `tao_region` VALUES ('1893', '宁远县', '1885', '431126', '3');
INSERT INTO `tao_region` VALUES ('1894', '蓝山县', '1885', '431127', '3');
INSERT INTO `tao_region` VALUES ('1895', '新田县', '1885', '431128', '3');
INSERT INTO `tao_region` VALUES ('1896', '江华瑶族自治县', '1885', '431129', '3');
INSERT INTO `tao_region` VALUES ('1897', '怀化市', '1788', '431200', '2');
INSERT INTO `tao_region` VALUES ('1898', '鹤城区', '1897', '431202', '3');
INSERT INTO `tao_region` VALUES ('1899', '中方县', '1897', '431221', '3');
INSERT INTO `tao_region` VALUES ('1900', '沅陵县', '1897', '431222', '3');
INSERT INTO `tao_region` VALUES ('1901', '辰溪县', '1897', '431223', '3');
INSERT INTO `tao_region` VALUES ('1902', '溆浦县', '1897', '431224', '3');
INSERT INTO `tao_region` VALUES ('1903', '会同县', '1897', '431225', '3');
INSERT INTO `tao_region` VALUES ('1904', '麻阳苗族自治县', '1897', '431226', '3');
INSERT INTO `tao_region` VALUES ('1905', '新晃侗族自治县', '1897', '431227', '3');
INSERT INTO `tao_region` VALUES ('1906', '芷江侗族自治县', '1897', '431228', '3');
INSERT INTO `tao_region` VALUES ('1907', '靖州苗族侗族自治县', '1897', '431229', '3');
INSERT INTO `tao_region` VALUES ('1908', '通道侗族自治县', '1897', '431230', '3');
INSERT INTO `tao_region` VALUES ('1909', '洪江市', '1897', '431281', '3');
INSERT INTO `tao_region` VALUES ('1910', '娄底市', '1788', '431300', '2');
INSERT INTO `tao_region` VALUES ('1911', '娄星区', '1910', '431302', '3');
INSERT INTO `tao_region` VALUES ('1912', '双峰县', '1910', '431321', '3');
INSERT INTO `tao_region` VALUES ('1913', '新化县', '1910', '431322', '3');
INSERT INTO `tao_region` VALUES ('1914', '冷水江市', '1910', '431381', '3');
INSERT INTO `tao_region` VALUES ('1915', '涟源市', '1910', '431382', '3');
INSERT INTO `tao_region` VALUES ('1916', '湘西土家族苗族自治州', '1788', '433100', '2');
INSERT INTO `tao_region` VALUES ('1917', '吉首市', '1916', '433101', '3');
INSERT INTO `tao_region` VALUES ('1918', '泸溪县', '1916', '433122', '3');
INSERT INTO `tao_region` VALUES ('1919', '凤凰县', '1916', '433123', '3');
INSERT INTO `tao_region` VALUES ('1920', '花垣县', '1916', '433124', '3');
INSERT INTO `tao_region` VALUES ('1921', '保靖县', '1916', '433125', '3');
INSERT INTO `tao_region` VALUES ('1922', '古丈县', '1916', '433126', '3');
INSERT INTO `tao_region` VALUES ('1923', '永顺县', '1916', '433127', '3');
INSERT INTO `tao_region` VALUES ('1924', '龙山县', '1916', '433130', '3');
INSERT INTO `tao_region` VALUES ('1925', '广东省', '0', '440000', '1');
INSERT INTO `tao_region` VALUES ('1926', '广州市', '1925', '440100', '2');
INSERT INTO `tao_region` VALUES ('1927', '荔湾区', '1926', '440103', '3');
INSERT INTO `tao_region` VALUES ('1928', '越秀区', '1926', '440104', '3');
INSERT INTO `tao_region` VALUES ('1929', '海珠区', '1926', '440105', '3');
INSERT INTO `tao_region` VALUES ('1930', '天河区', '1926', '440106', '3');
INSERT INTO `tao_region` VALUES ('1931', '白云区', '1926', '440111', '3');
INSERT INTO `tao_region` VALUES ('1932', '黄埔区', '1926', '440112', '3');
INSERT INTO `tao_region` VALUES ('1933', '番禺区', '1926', '440113', '3');
INSERT INTO `tao_region` VALUES ('1934', '花都区', '1926', '440114', '3');
INSERT INTO `tao_region` VALUES ('1935', '南沙区', '1926', '440115', '3');
INSERT INTO `tao_region` VALUES ('1936', '从化区', '1926', '440117', '3');
INSERT INTO `tao_region` VALUES ('1937', '增城区', '1926', '440118', '3');
INSERT INTO `tao_region` VALUES ('1938', '韶关市', '1925', '440200', '2');
INSERT INTO `tao_region` VALUES ('1939', '武江区', '1938', '440203', '3');
INSERT INTO `tao_region` VALUES ('1940', '浈江区', '1938', '440204', '3');
INSERT INTO `tao_region` VALUES ('1941', '曲江区', '1938', '440205', '3');
INSERT INTO `tao_region` VALUES ('1942', '始兴县', '1938', '440222', '3');
INSERT INTO `tao_region` VALUES ('1943', '仁化县', '1938', '440224', '3');
INSERT INTO `tao_region` VALUES ('1944', '翁源县', '1938', '440229', '3');
INSERT INTO `tao_region` VALUES ('1945', '乳源瑶族自治县', '1938', '440232', '3');
INSERT INTO `tao_region` VALUES ('1946', '新丰县', '1938', '440233', '3');
INSERT INTO `tao_region` VALUES ('1947', '乐昌市', '1938', '440281', '3');
INSERT INTO `tao_region` VALUES ('1948', '南雄市', '1938', '440282', '3');
INSERT INTO `tao_region` VALUES ('1949', '深圳市', '1925', '440300', '2');
INSERT INTO `tao_region` VALUES ('1950', '罗湖区', '1949', '440303', '3');
INSERT INTO `tao_region` VALUES ('1951', '福田区', '1949', '440304', '3');
INSERT INTO `tao_region` VALUES ('1952', '南山区', '1949', '440305', '3');
INSERT INTO `tao_region` VALUES ('1953', '宝安区', '1949', '440306', '3');
INSERT INTO `tao_region` VALUES ('1954', '龙岗区', '1949', '440307', '3');
INSERT INTO `tao_region` VALUES ('1955', '盐田区', '1949', '440308', '3');
INSERT INTO `tao_region` VALUES ('1956', '龙华区', '1949', '440309', '3');
INSERT INTO `tao_region` VALUES ('1957', '坪山区', '1949', '440310', '3');
INSERT INTO `tao_region` VALUES ('1958', '光明区', '1949', '440311', '3');
INSERT INTO `tao_region` VALUES ('1959', '珠海市', '1925', '440400', '2');
INSERT INTO `tao_region` VALUES ('1960', '香洲区', '1959', '440402', '3');
INSERT INTO `tao_region` VALUES ('1961', '斗门区', '1959', '440403', '3');
INSERT INTO `tao_region` VALUES ('1962', '金湾区', '1959', '440404', '3');
INSERT INTO `tao_region` VALUES ('1963', '汕头市', '1925', '440500', '2');
INSERT INTO `tao_region` VALUES ('1964', '龙湖区', '1963', '440507', '3');
INSERT INTO `tao_region` VALUES ('1965', '金平区', '1963', '440511', '3');
INSERT INTO `tao_region` VALUES ('1966', '濠江区', '1963', '440512', '3');
INSERT INTO `tao_region` VALUES ('1967', '潮阳区', '1963', '440513', '3');
INSERT INTO `tao_region` VALUES ('1968', '潮南区', '1963', '440514', '3');
INSERT INTO `tao_region` VALUES ('1969', '澄海区', '1963', '440515', '3');
INSERT INTO `tao_region` VALUES ('1970', '南澳县', '1963', '440523', '3');
INSERT INTO `tao_region` VALUES ('1971', '佛山市', '1925', '440600', '2');
INSERT INTO `tao_region` VALUES ('1972', '禅城区', '1971', '440604', '3');
INSERT INTO `tao_region` VALUES ('1973', '南海区', '1971', '440605', '3');
INSERT INTO `tao_region` VALUES ('1974', '顺德区', '1971', '440606', '3');
INSERT INTO `tao_region` VALUES ('1975', '三水区', '1971', '440607', '3');
INSERT INTO `tao_region` VALUES ('1976', '高明区', '1971', '440608', '3');
INSERT INTO `tao_region` VALUES ('1977', '江门市', '1925', '440700', '2');
INSERT INTO `tao_region` VALUES ('1978', '蓬江区', '1977', '440703', '3');
INSERT INTO `tao_region` VALUES ('1979', '江海区', '1977', '440704', '3');
INSERT INTO `tao_region` VALUES ('1980', '新会区', '1977', '440705', '3');
INSERT INTO `tao_region` VALUES ('1981', '台山市', '1977', '440781', '3');
INSERT INTO `tao_region` VALUES ('1982', '开平市', '1977', '440783', '3');
INSERT INTO `tao_region` VALUES ('1983', '鹤山市', '1977', '440784', '3');
INSERT INTO `tao_region` VALUES ('1984', '恩平市', '1977', '440785', '3');
INSERT INTO `tao_region` VALUES ('1985', '湛江市', '1925', '440800', '2');
INSERT INTO `tao_region` VALUES ('1986', '赤坎区', '1985', '440802', '3');
INSERT INTO `tao_region` VALUES ('1987', '霞山区', '1985', '440803', '3');
INSERT INTO `tao_region` VALUES ('1988', '坡头区', '1985', '440804', '3');
INSERT INTO `tao_region` VALUES ('1989', '麻章区', '1985', '440811', '3');
INSERT INTO `tao_region` VALUES ('1990', '遂溪县', '1985', '440823', '3');
INSERT INTO `tao_region` VALUES ('1991', '徐闻县', '1985', '440825', '3');
INSERT INTO `tao_region` VALUES ('1992', '廉江市', '1985', '440881', '3');
INSERT INTO `tao_region` VALUES ('1993', '雷州市', '1985', '440882', '3');
INSERT INTO `tao_region` VALUES ('1994', '吴川市', '1985', '440883', '3');
INSERT INTO `tao_region` VALUES ('1995', '茂名市', '1925', '440900', '2');
INSERT INTO `tao_region` VALUES ('1996', '茂南区', '1995', '440902', '3');
INSERT INTO `tao_region` VALUES ('1997', '电白区', '1995', '440904', '3');
INSERT INTO `tao_region` VALUES ('1998', '高州市', '1995', '440981', '3');
INSERT INTO `tao_region` VALUES ('1999', '化州市', '1995', '440982', '3');
INSERT INTO `tao_region` VALUES ('2000', '信宜市', '1995', '440983', '3');
INSERT INTO `tao_region` VALUES ('2001', '肇庆市', '1925', '441200', '2');
INSERT INTO `tao_region` VALUES ('2002', '端州区', '2001', '441202', '3');
INSERT INTO `tao_region` VALUES ('2003', '鼎湖区', '2001', '441203', '3');
INSERT INTO `tao_region` VALUES ('2004', '高要区', '2001', '441204', '3');
INSERT INTO `tao_region` VALUES ('2005', '广宁县', '2001', '441223', '3');
INSERT INTO `tao_region` VALUES ('2006', '怀集县', '2001', '441224', '3');
INSERT INTO `tao_region` VALUES ('2007', '封开县', '2001', '441225', '3');
INSERT INTO `tao_region` VALUES ('2008', '德庆县', '2001', '441226', '3');
INSERT INTO `tao_region` VALUES ('2009', '四会市', '2001', '441284', '3');
INSERT INTO `tao_region` VALUES ('2010', '惠州市', '1925', '441300', '2');
INSERT INTO `tao_region` VALUES ('2011', '惠城区', '2010', '441302', '3');
INSERT INTO `tao_region` VALUES ('2012', '惠阳区', '2010', '441303', '3');
INSERT INTO `tao_region` VALUES ('2013', '博罗县', '2010', '441322', '3');
INSERT INTO `tao_region` VALUES ('2014', '惠东县', '2010', '441323', '3');
INSERT INTO `tao_region` VALUES ('2015', '龙门县', '2010', '441324', '3');
INSERT INTO `tao_region` VALUES ('2016', '梅州市', '1925', '441400', '2');
INSERT INTO `tao_region` VALUES ('2017', '梅江区', '2016', '441402', '3');
INSERT INTO `tao_region` VALUES ('2018', '梅县区', '2016', '441403', '3');
INSERT INTO `tao_region` VALUES ('2019', '大埔县', '2016', '441422', '3');
INSERT INTO `tao_region` VALUES ('2020', '丰顺县', '2016', '441423', '3');
INSERT INTO `tao_region` VALUES ('2021', '五华县', '2016', '441424', '3');
INSERT INTO `tao_region` VALUES ('2022', '平远县', '2016', '441426', '3');
INSERT INTO `tao_region` VALUES ('2023', '蕉岭县', '2016', '441427', '3');
INSERT INTO `tao_region` VALUES ('2024', '兴宁市', '2016', '441481', '3');
INSERT INTO `tao_region` VALUES ('2025', '汕尾市', '1925', '441500', '2');
INSERT INTO `tao_region` VALUES ('2026', '城区', '2025', '441502', '3');
INSERT INTO `tao_region` VALUES ('2027', '海丰县', '2025', '441521', '3');
INSERT INTO `tao_region` VALUES ('2028', '陆河县', '2025', '441523', '3');
INSERT INTO `tao_region` VALUES ('2029', '陆丰市', '2025', '441581', '3');
INSERT INTO `tao_region` VALUES ('2030', '河源市', '1925', '441600', '2');
INSERT INTO `tao_region` VALUES ('2031', '源城区', '2030', '441602', '3');
INSERT INTO `tao_region` VALUES ('2032', '紫金县', '2030', '441621', '3');
INSERT INTO `tao_region` VALUES ('2033', '龙川县', '2030', '441622', '3');
INSERT INTO `tao_region` VALUES ('2034', '连平县', '2030', '441623', '3');
INSERT INTO `tao_region` VALUES ('2035', '和平县', '2030', '441624', '3');
INSERT INTO `tao_region` VALUES ('2036', '东源县', '2030', '441625', '3');
INSERT INTO `tao_region` VALUES ('2037', '阳江市', '1925', '441700', '2');
INSERT INTO `tao_region` VALUES ('2038', '江城区', '2037', '441702', '3');
INSERT INTO `tao_region` VALUES ('2039', '阳东区', '2037', '441704', '3');
INSERT INTO `tao_region` VALUES ('2040', '阳西县', '2037', '441721', '3');
INSERT INTO `tao_region` VALUES ('2041', '阳春市', '2037', '441781', '3');
INSERT INTO `tao_region` VALUES ('2042', '清远市', '1925', '441800', '2');
INSERT INTO `tao_region` VALUES ('2043', '清城区', '2042', '441802', '3');
INSERT INTO `tao_region` VALUES ('2044', '清新区', '2042', '441803', '3');
INSERT INTO `tao_region` VALUES ('2045', '佛冈县', '2042', '441821', '3');
INSERT INTO `tao_region` VALUES ('2046', '阳山县', '2042', '441823', '3');
INSERT INTO `tao_region` VALUES ('2047', '连山壮族瑶族自治县', '2042', '441825', '3');
INSERT INTO `tao_region` VALUES ('2048', '连南瑶族自治县', '2042', '441826', '3');
INSERT INTO `tao_region` VALUES ('2049', '英德市', '2042', '441881', '3');
INSERT INTO `tao_region` VALUES ('2050', '连州市', '2042', '441882', '3');
INSERT INTO `tao_region` VALUES ('2051', '东莞市', '1925', '441900', '2');
INSERT INTO `tao_region` VALUES ('2052', '中山市', '1925', '442000', '2');
INSERT INTO `tao_region` VALUES ('2053', '潮州市', '1925', '445100', '2');
INSERT INTO `tao_region` VALUES ('2054', '湘桥区', '2053', '445102', '3');
INSERT INTO `tao_region` VALUES ('2055', '潮安区', '2053', '445103', '3');
INSERT INTO `tao_region` VALUES ('2056', '饶平县', '2053', '445122', '3');
INSERT INTO `tao_region` VALUES ('2057', '揭阳市', '1925', '445200', '2');
INSERT INTO `tao_region` VALUES ('2058', '榕城区', '2057', '445202', '3');
INSERT INTO `tao_region` VALUES ('2059', '揭东区', '2057', '445203', '3');
INSERT INTO `tao_region` VALUES ('2060', '揭西县', '2057', '445222', '3');
INSERT INTO `tao_region` VALUES ('2061', '惠来县', '2057', '445224', '3');
INSERT INTO `tao_region` VALUES ('2062', '普宁市', '2057', '445281', '3');
INSERT INTO `tao_region` VALUES ('2063', '云浮市', '1925', '445300', '2');
INSERT INTO `tao_region` VALUES ('2064', '云城区', '2063', '445302', '3');
INSERT INTO `tao_region` VALUES ('2065', '云安区', '2063', '445303', '3');
INSERT INTO `tao_region` VALUES ('2066', '新兴县', '2063', '445321', '3');
INSERT INTO `tao_region` VALUES ('2067', '郁南县', '2063', '445322', '3');
INSERT INTO `tao_region` VALUES ('2068', '罗定市', '2063', '445381', '3');
INSERT INTO `tao_region` VALUES ('2069', '广西壮族自治区', '0', '450000', '1');
INSERT INTO `tao_region` VALUES ('2070', '南宁市', '2069', '450100', '2');
INSERT INTO `tao_region` VALUES ('2071', '兴宁区', '2070', '450102', '3');
INSERT INTO `tao_region` VALUES ('2072', '青秀区', '2070', '450103', '3');
INSERT INTO `tao_region` VALUES ('2073', '江南区', '2070', '450105', '3');
INSERT INTO `tao_region` VALUES ('2074', '西乡塘区', '2070', '450107', '3');
INSERT INTO `tao_region` VALUES ('2075', '良庆区', '2070', '450108', '3');
INSERT INTO `tao_region` VALUES ('2076', '邕宁区', '2070', '450109', '3');
INSERT INTO `tao_region` VALUES ('2077', '武鸣区', '2070', '450110', '3');
INSERT INTO `tao_region` VALUES ('2078', '隆安县', '2070', '450123', '3');
INSERT INTO `tao_region` VALUES ('2079', '马山县', '2070', '450124', '3');
INSERT INTO `tao_region` VALUES ('2080', '上林县', '2070', '450125', '3');
INSERT INTO `tao_region` VALUES ('2081', '宾阳县', '2070', '450126', '3');
INSERT INTO `tao_region` VALUES ('2083', '柳州市', '2069', '450200', '2');
INSERT INTO `tao_region` VALUES ('2084', '城中区', '2083', '450202', '3');
INSERT INTO `tao_region` VALUES ('2085', '鱼峰区', '2083', '450203', '3');
INSERT INTO `tao_region` VALUES ('2086', '柳南区', '2083', '450204', '3');
INSERT INTO `tao_region` VALUES ('2087', '柳北区', '2083', '450205', '3');
INSERT INTO `tao_region` VALUES ('2088', '柳江区', '2083', '450206', '3');
INSERT INTO `tao_region` VALUES ('2089', '柳城县', '2083', '450222', '3');
INSERT INTO `tao_region` VALUES ('2090', '鹿寨县', '2083', '450223', '3');
INSERT INTO `tao_region` VALUES ('2091', '融安县', '2083', '450224', '3');
INSERT INTO `tao_region` VALUES ('2092', '融水苗族自治县', '2083', '450225', '3');
INSERT INTO `tao_region` VALUES ('2093', '三江侗族自治县', '2083', '450226', '3');
INSERT INTO `tao_region` VALUES ('2094', '桂林市', '2069', '450300', '2');
INSERT INTO `tao_region` VALUES ('2095', '秀峰区', '2094', '450302', '3');
INSERT INTO `tao_region` VALUES ('2096', '叠彩区', '2094', '450303', '3');
INSERT INTO `tao_region` VALUES ('2097', '象山区', '2094', '450304', '3');
INSERT INTO `tao_region` VALUES ('2098', '七星区', '2094', '450305', '3');
INSERT INTO `tao_region` VALUES ('2099', '雁山区', '2094', '450311', '3');
INSERT INTO `tao_region` VALUES ('2100', '临桂区', '2094', '450312', '3');
INSERT INTO `tao_region` VALUES ('2101', '阳朔县', '2094', '450321', '3');
INSERT INTO `tao_region` VALUES ('2102', '灵川县', '2094', '450323', '3');
INSERT INTO `tao_region` VALUES ('2103', '全州县', '2094', '450324', '3');
INSERT INTO `tao_region` VALUES ('2104', '兴安县', '2094', '450325', '3');
INSERT INTO `tao_region` VALUES ('2105', '永福县', '2094', '450326', '3');
INSERT INTO `tao_region` VALUES ('2106', '灌阳县', '2094', '450327', '3');
INSERT INTO `tao_region` VALUES ('2107', '龙胜各族自治县', '2094', '450328', '3');
INSERT INTO `tao_region` VALUES ('2108', '资源县', '2094', '450329', '3');
INSERT INTO `tao_region` VALUES ('2109', '平乐县', '2094', '450330', '3');
INSERT INTO `tao_region` VALUES ('2110', '荔浦市', '2094', '450381', '3');
INSERT INTO `tao_region` VALUES ('2111', '恭城瑶族自治县', '2094', '450332', '3');
INSERT INTO `tao_region` VALUES ('2112', '梧州市', '2069', '450400', '2');
INSERT INTO `tao_region` VALUES ('2113', '万秀区', '2112', '450403', '3');
INSERT INTO `tao_region` VALUES ('2114', '长洲区', '2112', '450405', '3');
INSERT INTO `tao_region` VALUES ('2115', '龙圩区', '2112', '450406', '3');
INSERT INTO `tao_region` VALUES ('2116', '苍梧县', '2112', '450421', '3');
INSERT INTO `tao_region` VALUES ('2117', '藤县', '2112', '450422', '3');
INSERT INTO `tao_region` VALUES ('2118', '蒙山县', '2112', '450423', '3');
INSERT INTO `tao_region` VALUES ('2119', '岑溪市', '2112', '450481', '3');
INSERT INTO `tao_region` VALUES ('2120', '北海市', '2069', '450500', '2');
INSERT INTO `tao_region` VALUES ('2121', '海城区', '2120', '450502', '3');
INSERT INTO `tao_region` VALUES ('2122', '银海区', '2120', '450503', '3');
INSERT INTO `tao_region` VALUES ('2123', '铁山港区', '2120', '450512', '3');
INSERT INTO `tao_region` VALUES ('2124', '合浦县', '2120', '450521', '3');
INSERT INTO `tao_region` VALUES ('2125', '防城港市', '2069', '450600', '2');
INSERT INTO `tao_region` VALUES ('2126', '港口区', '2125', '450602', '3');
INSERT INTO `tao_region` VALUES ('2127', '防城区', '2125', '450603', '3');
INSERT INTO `tao_region` VALUES ('2128', '上思县', '2125', '450621', '3');
INSERT INTO `tao_region` VALUES ('2129', '东兴市', '2125', '450681', '3');
INSERT INTO `tao_region` VALUES ('2130', '钦州市', '2069', '450700', '2');
INSERT INTO `tao_region` VALUES ('2131', '钦南区', '2130', '450702', '3');
INSERT INTO `tao_region` VALUES ('2132', '钦北区', '2130', '450703', '3');
INSERT INTO `tao_region` VALUES ('2133', '灵山县', '2130', '450721', '3');
INSERT INTO `tao_region` VALUES ('2134', '浦北县', '2130', '450722', '3');
INSERT INTO `tao_region` VALUES ('2135', '贵港市', '2069', '450800', '2');
INSERT INTO `tao_region` VALUES ('2136', '港北区', '2135', '450802', '3');
INSERT INTO `tao_region` VALUES ('2137', '港南区', '2135', '450803', '3');
INSERT INTO `tao_region` VALUES ('2138', '覃塘区', '2135', '450804', '3');
INSERT INTO `tao_region` VALUES ('2139', '平南县', '2135', '450821', '3');
INSERT INTO `tao_region` VALUES ('2140', '桂平市', '2135', '450881', '3');
INSERT INTO `tao_region` VALUES ('2141', '玉林市', '2069', '450900', '2');
INSERT INTO `tao_region` VALUES ('2142', '玉州区', '2141', '450902', '3');
INSERT INTO `tao_region` VALUES ('2143', '福绵区', '2141', '450903', '3');
INSERT INTO `tao_region` VALUES ('2144', '容县', '2141', '450921', '3');
INSERT INTO `tao_region` VALUES ('2145', '陆川县', '2141', '450922', '3');
INSERT INTO `tao_region` VALUES ('2146', '博白县', '2141', '450923', '3');
INSERT INTO `tao_region` VALUES ('2147', '兴业县', '2141', '450924', '3');
INSERT INTO `tao_region` VALUES ('2148', '北流市', '2141', '450981', '3');
INSERT INTO `tao_region` VALUES ('2149', '百色市', '2069', '451000', '2');
INSERT INTO `tao_region` VALUES ('2150', '右江区', '2149', '451002', '3');
INSERT INTO `tao_region` VALUES ('2151', '田阳区', '2149', '451003', '3');
INSERT INTO `tao_region` VALUES ('2152', '田东县', '2149', '451022', '3');
INSERT INTO `tao_region` VALUES ('2153', '德保县', '2149', '451024', '3');
INSERT INTO `tao_region` VALUES ('2154', '那坡县', '2149', '451026', '3');
INSERT INTO `tao_region` VALUES ('2155', '凌云县', '2149', '451027', '3');
INSERT INTO `tao_region` VALUES ('2156', '乐业县', '2149', '451028', '3');
INSERT INTO `tao_region` VALUES ('2157', '田林县', '2149', '451029', '3');
INSERT INTO `tao_region` VALUES ('2158', '西林县', '2149', '451030', '3');
INSERT INTO `tao_region` VALUES ('2159', '隆林各族自治县', '2149', '451031', '3');
INSERT INTO `tao_region` VALUES ('2160', '靖西市', '2149', '451081', '3');
INSERT INTO `tao_region` VALUES ('2161', '平果市', '2149', '451082', '3');
INSERT INTO `tao_region` VALUES ('2162', '贺州市', '2069', '451100', '2');
INSERT INTO `tao_region` VALUES ('2163', '八步区', '2162', '451102', '3');
INSERT INTO `tao_region` VALUES ('2164', '平桂区', '2162', '451103', '3');
INSERT INTO `tao_region` VALUES ('2165', '昭平县', '2162', '451121', '3');
INSERT INTO `tao_region` VALUES ('2166', '钟山县', '2162', '451122', '3');
INSERT INTO `tao_region` VALUES ('2167', '富川瑶族自治县', '2162', '451123', '3');
INSERT INTO `tao_region` VALUES ('2168', '河池市', '2069', '451200', '2');
INSERT INTO `tao_region` VALUES ('2169', '金城江区', '2168', '451202', '3');
INSERT INTO `tao_region` VALUES ('2170', '宜州区', '2168', '451203', '3');
INSERT INTO `tao_region` VALUES ('2171', '南丹县', '2168', '451221', '3');
INSERT INTO `tao_region` VALUES ('2172', '天峨县', '2168', '451222', '3');
INSERT INTO `tao_region` VALUES ('2173', '凤山县', '2168', '451223', '3');
INSERT INTO `tao_region` VALUES ('2174', '东兰县', '2168', '451224', '3');
INSERT INTO `tao_region` VALUES ('2175', '罗城仫佬族自治县', '2168', '451225', '3');
INSERT INTO `tao_region` VALUES ('2176', '环江毛南族自治县', '2168', '451226', '3');
INSERT INTO `tao_region` VALUES ('2177', '巴马瑶族自治县', '2168', '451227', '3');
INSERT INTO `tao_region` VALUES ('2178', '都安瑶族自治县', '2168', '451228', '3');
INSERT INTO `tao_region` VALUES ('2179', '大化瑶族自治县', '2168', '451229', '3');
INSERT INTO `tao_region` VALUES ('2180', '来宾市', '2069', '451300', '2');
INSERT INTO `tao_region` VALUES ('2181', '兴宾区', '2180', '451302', '3');
INSERT INTO `tao_region` VALUES ('2182', '忻城县', '2180', '451321', '3');
INSERT INTO `tao_region` VALUES ('2183', '象州县', '2180', '451322', '3');
INSERT INTO `tao_region` VALUES ('2184', '武宣县', '2180', '451323', '3');
INSERT INTO `tao_region` VALUES ('2185', '金秀瑶族自治县', '2180', '451324', '3');
INSERT INTO `tao_region` VALUES ('2186', '合山市', '2180', '451381', '3');
INSERT INTO `tao_region` VALUES ('2187', '崇左市', '2069', '451400', '2');
INSERT INTO `tao_region` VALUES ('2188', '江州区', '2187', '451402', '3');
INSERT INTO `tao_region` VALUES ('2189', '扶绥县', '2187', '451421', '3');
INSERT INTO `tao_region` VALUES ('2190', '宁明县', '2187', '451422', '3');
INSERT INTO `tao_region` VALUES ('2191', '龙州县', '2187', '451423', '3');
INSERT INTO `tao_region` VALUES ('2192', '大新县', '2187', '451424', '3');
INSERT INTO `tao_region` VALUES ('2193', '天等县', '2187', '451425', '3');
INSERT INTO `tao_region` VALUES ('2194', '凭祥市', '2187', '451481', '3');
INSERT INTO `tao_region` VALUES ('2195', '海南省', '0', '460000', '1');
INSERT INTO `tao_region` VALUES ('2196', '海口市', '2195', '460100', '2');
INSERT INTO `tao_region` VALUES ('2197', '秀英区', '2196', '460105', '3');
INSERT INTO `tao_region` VALUES ('2198', '龙华区', '2196', '460106', '3');
INSERT INTO `tao_region` VALUES ('2199', '琼山区', '2196', '460107', '3');
INSERT INTO `tao_region` VALUES ('2200', '美兰区', '2196', '460108', '3');
INSERT INTO `tao_region` VALUES ('2201', '三亚市', '2195', '460200', '2');
INSERT INTO `tao_region` VALUES ('2202', '海棠区', '2201', '460202', '3');
INSERT INTO `tao_region` VALUES ('2203', '吉阳区', '2201', '460203', '3');
INSERT INTO `tao_region` VALUES ('2204', '天涯区', '2201', '460204', '3');
INSERT INTO `tao_region` VALUES ('2205', '崖州区', '2201', '460205', '3');
INSERT INTO `tao_region` VALUES ('2206', '三沙市', '2195', '460300', '2');
INSERT INTO `tao_region` VALUES ('2207', '儋州市', '2195', '460400', '2');
INSERT INTO `tao_region` VALUES ('2223', '重庆市', '0', '500000', '1');
INSERT INTO `tao_region` VALUES ('2224', '重庆市', '2223', '500100', '2');
INSERT INTO `tao_region` VALUES ('2225', '万州区', '2224', '500101', '3');
INSERT INTO `tao_region` VALUES ('2226', '涪陵区', '2224', '500102', '3');
INSERT INTO `tao_region` VALUES ('2227', '渝中区', '2224', '500103', '3');
INSERT INTO `tao_region` VALUES ('2228', '大渡口区', '2224', '500104', '3');
INSERT INTO `tao_region` VALUES ('2229', '江北区', '2224', '500105', '3');
INSERT INTO `tao_region` VALUES ('2230', '沙坪坝区', '2224', '500106', '3');
INSERT INTO `tao_region` VALUES ('2231', '九龙坡区', '2224', '500107', '3');
INSERT INTO `tao_region` VALUES ('2232', '南岸区', '2224', '500108', '3');
INSERT INTO `tao_region` VALUES ('2233', '北碚区', '2224', '500109', '3');
INSERT INTO `tao_region` VALUES ('2234', '綦江区', '2224', '500110', '3');
INSERT INTO `tao_region` VALUES ('2235', '大足区', '2224', '500111', '3');
INSERT INTO `tao_region` VALUES ('2236', '渝北区', '2224', '500112', '3');
INSERT INTO `tao_region` VALUES ('2237', '巴南区', '2224', '500113', '3');
INSERT INTO `tao_region` VALUES ('2238', '黔江区', '2224', '500114', '3');
INSERT INTO `tao_region` VALUES ('2239', '长寿区', '2224', '500115', '3');
INSERT INTO `tao_region` VALUES ('2240', '江津区', '2224', '500116', '3');
INSERT INTO `tao_region` VALUES ('2241', '合川区', '2224', '500117', '3');
INSERT INTO `tao_region` VALUES ('2242', '永川区', '2224', '500118', '3');
INSERT INTO `tao_region` VALUES ('2243', '南川区', '2224', '500119', '3');
INSERT INTO `tao_region` VALUES ('2244', '璧山区', '2224', '500120', '3');
INSERT INTO `tao_region` VALUES ('2245', '铜梁区', '2224', '500151', '3');
INSERT INTO `tao_region` VALUES ('2246', '潼南区', '2224', '500152', '3');
INSERT INTO `tao_region` VALUES ('2247', '荣昌区', '2224', '500153', '3');
INSERT INTO `tao_region` VALUES ('2248', '开州区', '2224', '500154', '3');
INSERT INTO `tao_region` VALUES ('2249', '梁平区', '2224', '500155', '3');
INSERT INTO `tao_region` VALUES ('2250', '武隆区', '2224', '500156', '3');
INSERT INTO `tao_region` VALUES ('2263', '四川省', '0', '510000', '1');
INSERT INTO `tao_region` VALUES ('2264', '成都市', '2263', '510100', '2');
INSERT INTO `tao_region` VALUES ('2265', '锦江区', '2264', '510104', '3');
INSERT INTO `tao_region` VALUES ('2266', '青羊区', '2264', '510105', '3');
INSERT INTO `tao_region` VALUES ('2267', '金牛区', '2264', '510106', '3');
INSERT INTO `tao_region` VALUES ('2268', '武侯区', '2264', '510107', '3');
INSERT INTO `tao_region` VALUES ('2269', '成华区', '2264', '510108', '3');
INSERT INTO `tao_region` VALUES ('2270', '龙泉驿区', '2264', '510112', '3');
INSERT INTO `tao_region` VALUES ('2271', '青白江区', '2264', '510113', '3');
INSERT INTO `tao_region` VALUES ('2272', '新都区', '2264', '510114', '3');
INSERT INTO `tao_region` VALUES ('2273', '温江区', '2264', '510115', '3');
INSERT INTO `tao_region` VALUES ('2274', '双流区', '2264', '510116', '3');
INSERT INTO `tao_region` VALUES ('2275', '郫都区', '2264', '510117', '3');
INSERT INTO `tao_region` VALUES ('2276', '金堂县', '2264', '510121', '3');
INSERT INTO `tao_region` VALUES ('2277', '大邑县', '2264', '510129', '3');
INSERT INTO `tao_region` VALUES ('2278', '蒲江县', '2264', '510131', '3');
INSERT INTO `tao_region` VALUES ('2280', '都江堰市', '2264', '510181', '3');
INSERT INTO `tao_region` VALUES ('2281', '彭州市', '2264', '510182', '3');
INSERT INTO `tao_region` VALUES ('2282', '邛崃市', '2264', '510183', '3');
INSERT INTO `tao_region` VALUES ('2283', '崇州市', '2264', '510184', '3');
INSERT INTO `tao_region` VALUES ('2284', '简阳市', '2264', '510185', '3');
INSERT INTO `tao_region` VALUES ('2285', '自贡市', '2263', '510300', '2');
INSERT INTO `tao_region` VALUES ('2286', '自流井区', '2285', '510302', '3');
INSERT INTO `tao_region` VALUES ('2287', '贡井区', '2285', '510303', '3');
INSERT INTO `tao_region` VALUES ('2288', '大安区', '2285', '510304', '3');
INSERT INTO `tao_region` VALUES ('2289', '沿滩区', '2285', '510311', '3');
INSERT INTO `tao_region` VALUES ('2290', '荣县', '2285', '510321', '3');
INSERT INTO `tao_region` VALUES ('2291', '富顺县', '2285', '510322', '3');
INSERT INTO `tao_region` VALUES ('2292', '攀枝花市', '2263', '510400', '2');
INSERT INTO `tao_region` VALUES ('2293', '东区', '2292', '510402', '3');
INSERT INTO `tao_region` VALUES ('2294', '西区', '2292', '510403', '3');
INSERT INTO `tao_region` VALUES ('2295', '仁和区', '2292', '510411', '3');
INSERT INTO `tao_region` VALUES ('2296', '米易县', '2292', '510421', '3');
INSERT INTO `tao_region` VALUES ('2297', '盐边县', '2292', '510422', '3');
INSERT INTO `tao_region` VALUES ('2298', '泸州市', '2263', '510500', '2');
INSERT INTO `tao_region` VALUES ('2299', '江阳区', '2298', '510502', '3');
INSERT INTO `tao_region` VALUES ('2300', '纳溪区', '2298', '510503', '3');
INSERT INTO `tao_region` VALUES ('2301', '龙马潭区', '2298', '510504', '3');
INSERT INTO `tao_region` VALUES ('2302', '泸县', '2298', '510521', '3');
INSERT INTO `tao_region` VALUES ('2303', '合江县', '2298', '510522', '3');
INSERT INTO `tao_region` VALUES ('2304', '叙永县', '2298', '510524', '3');
INSERT INTO `tao_region` VALUES ('2305', '古蔺县', '2298', '510525', '3');
INSERT INTO `tao_region` VALUES ('2306', '德阳市', '2263', '510600', '2');
INSERT INTO `tao_region` VALUES ('2307', '旌阳区', '2306', '510603', '3');
INSERT INTO `tao_region` VALUES ('2308', '罗江区', '2306', '510604', '3');
INSERT INTO `tao_region` VALUES ('2309', '中江县', '2306', '510623', '3');
INSERT INTO `tao_region` VALUES ('2310', '广汉市', '2306', '510681', '3');
INSERT INTO `tao_region` VALUES ('2311', '什邡市', '2306', '510682', '3');
INSERT INTO `tao_region` VALUES ('2312', '绵竹市', '2306', '510683', '3');
INSERT INTO `tao_region` VALUES ('2313', '绵阳市', '2263', '510700', '2');
INSERT INTO `tao_region` VALUES ('2314', '涪城区', '2313', '510703', '3');
INSERT INTO `tao_region` VALUES ('2315', '游仙区', '2313', '510704', '3');
INSERT INTO `tao_region` VALUES ('2316', '安州区', '2313', '510705', '3');
INSERT INTO `tao_region` VALUES ('2317', '三台县', '2313', '510722', '3');
INSERT INTO `tao_region` VALUES ('2318', '盐亭县', '2313', '510723', '3');
INSERT INTO `tao_region` VALUES ('2319', '梓潼县', '2313', '510725', '3');
INSERT INTO `tao_region` VALUES ('2320', '北川羌族自治县', '2313', '510726', '3');
INSERT INTO `tao_region` VALUES ('2321', '平武县', '2313', '510727', '3');
INSERT INTO `tao_region` VALUES ('2322', '江油市', '2313', '510781', '3');
INSERT INTO `tao_region` VALUES ('2323', '广元市', '2263', '510800', '2');
INSERT INTO `tao_region` VALUES ('2324', '利州区', '2323', '510802', '3');
INSERT INTO `tao_region` VALUES ('2325', '昭化区', '2323', '510811', '3');
INSERT INTO `tao_region` VALUES ('2326', '朝天区', '2323', '510812', '3');
INSERT INTO `tao_region` VALUES ('2327', '旺苍县', '2323', '510821', '3');
INSERT INTO `tao_region` VALUES ('2328', '青川县', '2323', '510822', '3');
INSERT INTO `tao_region` VALUES ('2329', '剑阁县', '2323', '510823', '3');
INSERT INTO `tao_region` VALUES ('2330', '苍溪县', '2323', '510824', '3');
INSERT INTO `tao_region` VALUES ('2331', '遂宁市', '2263', '510900', '2');
INSERT INTO `tao_region` VALUES ('2332', '船山区', '2331', '510903', '3');
INSERT INTO `tao_region` VALUES ('2333', '安居区', '2331', '510904', '3');
INSERT INTO `tao_region` VALUES ('2334', '蓬溪县', '2331', '510921', '3');
INSERT INTO `tao_region` VALUES ('2335', '大英县', '2331', '510923', '3');
INSERT INTO `tao_region` VALUES ('2336', '射洪市', '2331', '510981', '3');
INSERT INTO `tao_region` VALUES ('2337', '内江市', '2263', '511000', '2');
INSERT INTO `tao_region` VALUES ('2338', '市中区', '2337', '511002', '3');
INSERT INTO `tao_region` VALUES ('2339', '东兴区', '2337', '511011', '3');
INSERT INTO `tao_region` VALUES ('2340', '威远县', '2337', '511024', '3');
INSERT INTO `tao_region` VALUES ('2341', '资中县', '2337', '511025', '3');
INSERT INTO `tao_region` VALUES ('2342', '隆昌市', '2337', '511083', '3');
INSERT INTO `tao_region` VALUES ('2343', '乐山市', '2263', '511100', '2');
INSERT INTO `tao_region` VALUES ('2344', '市中区', '2343', '511102', '3');
INSERT INTO `tao_region` VALUES ('2345', '沙湾区', '2343', '511111', '3');
INSERT INTO `tao_region` VALUES ('2346', '五通桥区', '2343', '511112', '3');
INSERT INTO `tao_region` VALUES ('2347', '金口河区', '2343', '511113', '3');
INSERT INTO `tao_region` VALUES ('2348', '犍为县', '2343', '511123', '3');
INSERT INTO `tao_region` VALUES ('2349', '井研县', '2343', '511124', '3');
INSERT INTO `tao_region` VALUES ('2350', '夹江县', '2343', '511126', '3');
INSERT INTO `tao_region` VALUES ('2351', '沐川县', '2343', '511129', '3');
INSERT INTO `tao_region` VALUES ('2352', '峨边彝族自治县', '2343', '511132', '3');
INSERT INTO `tao_region` VALUES ('2353', '马边彝族自治县', '2343', '511133', '3');
INSERT INTO `tao_region` VALUES ('2354', '峨眉山市', '2343', '511181', '3');
INSERT INTO `tao_region` VALUES ('2355', '南充市', '2263', '511300', '2');
INSERT INTO `tao_region` VALUES ('2356', '顺庆区', '2355', '511302', '3');
INSERT INTO `tao_region` VALUES ('2357', '高坪区', '2355', '511303', '3');
INSERT INTO `tao_region` VALUES ('2358', '嘉陵区', '2355', '511304', '3');
INSERT INTO `tao_region` VALUES ('2359', '南部县', '2355', '511321', '3');
INSERT INTO `tao_region` VALUES ('2360', '营山县', '2355', '511322', '3');
INSERT INTO `tao_region` VALUES ('2361', '蓬安县', '2355', '511323', '3');
INSERT INTO `tao_region` VALUES ('2362', '仪陇县', '2355', '511324', '3');
INSERT INTO `tao_region` VALUES ('2363', '西充县', '2355', '511325', '3');
INSERT INTO `tao_region` VALUES ('2364', '阆中市', '2355', '511381', '3');
INSERT INTO `tao_region` VALUES ('2365', '眉山市', '2263', '511400', '2');
INSERT INTO `tao_region` VALUES ('2366', '东坡区', '2365', '511402', '3');
INSERT INTO `tao_region` VALUES ('2367', '彭山区', '2365', '511403', '3');
INSERT INTO `tao_region` VALUES ('2368', '仁寿县', '2365', '511421', '3');
INSERT INTO `tao_region` VALUES ('2369', '洪雅县', '2365', '511423', '3');
INSERT INTO `tao_region` VALUES ('2370', '丹棱县', '2365', '511424', '3');
INSERT INTO `tao_region` VALUES ('2371', '青神县', '2365', '511425', '3');
INSERT INTO `tao_region` VALUES ('2372', '宜宾市', '2263', '511500', '2');
INSERT INTO `tao_region` VALUES ('2373', '翠屏区', '2372', '511502', '3');
INSERT INTO `tao_region` VALUES ('2374', '南溪区', '2372', '511503', '3');
INSERT INTO `tao_region` VALUES ('2375', '叙州区', '2372', '511504', '3');
INSERT INTO `tao_region` VALUES ('2376', '江安县', '2372', '511523', '3');
INSERT INTO `tao_region` VALUES ('2377', '长宁县', '2372', '511524', '3');
INSERT INTO `tao_region` VALUES ('2378', '高县', '2372', '511525', '3');
INSERT INTO `tao_region` VALUES ('2379', '珙县', '2372', '511526', '3');
INSERT INTO `tao_region` VALUES ('2380', '筠连县', '2372', '511527', '3');
INSERT INTO `tao_region` VALUES ('2381', '兴文县', '2372', '511528', '3');
INSERT INTO `tao_region` VALUES ('2382', '屏山县', '2372', '511529', '3');
INSERT INTO `tao_region` VALUES ('2383', '广安市', '2263', '511600', '2');
INSERT INTO `tao_region` VALUES ('2384', '广安区', '2383', '511602', '3');
INSERT INTO `tao_region` VALUES ('2385', '前锋区', '2383', '511603', '3');
INSERT INTO `tao_region` VALUES ('2386', '岳池县', '2383', '511621', '3');
INSERT INTO `tao_region` VALUES ('2387', '武胜县', '2383', '511622', '3');
INSERT INTO `tao_region` VALUES ('2388', '邻水县', '2383', '511623', '3');
INSERT INTO `tao_region` VALUES ('2389', '华蓥市', '2383', '511681', '3');
INSERT INTO `tao_region` VALUES ('2390', '达州市', '2263', '511700', '2');
INSERT INTO `tao_region` VALUES ('2391', '通川区', '2390', '511702', '3');
INSERT INTO `tao_region` VALUES ('2392', '达川区', '2390', '511703', '3');
INSERT INTO `tao_region` VALUES ('2393', '宣汉县', '2390', '511722', '3');
INSERT INTO `tao_region` VALUES ('2394', '开江县', '2390', '511723', '3');
INSERT INTO `tao_region` VALUES ('2395', '大竹县', '2390', '511724', '3');
INSERT INTO `tao_region` VALUES ('2396', '渠县', '2390', '511725', '3');
INSERT INTO `tao_region` VALUES ('2397', '万源市', '2390', '511781', '3');
INSERT INTO `tao_region` VALUES ('2398', '雅安市', '2263', '511800', '2');
INSERT INTO `tao_region` VALUES ('2399', '雨城区', '2398', '511802', '3');
INSERT INTO `tao_region` VALUES ('2400', '名山区', '2398', '511803', '3');
INSERT INTO `tao_region` VALUES ('2401', '荥经县', '2398', '511822', '3');
INSERT INTO `tao_region` VALUES ('2402', '汉源县', '2398', '511823', '3');
INSERT INTO `tao_region` VALUES ('2403', '石棉县', '2398', '511824', '3');
INSERT INTO `tao_region` VALUES ('2404', '天全县', '2398', '511825', '3');
INSERT INTO `tao_region` VALUES ('2405', '芦山县', '2398', '511826', '3');
INSERT INTO `tao_region` VALUES ('2406', '宝兴县', '2398', '511827', '3');
INSERT INTO `tao_region` VALUES ('2407', '巴中市', '2263', '511900', '2');
INSERT INTO `tao_region` VALUES ('2408', '巴州区', '2407', '511902', '3');
INSERT INTO `tao_region` VALUES ('2409', '恩阳区', '2407', '511903', '3');
INSERT INTO `tao_region` VALUES ('2410', '通江县', '2407', '511921', '3');
INSERT INTO `tao_region` VALUES ('2411', '南江县', '2407', '511922', '3');
INSERT INTO `tao_region` VALUES ('2412', '平昌县', '2407', '511923', '3');
INSERT INTO `tao_region` VALUES ('2413', '资阳市', '2263', '512000', '2');
INSERT INTO `tao_region` VALUES ('2414', '雁江区', '2413', '512002', '3');
INSERT INTO `tao_region` VALUES ('2415', '安岳县', '2413', '512021', '3');
INSERT INTO `tao_region` VALUES ('2416', '乐至县', '2413', '512022', '3');
INSERT INTO `tao_region` VALUES ('2417', '阿坝藏族羌族自治州', '2263', '513200', '2');
INSERT INTO `tao_region` VALUES ('2418', '马尔康市', '2417', '513201', '3');
INSERT INTO `tao_region` VALUES ('2419', '汶川县', '2417', '513221', '3');
INSERT INTO `tao_region` VALUES ('2420', '理县', '2417', '513222', '3');
INSERT INTO `tao_region` VALUES ('2421', '茂县', '2417', '513223', '3');
INSERT INTO `tao_region` VALUES ('2422', '松潘县', '2417', '513224', '3');
INSERT INTO `tao_region` VALUES ('2423', '九寨沟县', '2417', '513225', '3');
INSERT INTO `tao_region` VALUES ('2424', '金川县', '2417', '513226', '3');
INSERT INTO `tao_region` VALUES ('2425', '小金县', '2417', '513227', '3');
INSERT INTO `tao_region` VALUES ('2426', '黑水县', '2417', '513228', '3');
INSERT INTO `tao_region` VALUES ('2427', '壤塘县', '2417', '513230', '3');
INSERT INTO `tao_region` VALUES ('2428', '阿坝县', '2417', '513231', '3');
INSERT INTO `tao_region` VALUES ('2429', '若尔盖县', '2417', '513232', '3');
INSERT INTO `tao_region` VALUES ('2430', '红原县', '2417', '513233', '3');
INSERT INTO `tao_region` VALUES ('2431', '甘孜藏族自治州', '2263', '513300', '2');
INSERT INTO `tao_region` VALUES ('2432', '康定市', '2431', '513301', '3');
INSERT INTO `tao_region` VALUES ('2433', '泸定县', '2431', '513322', '3');
INSERT INTO `tao_region` VALUES ('2434', '丹巴县', '2431', '513323', '3');
INSERT INTO `tao_region` VALUES ('2435', '九龙县', '2431', '513324', '3');
INSERT INTO `tao_region` VALUES ('2436', '雅江县', '2431', '513325', '3');
INSERT INTO `tao_region` VALUES ('2437', '道孚县', '2431', '513326', '3');
INSERT INTO `tao_region` VALUES ('2438', '炉霍县', '2431', '513327', '3');
INSERT INTO `tao_region` VALUES ('2439', '甘孜县', '2431', '513328', '3');
INSERT INTO `tao_region` VALUES ('2440', '新龙县', '2431', '513329', '3');
INSERT INTO `tao_region` VALUES ('2441', '德格县', '2431', '513330', '3');
INSERT INTO `tao_region` VALUES ('2442', '白玉县', '2431', '513331', '3');
INSERT INTO `tao_region` VALUES ('2443', '石渠县', '2431', '513332', '3');
INSERT INTO `tao_region` VALUES ('2444', '色达县', '2431', '513333', '3');
INSERT INTO `tao_region` VALUES ('2445', '理塘县', '2431', '513334', '3');
INSERT INTO `tao_region` VALUES ('2446', '巴塘县', '2431', '513335', '3');
INSERT INTO `tao_region` VALUES ('2447', '乡城县', '2431', '513336', '3');
INSERT INTO `tao_region` VALUES ('2448', '稻城县', '2431', '513337', '3');
INSERT INTO `tao_region` VALUES ('2449', '得荣县', '2431', '513338', '3');
INSERT INTO `tao_region` VALUES ('2450', '凉山彝族自治州', '2263', '513400', '2');
INSERT INTO `tao_region` VALUES ('2451', '西昌市', '2450', '513401', '3');
INSERT INTO `tao_region` VALUES ('2452', '木里藏族自治县', '2450', '513422', '3');
INSERT INTO `tao_region` VALUES ('2453', '盐源县', '2450', '513423', '3');
INSERT INTO `tao_region` VALUES ('2454', '德昌县', '2450', '513424', '3');
INSERT INTO `tao_region` VALUES ('2456', '会东县', '2450', '513426', '3');
INSERT INTO `tao_region` VALUES ('2457', '宁南县', '2450', '513427', '3');
INSERT INTO `tao_region` VALUES ('2458', '普格县', '2450', '513428', '3');
INSERT INTO `tao_region` VALUES ('2459', '布拖县', '2450', '513429', '3');
INSERT INTO `tao_region` VALUES ('2460', '金阳县', '2450', '513430', '3');
INSERT INTO `tao_region` VALUES ('2461', '昭觉县', '2450', '513431', '3');
INSERT INTO `tao_region` VALUES ('2462', '喜德县', '2450', '513432', '3');
INSERT INTO `tao_region` VALUES ('2463', '冕宁县', '2450', '513433', '3');
INSERT INTO `tao_region` VALUES ('2464', '越西县', '2450', '513434', '3');
INSERT INTO `tao_region` VALUES ('2465', '甘洛县', '2450', '513435', '3');
INSERT INTO `tao_region` VALUES ('2466', '美姑县', '2450', '513436', '3');
INSERT INTO `tao_region` VALUES ('2467', '雷波县', '2450', '513437', '3');
INSERT INTO `tao_region` VALUES ('2468', '贵州省', '0', '520000', '1');
INSERT INTO `tao_region` VALUES ('2469', '贵阳市', '2468', '520100', '2');
INSERT INTO `tao_region` VALUES ('2470', '南明区', '2469', '520102', '3');
INSERT INTO `tao_region` VALUES ('2471', '云岩区', '2469', '520103', '3');
INSERT INTO `tao_region` VALUES ('2472', '花溪区', '2469', '520111', '3');
INSERT INTO `tao_region` VALUES ('2473', '乌当区', '2469', '520112', '3');
INSERT INTO `tao_region` VALUES ('2474', '白云区', '2469', '520113', '3');
INSERT INTO `tao_region` VALUES ('2475', '观山湖区', '2469', '520115', '3');
INSERT INTO `tao_region` VALUES ('2476', '开阳县', '2469', '520121', '3');
INSERT INTO `tao_region` VALUES ('2477', '息烽县', '2469', '520122', '3');
INSERT INTO `tao_region` VALUES ('2478', '修文县', '2469', '520123', '3');
INSERT INTO `tao_region` VALUES ('2479', '清镇市', '2469', '520181', '3');
INSERT INTO `tao_region` VALUES ('2480', '六盘水市', '2468', '520200', '2');
INSERT INTO `tao_region` VALUES ('2481', '钟山区', '2480', '520201', '3');
INSERT INTO `tao_region` VALUES ('2482', '六枝特区', '2480', '520203', '3');
INSERT INTO `tao_region` VALUES ('2484', '盘州市', '2480', '520281', '3');
INSERT INTO `tao_region` VALUES ('2485', '遵义市', '2468', '520300', '2');
INSERT INTO `tao_region` VALUES ('2486', '红花岗区', '2485', '520302', '3');
INSERT INTO `tao_region` VALUES ('2487', '汇川区', '2485', '520303', '3');
INSERT INTO `tao_region` VALUES ('2488', '播州区', '2485', '520304', '3');
INSERT INTO `tao_region` VALUES ('2489', '桐梓县', '2485', '520322', '3');
INSERT INTO `tao_region` VALUES ('2490', '绥阳县', '2485', '520323', '3');
INSERT INTO `tao_region` VALUES ('2491', '正安县', '2485', '520324', '3');
INSERT INTO `tao_region` VALUES ('2492', '道真仡佬族苗族自治县', '2485', '520325', '3');
INSERT INTO `tao_region` VALUES ('2493', '务川仡佬族苗族自治县', '2485', '520326', '3');
INSERT INTO `tao_region` VALUES ('2494', '凤冈县', '2485', '520327', '3');
INSERT INTO `tao_region` VALUES ('2495', '湄潭县', '2485', '520328', '3');
INSERT INTO `tao_region` VALUES ('2496', '余庆县', '2485', '520329', '3');
INSERT INTO `tao_region` VALUES ('2497', '习水县', '2485', '520330', '3');
INSERT INTO `tao_region` VALUES ('2498', '赤水市', '2485', '520381', '3');
INSERT INTO `tao_region` VALUES ('2499', '仁怀市', '2485', '520382', '3');
INSERT INTO `tao_region` VALUES ('2500', '安顺市', '2468', '520400', '2');
INSERT INTO `tao_region` VALUES ('2501', '西秀区', '2500', '520402', '3');
INSERT INTO `tao_region` VALUES ('2502', '平坝区', '2500', '520403', '3');
INSERT INTO `tao_region` VALUES ('2503', '普定县', '2500', '520422', '3');
INSERT INTO `tao_region` VALUES ('2504', '镇宁布依族苗族自治县', '2500', '520423', '3');
INSERT INTO `tao_region` VALUES ('2505', '关岭布依族苗族自治县', '2500', '520424', '3');
INSERT INTO `tao_region` VALUES ('2506', '紫云苗族布依族自治县', '2500', '520425', '3');
INSERT INTO `tao_region` VALUES ('2507', '毕节市', '2468', '520500', '2');
INSERT INTO `tao_region` VALUES ('2508', '七星关区', '2507', '520502', '3');
INSERT INTO `tao_region` VALUES ('2509', '大方县', '2507', '520521', '3');
INSERT INTO `tao_region` VALUES ('2511', '金沙县', '2507', '520523', '3');
INSERT INTO `tao_region` VALUES ('2512', '织金县', '2507', '520524', '3');
INSERT INTO `tao_region` VALUES ('2513', '纳雍县', '2507', '520525', '3');
INSERT INTO `tao_region` VALUES ('2514', '威宁彝族回族苗族自治县', '2507', '520526', '3');
INSERT INTO `tao_region` VALUES ('2515', '赫章县', '2507', '520527', '3');
INSERT INTO `tao_region` VALUES ('2516', '铜仁市', '2468', '520600', '2');
INSERT INTO `tao_region` VALUES ('2517', '碧江区', '2516', '520602', '3');
INSERT INTO `tao_region` VALUES ('2518', '万山区', '2516', '520603', '3');
INSERT INTO `tao_region` VALUES ('2519', '江口县', '2516', '520621', '3');
INSERT INTO `tao_region` VALUES ('2520', '玉屏侗族自治县', '2516', '520622', '3');
INSERT INTO `tao_region` VALUES ('2521', '石阡县', '2516', '520623', '3');
INSERT INTO `tao_region` VALUES ('2522', '思南县', '2516', '520624', '3');
INSERT INTO `tao_region` VALUES ('2523', '印江土家族苗族自治县', '2516', '520625', '3');
INSERT INTO `tao_region` VALUES ('2524', '德江县', '2516', '520626', '3');
INSERT INTO `tao_region` VALUES ('2525', '沿河土家族自治县', '2516', '520627', '3');
INSERT INTO `tao_region` VALUES ('2526', '松桃苗族自治县', '2516', '520628', '3');
INSERT INTO `tao_region` VALUES ('2527', '黔西南布依族苗族自治州', '2468', '522300', '2');
INSERT INTO `tao_region` VALUES ('2528', '兴义市', '2527', '522301', '3');
INSERT INTO `tao_region` VALUES ('2529', '兴仁市', '2527', '522302', '3');
INSERT INTO `tao_region` VALUES ('2530', '普安县', '2527', '522323', '3');
INSERT INTO `tao_region` VALUES ('2531', '晴隆县', '2527', '522324', '3');
INSERT INTO `tao_region` VALUES ('2532', '贞丰县', '2527', '522325', '3');
INSERT INTO `tao_region` VALUES ('2533', '望谟县', '2527', '522326', '3');
INSERT INTO `tao_region` VALUES ('2534', '册亨县', '2527', '522327', '3');
INSERT INTO `tao_region` VALUES ('2535', '安龙县', '2527', '522328', '3');
INSERT INTO `tao_region` VALUES ('2536', '黔东南苗族侗族自治州', '2468', '522600', '2');
INSERT INTO `tao_region` VALUES ('2537', '凯里市', '2536', '522601', '3');
INSERT INTO `tao_region` VALUES ('2538', '黄平县', '2536', '522622', '3');
INSERT INTO `tao_region` VALUES ('2539', '施秉县', '2536', '522623', '3');
INSERT INTO `tao_region` VALUES ('2540', '三穗县', '2536', '522624', '3');
INSERT INTO `tao_region` VALUES ('2541', '镇远县', '2536', '522625', '3');
INSERT INTO `tao_region` VALUES ('2542', '岑巩县', '2536', '522626', '3');
INSERT INTO `tao_region` VALUES ('2543', '天柱县', '2536', '522627', '3');
INSERT INTO `tao_region` VALUES ('2544', '锦屏县', '2536', '522628', '3');
INSERT INTO `tao_region` VALUES ('2545', '剑河县', '2536', '522629', '3');
INSERT INTO `tao_region` VALUES ('2546', '台江县', '2536', '522630', '3');
INSERT INTO `tao_region` VALUES ('2547', '黎平县', '2536', '522631', '3');
INSERT INTO `tao_region` VALUES ('2548', '榕江县', '2536', '522632', '3');
INSERT INTO `tao_region` VALUES ('2549', '从江县', '2536', '522633', '3');
INSERT INTO `tao_region` VALUES ('2550', '雷山县', '2536', '522634', '3');
INSERT INTO `tao_region` VALUES ('2551', '麻江县', '2536', '522635', '3');
INSERT INTO `tao_region` VALUES ('2552', '丹寨县', '2536', '522636', '3');
INSERT INTO `tao_region` VALUES ('2553', '黔南布依族苗族自治州', '2468', '522700', '2');
INSERT INTO `tao_region` VALUES ('2554', '都匀市', '2553', '522701', '3');
INSERT INTO `tao_region` VALUES ('2555', '福泉市', '2553', '522702', '3');
INSERT INTO `tao_region` VALUES ('2556', '荔波县', '2553', '522722', '3');
INSERT INTO `tao_region` VALUES ('2557', '贵定县', '2553', '522723', '3');
INSERT INTO `tao_region` VALUES ('2558', '瓮安县', '2553', '522725', '3');
INSERT INTO `tao_region` VALUES ('2559', '独山县', '2553', '522726', '3');
INSERT INTO `tao_region` VALUES ('2560', '平塘县', '2553', '522727', '3');
INSERT INTO `tao_region` VALUES ('2561', '罗甸县', '2553', '522728', '3');
INSERT INTO `tao_region` VALUES ('2562', '长顺县', '2553', '522729', '3');
INSERT INTO `tao_region` VALUES ('2563', '龙里县', '2553', '522730', '3');
INSERT INTO `tao_region` VALUES ('2564', '惠水县', '2553', '522731', '3');
INSERT INTO `tao_region` VALUES ('2565', '三都水族自治县', '2553', '522732', '3');
INSERT INTO `tao_region` VALUES ('2566', '云南省', '0', '530000', '1');
INSERT INTO `tao_region` VALUES ('2567', '昆明市', '2566', '530100', '2');
INSERT INTO `tao_region` VALUES ('2568', '五华区', '2567', '530102', '3');
INSERT INTO `tao_region` VALUES ('2569', '盘龙区', '2567', '530103', '3');
INSERT INTO `tao_region` VALUES ('2570', '官渡区', '2567', '530111', '3');
INSERT INTO `tao_region` VALUES ('2571', '西山区', '2567', '530112', '3');
INSERT INTO `tao_region` VALUES ('2572', '东川区', '2567', '530113', '3');
INSERT INTO `tao_region` VALUES ('2573', '呈贡区', '2567', '530114', '3');
INSERT INTO `tao_region` VALUES ('2574', '晋宁区', '2567', '530115', '3');
INSERT INTO `tao_region` VALUES ('2575', '富民县', '2567', '530124', '3');
INSERT INTO `tao_region` VALUES ('2576', '宜良县', '2567', '530125', '3');
INSERT INTO `tao_region` VALUES ('2577', '石林彝族自治县', '2567', '530126', '3');
INSERT INTO `tao_region` VALUES ('2578', '嵩明县', '2567', '530127', '3');
INSERT INTO `tao_region` VALUES ('2579', '禄劝彝族苗族自治县', '2567', '530128', '3');
INSERT INTO `tao_region` VALUES ('2580', '寻甸回族彝族自治县', '2567', '530129', '3');
INSERT INTO `tao_region` VALUES ('2581', '安宁市', '2567', '530181', '3');
INSERT INTO `tao_region` VALUES ('2582', '曲靖市', '2566', '530300', '2');
INSERT INTO `tao_region` VALUES ('2583', '麒麟区', '2582', '530302', '3');
INSERT INTO `tao_region` VALUES ('2584', '沾益区', '2582', '530303', '3');
INSERT INTO `tao_region` VALUES ('2585', '马龙区', '2582', '530304', '3');
INSERT INTO `tao_region` VALUES ('2586', '陆良县', '2582', '530322', '3');
INSERT INTO `tao_region` VALUES ('2587', '师宗县', '2582', '530323', '3');
INSERT INTO `tao_region` VALUES ('2588', '罗平县', '2582', '530324', '3');
INSERT INTO `tao_region` VALUES ('2589', '富源县', '2582', '530325', '3');
INSERT INTO `tao_region` VALUES ('2590', '会泽县', '2582', '530326', '3');
INSERT INTO `tao_region` VALUES ('2591', '宣威市', '2582', '530381', '3');
INSERT INTO `tao_region` VALUES ('2592', '玉溪市', '2566', '530400', '2');
INSERT INTO `tao_region` VALUES ('2593', '红塔区', '2592', '530402', '3');
INSERT INTO `tao_region` VALUES ('2594', '江川区', '2592', '530403', '3');
INSERT INTO `tao_region` VALUES ('2595', '通海县', '2592', '530423', '3');
INSERT INTO `tao_region` VALUES ('2596', '华宁县', '2592', '530424', '3');
INSERT INTO `tao_region` VALUES ('2597', '易门县', '2592', '530425', '3');
INSERT INTO `tao_region` VALUES ('2598', '峨山彝族自治县', '2592', '530426', '3');
INSERT INTO `tao_region` VALUES ('2599', '新平彝族傣族自治县', '2592', '530427', '3');
INSERT INTO `tao_region` VALUES ('2600', '元江哈尼族彝族傣族自治县', '2592', '530428', '3');
INSERT INTO `tao_region` VALUES ('2601', '澄江市', '2592', '530481', '3');
INSERT INTO `tao_region` VALUES ('2602', '保山市', '2566', '530500', '2');
INSERT INTO `tao_region` VALUES ('2603', '隆阳区', '2602', '530502', '3');
INSERT INTO `tao_region` VALUES ('2604', '施甸县', '2602', '530521', '3');
INSERT INTO `tao_region` VALUES ('2605', '龙陵县', '2602', '530523', '3');
INSERT INTO `tao_region` VALUES ('2606', '昌宁县', '2602', '530524', '3');
INSERT INTO `tao_region` VALUES ('2607', '腾冲市', '2602', '530581', '3');
INSERT INTO `tao_region` VALUES ('2608', '昭通市', '2566', '530600', '2');
INSERT INTO `tao_region` VALUES ('2609', '昭阳区', '2608', '530602', '3');
INSERT INTO `tao_region` VALUES ('2610', '鲁甸县', '2608', '530621', '3');
INSERT INTO `tao_region` VALUES ('2611', '巧家县', '2608', '530622', '3');
INSERT INTO `tao_region` VALUES ('2612', '盐津县', '2608', '530623', '3');
INSERT INTO `tao_region` VALUES ('2613', '大关县', '2608', '530624', '3');
INSERT INTO `tao_region` VALUES ('2614', '永善县', '2608', '530625', '3');
INSERT INTO `tao_region` VALUES ('2615', '绥江县', '2608', '530626', '3');
INSERT INTO `tao_region` VALUES ('2616', '镇雄县', '2608', '530627', '3');
INSERT INTO `tao_region` VALUES ('2617', '彝良县', '2608', '530628', '3');
INSERT INTO `tao_region` VALUES ('2618', '威信县', '2608', '530629', '3');
INSERT INTO `tao_region` VALUES ('2619', '水富市', '2608', '530681', '3');
INSERT INTO `tao_region` VALUES ('2620', '丽江市', '2566', '530700', '2');
INSERT INTO `tao_region` VALUES ('2621', '古城区', '2620', '530702', '3');
INSERT INTO `tao_region` VALUES ('2622', '玉龙纳西族自治县', '2620', '530721', '3');
INSERT INTO `tao_region` VALUES ('2623', '永胜县', '2620', '530722', '3');
INSERT INTO `tao_region` VALUES ('2624', '华坪县', '2620', '530723', '3');
INSERT INTO `tao_region` VALUES ('2625', '宁蒗彝族自治县', '2620', '530724', '3');
INSERT INTO `tao_region` VALUES ('2626', '普洱市', '2566', '530800', '2');
INSERT INTO `tao_region` VALUES ('2627', '思茅区', '2626', '530802', '3');
INSERT INTO `tao_region` VALUES ('2628', '宁洱哈尼族彝族自治县', '2626', '530821', '3');
INSERT INTO `tao_region` VALUES ('2629', '墨江哈尼族自治县', '2626', '530822', '3');
INSERT INTO `tao_region` VALUES ('2630', '景东彝族自治县', '2626', '530823', '3');
INSERT INTO `tao_region` VALUES ('2631', '景谷傣族彝族自治县', '2626', '530824', '3');
INSERT INTO `tao_region` VALUES ('2632', '镇沅彝族哈尼族拉祜族自治县', '2626', '530825', '3');
INSERT INTO `tao_region` VALUES ('2633', '江城哈尼族彝族自治县', '2626', '530826', '3');
INSERT INTO `tao_region` VALUES ('2634', '孟连傣族拉祜族佤族自治县', '2626', '530827', '3');
INSERT INTO `tao_region` VALUES ('2635', '澜沧拉祜族自治县', '2626', '530828', '3');
INSERT INTO `tao_region` VALUES ('2636', '西盟佤族自治县', '2626', '530829', '3');
INSERT INTO `tao_region` VALUES ('2637', '临沧市', '2566', '530900', '2');
INSERT INTO `tao_region` VALUES ('2638', '临翔区', '2637', '530902', '3');
INSERT INTO `tao_region` VALUES ('2639', '凤庆县', '2637', '530921', '3');
INSERT INTO `tao_region` VALUES ('2640', '云县', '2637', '530922', '3');
INSERT INTO `tao_region` VALUES ('2641', '永德县', '2637', '530923', '3');
INSERT INTO `tao_region` VALUES ('2642', '镇康县', '2637', '530924', '3');
INSERT INTO `tao_region` VALUES ('2643', '双江拉祜族佤族布朗族傣族自治县', '2637', '530925', '3');
INSERT INTO `tao_region` VALUES ('2644', '耿马傣族佤族自治县', '2637', '530926', '3');
INSERT INTO `tao_region` VALUES ('2645', '沧源佤族自治县', '2637', '530927', '3');
INSERT INTO `tao_region` VALUES ('2646', '楚雄彝族自治州', '2566', '532300', '2');
INSERT INTO `tao_region` VALUES ('2647', '楚雄市', '2646', '532301', '3');
INSERT INTO `tao_region` VALUES ('2648', '双柏县', '2646', '532322', '3');
INSERT INTO `tao_region` VALUES ('2649', '牟定县', '2646', '532323', '3');
INSERT INTO `tao_region` VALUES ('2650', '南华县', '2646', '532324', '3');
INSERT INTO `tao_region` VALUES ('2651', '姚安县', '2646', '532325', '3');
INSERT INTO `tao_region` VALUES ('2652', '大姚县', '2646', '532326', '3');
INSERT INTO `tao_region` VALUES ('2653', '永仁县', '2646', '532327', '3');
INSERT INTO `tao_region` VALUES ('2654', '元谋县', '2646', '532328', '3');
INSERT INTO `tao_region` VALUES ('2655', '武定县', '2646', '532329', '3');
INSERT INTO `tao_region` VALUES ('2657', '红河哈尼族彝族自治州', '2566', '532500', '2');
INSERT INTO `tao_region` VALUES ('2658', '个旧市', '2657', '532501', '3');
INSERT INTO `tao_region` VALUES ('2659', '开远市', '2657', '532502', '3');
INSERT INTO `tao_region` VALUES ('2660', '蒙自市', '2657', '532503', '3');
INSERT INTO `tao_region` VALUES ('2661', '弥勒市', '2657', '532504', '3');
INSERT INTO `tao_region` VALUES ('2662', '屏边苗族自治县', '2657', '532523', '3');
INSERT INTO `tao_region` VALUES ('2663', '建水县', '2657', '532524', '3');
INSERT INTO `tao_region` VALUES ('2664', '石屏县', '2657', '532525', '3');
INSERT INTO `tao_region` VALUES ('2665', '泸西县', '2657', '532527', '3');
INSERT INTO `tao_region` VALUES ('2666', '元阳县', '2657', '532528', '3');
INSERT INTO `tao_region` VALUES ('2667', '红河县', '2657', '532529', '3');
INSERT INTO `tao_region` VALUES ('2668', '金平苗族瑶族傣族自治县', '2657', '532530', '3');
INSERT INTO `tao_region` VALUES ('2669', '绿春县', '2657', '532531', '3');
INSERT INTO `tao_region` VALUES ('2670', '河口瑶族自治县', '2657', '532532', '3');
INSERT INTO `tao_region` VALUES ('2671', '文山壮族苗族自治州', '2566', '532600', '2');
INSERT INTO `tao_region` VALUES ('2672', '文山市', '2671', '532601', '3');
INSERT INTO `tao_region` VALUES ('2673', '砚山县', '2671', '532622', '3');
INSERT INTO `tao_region` VALUES ('2674', '西畴县', '2671', '532623', '3');
INSERT INTO `tao_region` VALUES ('2675', '麻栗坡县', '2671', '532624', '3');
INSERT INTO `tao_region` VALUES ('2676', '马关县', '2671', '532625', '3');
INSERT INTO `tao_region` VALUES ('2677', '丘北县', '2671', '532626', '3');
INSERT INTO `tao_region` VALUES ('2678', '广南县', '2671', '532627', '3');
INSERT INTO `tao_region` VALUES ('2679', '富宁县', '2671', '532628', '3');
INSERT INTO `tao_region` VALUES ('2680', '西双版纳傣族自治州', '2566', '532800', '2');
INSERT INTO `tao_region` VALUES ('2681', '景洪市', '2680', '532801', '3');
INSERT INTO `tao_region` VALUES ('2682', '勐海县', '2680', '532822', '3');
INSERT INTO `tao_region` VALUES ('2683', '勐腊县', '2680', '532823', '3');
INSERT INTO `tao_region` VALUES ('2684', '大理白族自治州', '2566', '532900', '2');
INSERT INTO `tao_region` VALUES ('2685', '大理市', '2684', '532901', '3');
INSERT INTO `tao_region` VALUES ('2686', '漾濞彝族自治县', '2684', '532922', '3');
INSERT INTO `tao_region` VALUES ('2687', '祥云县', '2684', '532923', '3');
INSERT INTO `tao_region` VALUES ('2688', '宾川县', '2684', '532924', '3');
INSERT INTO `tao_region` VALUES ('2689', '弥渡县', '2684', '532925', '3');
INSERT INTO `tao_region` VALUES ('2690', '南涧彝族自治县', '2684', '532926', '3');
INSERT INTO `tao_region` VALUES ('2691', '巍山彝族回族自治县', '2684', '532927', '3');
INSERT INTO `tao_region` VALUES ('2692', '永平县', '2684', '532928', '3');
INSERT INTO `tao_region` VALUES ('2693', '云龙县', '2684', '532929', '3');
INSERT INTO `tao_region` VALUES ('2694', '洱源县', '2684', '532930', '3');
INSERT INTO `tao_region` VALUES ('2695', '剑川县', '2684', '532931', '3');
INSERT INTO `tao_region` VALUES ('2696', '鹤庆县', '2684', '532932', '3');
INSERT INTO `tao_region` VALUES ('2697', '德宏傣族景颇族自治州', '2566', '533100', '2');
INSERT INTO `tao_region` VALUES ('2698', '瑞丽市', '2697', '533102', '3');
INSERT INTO `tao_region` VALUES ('2699', '芒市', '2697', '533103', '3');
INSERT INTO `tao_region` VALUES ('2700', '梁河县', '2697', '533122', '3');
INSERT INTO `tao_region` VALUES ('2701', '盈江县', '2697', '533123', '3');
INSERT INTO `tao_region` VALUES ('2702', '陇川县', '2697', '533124', '3');
INSERT INTO `tao_region` VALUES ('2703', '怒江傈僳族自治州', '2566', '533300', '2');
INSERT INTO `tao_region` VALUES ('2704', '泸水市', '2703', '533301', '3');
INSERT INTO `tao_region` VALUES ('2705', '福贡县', '2703', '533323', '3');
INSERT INTO `tao_region` VALUES ('2706', '贡山独龙族怒族自治县', '2703', '533324', '3');
INSERT INTO `tao_region` VALUES ('2707', '兰坪白族普米族自治县', '2703', '533325', '3');
INSERT INTO `tao_region` VALUES ('2708', '迪庆藏族自治州', '2566', '533400', '2');
INSERT INTO `tao_region` VALUES ('2709', '香格里拉市', '2708', '533401', '3');
INSERT INTO `tao_region` VALUES ('2710', '德钦县', '2708', '533422', '3');
INSERT INTO `tao_region` VALUES ('2711', '维西傈僳族自治县', '2708', '533423', '3');
INSERT INTO `tao_region` VALUES ('2712', '西藏自治区', '0', '540000', '1');
INSERT INTO `tao_region` VALUES ('2713', '拉萨市', '2712', '540100', '2');
INSERT INTO `tao_region` VALUES ('2714', '城关区', '2713', '540102', '3');
INSERT INTO `tao_region` VALUES ('2715', '堆龙德庆区', '2713', '540103', '3');
INSERT INTO `tao_region` VALUES ('2716', '达孜区', '2713', '540104', '3');
INSERT INTO `tao_region` VALUES ('2717', '林周县', '2713', '540121', '3');
INSERT INTO `tao_region` VALUES ('2718', '当雄县', '2713', '540122', '3');
INSERT INTO `tao_region` VALUES ('2719', '尼木县', '2713', '540123', '3');
INSERT INTO `tao_region` VALUES ('2720', '曲水县', '2713', '540124', '3');
INSERT INTO `tao_region` VALUES ('2721', '墨竹工卡县', '2713', '540127', '3');
INSERT INTO `tao_region` VALUES ('2722', '日喀则市', '2712', '540200', '2');
INSERT INTO `tao_region` VALUES ('2723', '桑珠孜区', '2722', '540202', '3');
INSERT INTO `tao_region` VALUES ('2724', '南木林县', '2722', '540221', '3');
INSERT INTO `tao_region` VALUES ('2725', '江孜县', '2722', '540222', '3');
INSERT INTO `tao_region` VALUES ('2726', '定日县', '2722', '540223', '3');
INSERT INTO `tao_region` VALUES ('2727', '萨迦县', '2722', '540224', '3');
INSERT INTO `tao_region` VALUES ('2728', '拉孜县', '2722', '540225', '3');
INSERT INTO `tao_region` VALUES ('2729', '昂仁县', '2722', '540226', '3');
INSERT INTO `tao_region` VALUES ('2730', '谢通门县', '2722', '540227', '3');
INSERT INTO `tao_region` VALUES ('2731', '白朗县', '2722', '540228', '3');
INSERT INTO `tao_region` VALUES ('2732', '仁布县', '2722', '540229', '3');
INSERT INTO `tao_region` VALUES ('2733', '康马县', '2722', '540230', '3');
INSERT INTO `tao_region` VALUES ('2734', '定结县', '2722', '540231', '3');
INSERT INTO `tao_region` VALUES ('2735', '仲巴县', '2722', '540232', '3');
INSERT INTO `tao_region` VALUES ('2736', '亚东县', '2722', '540233', '3');
INSERT INTO `tao_region` VALUES ('2737', '吉隆县', '2722', '540234', '3');
INSERT INTO `tao_region` VALUES ('2738', '聂拉木县', '2722', '540235', '3');
INSERT INTO `tao_region` VALUES ('2739', '萨嘎县', '2722', '540236', '3');
INSERT INTO `tao_region` VALUES ('2740', '岗巴县', '2722', '540237', '3');
INSERT INTO `tao_region` VALUES ('2741', '昌都市', '2712', '540300', '2');
INSERT INTO `tao_region` VALUES ('2742', '卡若区', '2741', '540302', '3');
INSERT INTO `tao_region` VALUES ('2743', '江达县', '2741', '540321', '3');
INSERT INTO `tao_region` VALUES ('2744', '贡觉县', '2741', '540322', '3');
INSERT INTO `tao_region` VALUES ('2745', '类乌齐县', '2741', '540323', '3');
INSERT INTO `tao_region` VALUES ('2746', '丁青县', '2741', '540324', '3');
INSERT INTO `tao_region` VALUES ('2747', '察雅县', '2741', '540325', '3');
INSERT INTO `tao_region` VALUES ('2748', '八宿县', '2741', '540326', '3');
INSERT INTO `tao_region` VALUES ('2749', '左贡县', '2741', '540327', '3');
INSERT INTO `tao_region` VALUES ('2750', '芒康县', '2741', '540328', '3');
INSERT INTO `tao_region` VALUES ('2751', '洛隆县', '2741', '540329', '3');
INSERT INTO `tao_region` VALUES ('2752', '边坝县', '2741', '540330', '3');
INSERT INTO `tao_region` VALUES ('2753', '林芝市', '2712', '540400', '2');
INSERT INTO `tao_region` VALUES ('2754', '巴宜区', '2753', '540402', '3');
INSERT INTO `tao_region` VALUES ('2755', '工布江达县', '2753', '540421', '3');
INSERT INTO `tao_region` VALUES ('2756', '米林县', '2753', '540422', '3');
INSERT INTO `tao_region` VALUES ('2757', '墨脱县', '2753', '540423', '3');
INSERT INTO `tao_region` VALUES ('2758', '波密县', '2753', '540424', '3');
INSERT INTO `tao_region` VALUES ('2759', '察隅县', '2753', '540425', '3');
INSERT INTO `tao_region` VALUES ('2760', '朗县', '2753', '540426', '3');
INSERT INTO `tao_region` VALUES ('2761', '山南市', '2712', '540500', '2');
INSERT INTO `tao_region` VALUES ('2762', '乃东区', '2761', '540502', '3');
INSERT INTO `tao_region` VALUES ('2763', '扎囊县', '2761', '540521', '3');
INSERT INTO `tao_region` VALUES ('2764', '贡嘎县', '2761', '540522', '3');
INSERT INTO `tao_region` VALUES ('2765', '桑日县', '2761', '540523', '3');
INSERT INTO `tao_region` VALUES ('2766', '琼结县', '2761', '540524', '3');
INSERT INTO `tao_region` VALUES ('2767', '曲松县', '2761', '540525', '3');
INSERT INTO `tao_region` VALUES ('2768', '措美县', '2761', '540526', '3');
INSERT INTO `tao_region` VALUES ('2769', '洛扎县', '2761', '540527', '3');
INSERT INTO `tao_region` VALUES ('2770', '加查县', '2761', '540528', '3');
INSERT INTO `tao_region` VALUES ('2771', '隆子县', '2761', '540529', '3');
INSERT INTO `tao_region` VALUES ('2772', '错那县', '2761', '540530', '3');
INSERT INTO `tao_region` VALUES ('2773', '浪卡子县', '2761', '540531', '3');
INSERT INTO `tao_region` VALUES ('2774', '那曲市', '2712', '540600', '2');
INSERT INTO `tao_region` VALUES ('2775', '色尼区', '2774', '540602', '3');
INSERT INTO `tao_region` VALUES ('2776', '嘉黎县', '2774', '540621', '3');
INSERT INTO `tao_region` VALUES ('2777', '比如县', '2774', '540622', '3');
INSERT INTO `tao_region` VALUES ('2778', '聂荣县', '2774', '540623', '3');
INSERT INTO `tao_region` VALUES ('2779', '安多县', '2774', '540624', '3');
INSERT INTO `tao_region` VALUES ('2780', '申扎县', '2774', '540625', '3');
INSERT INTO `tao_region` VALUES ('2781', '索县', '2774', '540626', '3');
INSERT INTO `tao_region` VALUES ('2782', '班戈县', '2774', '540627', '3');
INSERT INTO `tao_region` VALUES ('2783', '巴青县', '2774', '540628', '3');
INSERT INTO `tao_region` VALUES ('2784', '尼玛县', '2774', '540629', '3');
INSERT INTO `tao_region` VALUES ('2785', '双湖县', '2774', '540630', '3');
INSERT INTO `tao_region` VALUES ('2786', '阿里地区', '2712', '542500', '2');
INSERT INTO `tao_region` VALUES ('2787', '普兰县', '2786', '542521', '3');
INSERT INTO `tao_region` VALUES ('2788', '札达县', '2786', '542522', '3');
INSERT INTO `tao_region` VALUES ('2789', '噶尔县', '2786', '542523', '3');
INSERT INTO `tao_region` VALUES ('2790', '日土县', '2786', '542524', '3');
INSERT INTO `tao_region` VALUES ('2791', '革吉县', '2786', '542525', '3');
INSERT INTO `tao_region` VALUES ('2792', '改则县', '2786', '542526', '3');
INSERT INTO `tao_region` VALUES ('2793', '措勤县', '2786', '542527', '3');
INSERT INTO `tao_region` VALUES ('2794', '陕西省', '0', '610000', '1');
INSERT INTO `tao_region` VALUES ('2795', '西安市', '2794', '610100', '2');
INSERT INTO `tao_region` VALUES ('2796', '新城区', '2795', '610102', '3');
INSERT INTO `tao_region` VALUES ('2797', '碑林区', '2795', '610103', '3');
INSERT INTO `tao_region` VALUES ('2798', '莲湖区', '2795', '610104', '3');
INSERT INTO `tao_region` VALUES ('2799', '灞桥区', '2795', '610111', '3');
INSERT INTO `tao_region` VALUES ('2800', '未央区', '2795', '610112', '3');
INSERT INTO `tao_region` VALUES ('2801', '雁塔区', '2795', '610113', '3');
INSERT INTO `tao_region` VALUES ('2802', '阎良区', '2795', '610114', '3');
INSERT INTO `tao_region` VALUES ('2803', '临潼区', '2795', '610115', '3');
INSERT INTO `tao_region` VALUES ('2804', '长安区', '2795', '610116', '3');
INSERT INTO `tao_region` VALUES ('2805', '高陵区', '2795', '610117', '3');
INSERT INTO `tao_region` VALUES ('2806', '鄠邑区', '2795', '610118', '3');
INSERT INTO `tao_region` VALUES ('2807', '蓝田县', '2795', '610122', '3');
INSERT INTO `tao_region` VALUES ('2808', '周至县', '2795', '610124', '3');
INSERT INTO `tao_region` VALUES ('2809', '铜川市', '2794', '610200', '2');
INSERT INTO `tao_region` VALUES ('2810', '王益区', '2809', '610202', '3');
INSERT INTO `tao_region` VALUES ('2811', '印台区', '2809', '610203', '3');
INSERT INTO `tao_region` VALUES ('2812', '耀州区', '2809', '610204', '3');
INSERT INTO `tao_region` VALUES ('2813', '宜君县', '2809', '610222', '3');
INSERT INTO `tao_region` VALUES ('2814', '宝鸡市', '2794', '610300', '2');
INSERT INTO `tao_region` VALUES ('2815', '渭滨区', '2814', '610302', '3');
INSERT INTO `tao_region` VALUES ('2816', '金台区', '2814', '610303', '3');
INSERT INTO `tao_region` VALUES ('2817', '陈仓区', '2814', '610304', '3');
INSERT INTO `tao_region` VALUES ('2819', '岐山县', '2814', '610323', '3');
INSERT INTO `tao_region` VALUES ('2820', '扶风县', '2814', '610324', '3');
INSERT INTO `tao_region` VALUES ('2821', '眉县', '2814', '610326', '3');
INSERT INTO `tao_region` VALUES ('2822', '陇县', '2814', '610327', '3');
INSERT INTO `tao_region` VALUES ('2823', '千阳县', '2814', '610328', '3');
INSERT INTO `tao_region` VALUES ('2824', '麟游县', '2814', '610329', '3');
INSERT INTO `tao_region` VALUES ('2825', '凤县', '2814', '610330', '3');
INSERT INTO `tao_region` VALUES ('2826', '太白县', '2814', '610331', '3');
INSERT INTO `tao_region` VALUES ('2827', '咸阳市', '2794', '610400', '2');
INSERT INTO `tao_region` VALUES ('2828', '秦都区', '2827', '610402', '3');
INSERT INTO `tao_region` VALUES ('2829', '杨陵区', '2827', '610403', '3');
INSERT INTO `tao_region` VALUES ('2830', '渭城区', '2827', '610404', '3');
INSERT INTO `tao_region` VALUES ('2831', '三原县', '2827', '610422', '3');
INSERT INTO `tao_region` VALUES ('2832', '泾阳县', '2827', '610423', '3');
INSERT INTO `tao_region` VALUES ('2833', '乾县', '2827', '610424', '3');
INSERT INTO `tao_region` VALUES ('2834', '礼泉县', '2827', '610425', '3');
INSERT INTO `tao_region` VALUES ('2835', '永寿县', '2827', '610426', '3');
INSERT INTO `tao_region` VALUES ('2836', '长武县', '2827', '610428', '3');
INSERT INTO `tao_region` VALUES ('2837', '旬邑县', '2827', '610429', '3');
INSERT INTO `tao_region` VALUES ('2838', '淳化县', '2827', '610430', '3');
INSERT INTO `tao_region` VALUES ('2839', '武功县', '2827', '610431', '3');
INSERT INTO `tao_region` VALUES ('2840', '兴平市', '2827', '610481', '3');
INSERT INTO `tao_region` VALUES ('2841', '彬州市', '2827', '610482', '3');
INSERT INTO `tao_region` VALUES ('2842', '渭南市', '2794', '610500', '2');
INSERT INTO `tao_region` VALUES ('2843', '临渭区', '2842', '610502', '3');
INSERT INTO `tao_region` VALUES ('2844', '华州区', '2842', '610503', '3');
INSERT INTO `tao_region` VALUES ('2845', '潼关县', '2842', '610522', '3');
INSERT INTO `tao_region` VALUES ('2846', '大荔县', '2842', '610523', '3');
INSERT INTO `tao_region` VALUES ('2847', '合阳县', '2842', '610524', '3');
INSERT INTO `tao_region` VALUES ('2848', '澄城县', '2842', '610525', '3');
INSERT INTO `tao_region` VALUES ('2849', '蒲城县', '2842', '610526', '3');
INSERT INTO `tao_region` VALUES ('2850', '白水县', '2842', '610527', '3');
INSERT INTO `tao_region` VALUES ('2851', '富平县', '2842', '610528', '3');
INSERT INTO `tao_region` VALUES ('2852', '韩城市', '2842', '610581', '3');
INSERT INTO `tao_region` VALUES ('2853', '华阴市', '2842', '610582', '3');
INSERT INTO `tao_region` VALUES ('2854', '延安市', '2794', '610600', '2');
INSERT INTO `tao_region` VALUES ('2855', '宝塔区', '2854', '610602', '3');
INSERT INTO `tao_region` VALUES ('2856', '安塞区', '2854', '610603', '3');
INSERT INTO `tao_region` VALUES ('2857', '延长县', '2854', '610621', '3');
INSERT INTO `tao_region` VALUES ('2858', '延川县', '2854', '610622', '3');
INSERT INTO `tao_region` VALUES ('2859', '志丹县', '2854', '610625', '3');
INSERT INTO `tao_region` VALUES ('2860', '吴起县', '2854', '610626', '3');
INSERT INTO `tao_region` VALUES ('2861', '甘泉县', '2854', '610627', '3');
INSERT INTO `tao_region` VALUES ('2862', '富县', '2854', '610628', '3');
INSERT INTO `tao_region` VALUES ('2863', '洛川县', '2854', '610629', '3');
INSERT INTO `tao_region` VALUES ('2864', '宜川县', '2854', '610630', '3');
INSERT INTO `tao_region` VALUES ('2865', '黄龙县', '2854', '610631', '3');
INSERT INTO `tao_region` VALUES ('2866', '黄陵县', '2854', '610632', '3');
INSERT INTO `tao_region` VALUES ('2867', '子长市', '2854', '610681', '3');
INSERT INTO `tao_region` VALUES ('2868', '汉中市', '2794', '610700', '2');
INSERT INTO `tao_region` VALUES ('2869', '汉台区', '2868', '610702', '3');
INSERT INTO `tao_region` VALUES ('2870', '南郑区', '2868', '610703', '3');
INSERT INTO `tao_region` VALUES ('2871', '城固县', '2868', '610722', '3');
INSERT INTO `tao_region` VALUES ('2872', '洋县', '2868', '610723', '3');
INSERT INTO `tao_region` VALUES ('2873', '西乡县', '2868', '610724', '3');
INSERT INTO `tao_region` VALUES ('2874', '勉县', '2868', '610725', '3');
INSERT INTO `tao_region` VALUES ('2875', '宁强县', '2868', '610726', '3');
INSERT INTO `tao_region` VALUES ('2876', '略阳县', '2868', '610727', '3');
INSERT INTO `tao_region` VALUES ('2877', '镇巴县', '2868', '610728', '3');
INSERT INTO `tao_region` VALUES ('2878', '留坝县', '2868', '610729', '3');
INSERT INTO `tao_region` VALUES ('2879', '佛坪县', '2868', '610730', '3');
INSERT INTO `tao_region` VALUES ('2880', '榆林市', '2794', '610800', '2');
INSERT INTO `tao_region` VALUES ('2881', '榆阳区', '2880', '610802', '3');
INSERT INTO `tao_region` VALUES ('2882', '横山区', '2880', '610803', '3');
INSERT INTO `tao_region` VALUES ('2883', '府谷县', '2880', '610822', '3');
INSERT INTO `tao_region` VALUES ('2884', '靖边县', '2880', '610824', '3');
INSERT INTO `tao_region` VALUES ('2885', '定边县', '2880', '610825', '3');
INSERT INTO `tao_region` VALUES ('2886', '绥德县', '2880', '610826', '3');
INSERT INTO `tao_region` VALUES ('2887', '米脂县', '2880', '610827', '3');
INSERT INTO `tao_region` VALUES ('2888', '佳县', '2880', '610828', '3');
INSERT INTO `tao_region` VALUES ('2889', '吴堡县', '2880', '610829', '3');
INSERT INTO `tao_region` VALUES ('2890', '清涧县', '2880', '610830', '3');
INSERT INTO `tao_region` VALUES ('2891', '子洲县', '2880', '610831', '3');
INSERT INTO `tao_region` VALUES ('2892', '神木市', '2880', '610881', '3');
INSERT INTO `tao_region` VALUES ('2893', '安康市', '2794', '610900', '2');
INSERT INTO `tao_region` VALUES ('2894', '汉滨区', '2893', '610902', '3');
INSERT INTO `tao_region` VALUES ('2895', '汉阴县', '2893', '610921', '3');
INSERT INTO `tao_region` VALUES ('2896', '石泉县', '2893', '610922', '3');
INSERT INTO `tao_region` VALUES ('2897', '宁陕县', '2893', '610923', '3');
INSERT INTO `tao_region` VALUES ('2898', '紫阳县', '2893', '610924', '3');
INSERT INTO `tao_region` VALUES ('2899', '岚皋县', '2893', '610925', '3');
INSERT INTO `tao_region` VALUES ('2900', '平利县', '2893', '610926', '3');
INSERT INTO `tao_region` VALUES ('2901', '镇坪县', '2893', '610927', '3');
INSERT INTO `tao_region` VALUES ('2903', '白河县', '2893', '610929', '3');
INSERT INTO `tao_region` VALUES ('2904', '商洛市', '2794', '611000', '2');
INSERT INTO `tao_region` VALUES ('2905', '商州区', '2904', '611002', '3');
INSERT INTO `tao_region` VALUES ('2906', '洛南县', '2904', '611021', '3');
INSERT INTO `tao_region` VALUES ('2907', '丹凤县', '2904', '611022', '3');
INSERT INTO `tao_region` VALUES ('2908', '商南县', '2904', '611023', '3');
INSERT INTO `tao_region` VALUES ('2909', '山阳县', '2904', '611024', '3');
INSERT INTO `tao_region` VALUES ('2910', '镇安县', '2904', '611025', '3');
INSERT INTO `tao_region` VALUES ('2911', '柞水县', '2904', '611026', '3');
INSERT INTO `tao_region` VALUES ('2912', '甘肃省', '0', '620000', '1');
INSERT INTO `tao_region` VALUES ('2913', '兰州市', '2912', '620100', '2');
INSERT INTO `tao_region` VALUES ('2914', '城关区', '2913', '620102', '3');
INSERT INTO `tao_region` VALUES ('2915', '七里河区', '2913', '620103', '3');
INSERT INTO `tao_region` VALUES ('2916', '西固区', '2913', '620104', '3');
INSERT INTO `tao_region` VALUES ('2917', '安宁区', '2913', '620105', '3');
INSERT INTO `tao_region` VALUES ('2918', '红古区', '2913', '620111', '3');
INSERT INTO `tao_region` VALUES ('2919', '永登县', '2913', '620121', '3');
INSERT INTO `tao_region` VALUES ('2920', '皋兰县', '2913', '620122', '3');
INSERT INTO `tao_region` VALUES ('2921', '榆中县', '2913', '620123', '3');
INSERT INTO `tao_region` VALUES ('2922', '嘉峪关市', '2912', '620200', '2');
INSERT INTO `tao_region` VALUES ('2923', '金昌市', '2912', '620300', '2');
INSERT INTO `tao_region` VALUES ('2924', '金川区', '2923', '620302', '3');
INSERT INTO `tao_region` VALUES ('2925', '永昌县', '2923', '620321', '3');
INSERT INTO `tao_region` VALUES ('2926', '白银市', '2912', '620400', '2');
INSERT INTO `tao_region` VALUES ('2927', '白银区', '2926', '620402', '3');
INSERT INTO `tao_region` VALUES ('2928', '平川区', '2926', '620403', '3');
INSERT INTO `tao_region` VALUES ('2929', '靖远县', '2926', '620421', '3');
INSERT INTO `tao_region` VALUES ('2930', '会宁县', '2926', '620422', '3');
INSERT INTO `tao_region` VALUES ('2931', '景泰县', '2926', '620423', '3');
INSERT INTO `tao_region` VALUES ('2932', '天水市', '2912', '620500', '2');
INSERT INTO `tao_region` VALUES ('2933', '秦州区', '2932', '620502', '3');
INSERT INTO `tao_region` VALUES ('2934', '麦积区', '2932', '620503', '3');
INSERT INTO `tao_region` VALUES ('2935', '清水县', '2932', '620521', '3');
INSERT INTO `tao_region` VALUES ('2936', '秦安县', '2932', '620522', '3');
INSERT INTO `tao_region` VALUES ('2937', '甘谷县', '2932', '620523', '3');
INSERT INTO `tao_region` VALUES ('2938', '武山县', '2932', '620524', '3');
INSERT INTO `tao_region` VALUES ('2939', '张家川回族自治县', '2932', '620525', '3');
INSERT INTO `tao_region` VALUES ('2940', '武威市', '2912', '620600', '2');
INSERT INTO `tao_region` VALUES ('2941', '凉州区', '2940', '620602', '3');
INSERT INTO `tao_region` VALUES ('2942', '民勤县', '2940', '620621', '3');
INSERT INTO `tao_region` VALUES ('2943', '古浪县', '2940', '620622', '3');
INSERT INTO `tao_region` VALUES ('2944', '天祝藏族自治县', '2940', '620623', '3');
INSERT INTO `tao_region` VALUES ('2945', '张掖市', '2912', '620700', '2');
INSERT INTO `tao_region` VALUES ('2946', '甘州区', '2945', '620702', '3');
INSERT INTO `tao_region` VALUES ('2947', '肃南裕固族自治县', '2945', '620721', '3');
INSERT INTO `tao_region` VALUES ('2948', '民乐县', '2945', '620722', '3');
INSERT INTO `tao_region` VALUES ('2949', '临泽县', '2945', '620723', '3');
INSERT INTO `tao_region` VALUES ('2950', '高台县', '2945', '620724', '3');
INSERT INTO `tao_region` VALUES ('2951', '山丹县', '2945', '620725', '3');
INSERT INTO `tao_region` VALUES ('2952', '平凉市', '2912', '620800', '2');
INSERT INTO `tao_region` VALUES ('2953', '崆峒区', '2952', '620802', '3');
INSERT INTO `tao_region` VALUES ('2954', '泾川县', '2952', '620821', '3');
INSERT INTO `tao_region` VALUES ('2955', '灵台县', '2952', '620822', '3');
INSERT INTO `tao_region` VALUES ('2956', '崇信县', '2952', '620823', '3');
INSERT INTO `tao_region` VALUES ('2957', '庄浪县', '2952', '620825', '3');
INSERT INTO `tao_region` VALUES ('2958', '静宁县', '2952', '620826', '3');
INSERT INTO `tao_region` VALUES ('2959', '华亭市', '2952', '620881', '3');
INSERT INTO `tao_region` VALUES ('2960', '酒泉市', '2912', '620900', '2');
INSERT INTO `tao_region` VALUES ('2961', '肃州区', '2960', '620902', '3');
INSERT INTO `tao_region` VALUES ('2962', '金塔县', '2960', '620921', '3');
INSERT INTO `tao_region` VALUES ('2963', '瓜州县', '2960', '620922', '3');
INSERT INTO `tao_region` VALUES ('2964', '肃北蒙古族自治县', '2960', '620923', '3');
INSERT INTO `tao_region` VALUES ('2965', '阿克塞哈萨克族自治县', '2960', '620924', '3');
INSERT INTO `tao_region` VALUES ('2966', '玉门市', '2960', '620981', '3');
INSERT INTO `tao_region` VALUES ('2967', '敦煌市', '2960', '620982', '3');
INSERT INTO `tao_region` VALUES ('2968', '庆阳市', '2912', '621000', '2');
INSERT INTO `tao_region` VALUES ('2969', '西峰区', '2968', '621002', '3');
INSERT INTO `tao_region` VALUES ('2970', '庆城县', '2968', '621021', '3');
INSERT INTO `tao_region` VALUES ('2971', '环县', '2968', '621022', '3');
INSERT INTO `tao_region` VALUES ('2972', '华池县', '2968', '621023', '3');
INSERT INTO `tao_region` VALUES ('2973', '合水县', '2968', '621024', '3');
INSERT INTO `tao_region` VALUES ('2974', '正宁县', '2968', '621025', '3');
INSERT INTO `tao_region` VALUES ('2975', '宁县', '2968', '621026', '3');
INSERT INTO `tao_region` VALUES ('2976', '镇原县', '2968', '621027', '3');
INSERT INTO `tao_region` VALUES ('2977', '定西市', '2912', '621100', '2');
INSERT INTO `tao_region` VALUES ('2978', '安定区', '2977', '621102', '3');
INSERT INTO `tao_region` VALUES ('2979', '通渭县', '2977', '621121', '3');
INSERT INTO `tao_region` VALUES ('2980', '陇西县', '2977', '621122', '3');
INSERT INTO `tao_region` VALUES ('2981', '渭源县', '2977', '621123', '3');
INSERT INTO `tao_region` VALUES ('2982', '临洮县', '2977', '621124', '3');
INSERT INTO `tao_region` VALUES ('2983', '漳县', '2977', '621125', '3');
INSERT INTO `tao_region` VALUES ('2984', '岷县', '2977', '621126', '3');
INSERT INTO `tao_region` VALUES ('2985', '陇南市', '2912', '621200', '2');
INSERT INTO `tao_region` VALUES ('2986', '武都区', '2985', '621202', '3');
INSERT INTO `tao_region` VALUES ('2987', '成县', '2985', '621221', '3');
INSERT INTO `tao_region` VALUES ('2988', '文县', '2985', '621222', '3');
INSERT INTO `tao_region` VALUES ('2989', '宕昌县', '2985', '621223', '3');
INSERT INTO `tao_region` VALUES ('2990', '康县', '2985', '621224', '3');
INSERT INTO `tao_region` VALUES ('2991', '西和县', '2985', '621225', '3');
INSERT INTO `tao_region` VALUES ('2992', '礼县', '2985', '621226', '3');
INSERT INTO `tao_region` VALUES ('2993', '徽县', '2985', '621227', '3');
INSERT INTO `tao_region` VALUES ('2994', '两当县', '2985', '621228', '3');
INSERT INTO `tao_region` VALUES ('2995', '临夏回族自治州', '2912', '622900', '2');
INSERT INTO `tao_region` VALUES ('2996', '临夏市', '2995', '622901', '3');
INSERT INTO `tao_region` VALUES ('2997', '临夏县', '2995', '622921', '3');
INSERT INTO `tao_region` VALUES ('2998', '康乐县', '2995', '622922', '3');
INSERT INTO `tao_region` VALUES ('2999', '永靖县', '2995', '622923', '3');
INSERT INTO `tao_region` VALUES ('3000', '广河县', '2995', '622924', '3');
INSERT INTO `tao_region` VALUES ('3001', '和政县', '2995', '622925', '3');
INSERT INTO `tao_region` VALUES ('3002', '东乡族自治县', '2995', '622926', '3');
INSERT INTO `tao_region` VALUES ('3003', '积石山保安族东乡族撒拉族自治县', '2995', '622927', '3');
INSERT INTO `tao_region` VALUES ('3004', '甘南藏族自治州', '2912', '623000', '2');
INSERT INTO `tao_region` VALUES ('3005', '合作市', '3004', '623001', '3');
INSERT INTO `tao_region` VALUES ('3006', '临潭县', '3004', '623021', '3');
INSERT INTO `tao_region` VALUES ('3007', '卓尼县', '3004', '623022', '3');
INSERT INTO `tao_region` VALUES ('3008', '舟曲县', '3004', '623023', '3');
INSERT INTO `tao_region` VALUES ('3009', '迭部县', '3004', '623024', '3');
INSERT INTO `tao_region` VALUES ('3010', '玛曲县', '3004', '623025', '3');
INSERT INTO `tao_region` VALUES ('3011', '碌曲县', '3004', '623026', '3');
INSERT INTO `tao_region` VALUES ('3012', '夏河县', '3004', '623027', '3');
INSERT INTO `tao_region` VALUES ('3013', '青海省', '0', '630000', '1');
INSERT INTO `tao_region` VALUES ('3014', '西宁市', '3013', '630100', '2');
INSERT INTO `tao_region` VALUES ('3015', '城东区', '3014', '630102', '3');
INSERT INTO `tao_region` VALUES ('3016', '城中区', '3014', '630103', '3');
INSERT INTO `tao_region` VALUES ('3017', '城西区', '3014', '630104', '3');
INSERT INTO `tao_region` VALUES ('3018', '城北区', '3014', '630105', '3');
INSERT INTO `tao_region` VALUES ('3019', '湟中区', '3014', '630106', '3');
INSERT INTO `tao_region` VALUES ('3020', '大通回族土族自治县', '3014', '630121', '3');
INSERT INTO `tao_region` VALUES ('3021', '湟源县', '3014', '630123', '3');
INSERT INTO `tao_region` VALUES ('3022', '海东市', '3013', '630200', '2');
INSERT INTO `tao_region` VALUES ('3023', '乐都区', '3022', '630202', '3');
INSERT INTO `tao_region` VALUES ('3024', '平安区', '3022', '630203', '3');
INSERT INTO `tao_region` VALUES ('3025', '民和回族土族自治县', '3022', '630222', '3');
INSERT INTO `tao_region` VALUES ('3026', '互助土族自治县', '3022', '630223', '3');
INSERT INTO `tao_region` VALUES ('3027', '化隆回族自治县', '3022', '630224', '3');
INSERT INTO `tao_region` VALUES ('3028', '循化撒拉族自治县', '3022', '630225', '3');
INSERT INTO `tao_region` VALUES ('3029', '海北藏族自治州', '3013', '632200', '2');
INSERT INTO `tao_region` VALUES ('3030', '门源回族自治县', '3029', '632221', '3');
INSERT INTO `tao_region` VALUES ('3031', '祁连县', '3029', '632222', '3');
INSERT INTO `tao_region` VALUES ('3032', '海晏县', '3029', '632223', '3');
INSERT INTO `tao_region` VALUES ('3033', '刚察县', '3029', '632224', '3');
INSERT INTO `tao_region` VALUES ('3034', '黄南藏族自治州', '3013', '632300', '2');
INSERT INTO `tao_region` VALUES ('3036', '尖扎县', '3034', '632322', '3');
INSERT INTO `tao_region` VALUES ('3037', '泽库县', '3034', '632323', '3');
INSERT INTO `tao_region` VALUES ('3038', '河南蒙古族自治县', '3034', '632324', '3');
INSERT INTO `tao_region` VALUES ('3039', '海南藏族自治州', '3013', '632500', '2');
INSERT INTO `tao_region` VALUES ('3040', '共和县', '3039', '632521', '3');
INSERT INTO `tao_region` VALUES ('3041', '同德县', '3039', '632522', '3');
INSERT INTO `tao_region` VALUES ('3042', '贵德县', '3039', '632523', '3');
INSERT INTO `tao_region` VALUES ('3043', '兴海县', '3039', '632524', '3');
INSERT INTO `tao_region` VALUES ('3044', '贵南县', '3039', '632525', '3');
INSERT INTO `tao_region` VALUES ('3045', '果洛藏族自治州', '3013', '632600', '2');
INSERT INTO `tao_region` VALUES ('3046', '玛沁县', '3045', '632621', '3');
INSERT INTO `tao_region` VALUES ('3047', '班玛县', '3045', '632622', '3');
INSERT INTO `tao_region` VALUES ('3048', '甘德县', '3045', '632623', '3');
INSERT INTO `tao_region` VALUES ('3049', '达日县', '3045', '632624', '3');
INSERT INTO `tao_region` VALUES ('3050', '久治县', '3045', '632625', '3');
INSERT INTO `tao_region` VALUES ('3051', '玛多县', '3045', '632626', '3');
INSERT INTO `tao_region` VALUES ('3052', '玉树藏族自治州', '3013', '632700', '2');
INSERT INTO `tao_region` VALUES ('3053', '玉树市', '3052', '632701', '3');
INSERT INTO `tao_region` VALUES ('3054', '杂多县', '3052', '632722', '3');
INSERT INTO `tao_region` VALUES ('3055', '称多县', '3052', '632723', '3');
INSERT INTO `tao_region` VALUES ('3056', '治多县', '3052', '632724', '3');
INSERT INTO `tao_region` VALUES ('3057', '囊谦县', '3052', '632725', '3');
INSERT INTO `tao_region` VALUES ('3058', '曲麻莱县', '3052', '632726', '3');
INSERT INTO `tao_region` VALUES ('3059', '海西蒙古族藏族自治州', '3013', '632800', '2');
INSERT INTO `tao_region` VALUES ('3060', '格尔木市', '3059', '632801', '3');
INSERT INTO `tao_region` VALUES ('3061', '德令哈市', '3059', '632802', '3');
INSERT INTO `tao_region` VALUES ('3062', '茫崖市', '3059', '632803', '3');
INSERT INTO `tao_region` VALUES ('3063', '乌兰县', '3059', '632821', '3');
INSERT INTO `tao_region` VALUES ('3064', '都兰县', '3059', '632822', '3');
INSERT INTO `tao_region` VALUES ('3065', '天峻县', '3059', '632823', '3');
INSERT INTO `tao_region` VALUES ('3066', '宁夏回族自治区', '0', '640000', '1');
INSERT INTO `tao_region` VALUES ('3067', '银川市', '3066', '640100', '2');
INSERT INTO `tao_region` VALUES ('3068', '兴庆区', '3067', '640104', '3');
INSERT INTO `tao_region` VALUES ('3069', '西夏区', '3067', '640105', '3');
INSERT INTO `tao_region` VALUES ('3070', '金凤区', '3067', '640106', '3');
INSERT INTO `tao_region` VALUES ('3071', '永宁县', '3067', '640121', '3');
INSERT INTO `tao_region` VALUES ('3072', '贺兰县', '3067', '640122', '3');
INSERT INTO `tao_region` VALUES ('3073', '灵武市', '3067', '640181', '3');
INSERT INTO `tao_region` VALUES ('3074', '石嘴山市', '3066', '640200', '2');
INSERT INTO `tao_region` VALUES ('3075', '大武口区', '3074', '640202', '3');
INSERT INTO `tao_region` VALUES ('3076', '惠农区', '3074', '640205', '3');
INSERT INTO `tao_region` VALUES ('3077', '平罗县', '3074', '640221', '3');
INSERT INTO `tao_region` VALUES ('3078', '吴忠市', '3066', '640300', '2');
INSERT INTO `tao_region` VALUES ('3079', '利通区', '3078', '640302', '3');
INSERT INTO `tao_region` VALUES ('3080', '红寺堡区', '3078', '640303', '3');
INSERT INTO `tao_region` VALUES ('3081', '盐池县', '3078', '640323', '3');
INSERT INTO `tao_region` VALUES ('3082', '同心县', '3078', '640324', '3');
INSERT INTO `tao_region` VALUES ('3083', '青铜峡市', '3078', '640381', '3');
INSERT INTO `tao_region` VALUES ('3084', '固原市', '3066', '640400', '2');
INSERT INTO `tao_region` VALUES ('3085', '原州区', '3084', '640402', '3');
INSERT INTO `tao_region` VALUES ('3086', '西吉县', '3084', '640422', '3');
INSERT INTO `tao_region` VALUES ('3087', '隆德县', '3084', '640423', '3');
INSERT INTO `tao_region` VALUES ('3088', '泾源县', '3084', '640424', '3');
INSERT INTO `tao_region` VALUES ('3089', '彭阳县', '3084', '640425', '3');
INSERT INTO `tao_region` VALUES ('3090', '中卫市', '3066', '640500', '2');
INSERT INTO `tao_region` VALUES ('3091', '沙坡头区', '3090', '640502', '3');
INSERT INTO `tao_region` VALUES ('3092', '中宁县', '3090', '640521', '3');
INSERT INTO `tao_region` VALUES ('3093', '海原县', '3090', '640522', '3');
INSERT INTO `tao_region` VALUES ('3094', '新疆维吾尔自治区', '0', '650000', '1');
INSERT INTO `tao_region` VALUES ('3095', '乌鲁木齐市', '3094', '650100', '2');
INSERT INTO `tao_region` VALUES ('3096', '天山区', '3095', '650102', '3');
INSERT INTO `tao_region` VALUES ('3097', '沙依巴克区', '3095', '650103', '3');
INSERT INTO `tao_region` VALUES ('3098', '新市区', '3095', '650104', '3');
INSERT INTO `tao_region` VALUES ('3099', '水磨沟区', '3095', '650105', '3');
INSERT INTO `tao_region` VALUES ('3100', '头屯河区', '3095', '650106', '3');
INSERT INTO `tao_region` VALUES ('3101', '达坂城区', '3095', '650107', '3');
INSERT INTO `tao_region` VALUES ('3102', '米东区', '3095', '650109', '3');
INSERT INTO `tao_region` VALUES ('3103', '乌鲁木齐县', '3095', '650121', '3');
INSERT INTO `tao_region` VALUES ('3104', '克拉玛依市', '3094', '650200', '2');
INSERT INTO `tao_region` VALUES ('3105', '独山子区', '3104', '650202', '3');
INSERT INTO `tao_region` VALUES ('3106', '克拉玛依区', '3104', '650203', '3');
INSERT INTO `tao_region` VALUES ('3107', '白碱滩区', '3104', '650204', '3');
INSERT INTO `tao_region` VALUES ('3108', '乌尔禾区', '3104', '650205', '3');
INSERT INTO `tao_region` VALUES ('3109', '吐鲁番市', '3094', '650400', '2');
INSERT INTO `tao_region` VALUES ('3110', '高昌区', '3109', '650402', '3');
INSERT INTO `tao_region` VALUES ('3111', '鄯善县', '3109', '650421', '3');
INSERT INTO `tao_region` VALUES ('3112', '托克逊县', '3109', '650422', '3');
INSERT INTO `tao_region` VALUES ('3113', '哈密市', '3094', '650500', '2');
INSERT INTO `tao_region` VALUES ('3114', '伊州区', '3113', '650502', '3');
INSERT INTO `tao_region` VALUES ('3115', '巴里坤哈萨克自治县', '3113', '650521', '3');
INSERT INTO `tao_region` VALUES ('3116', '伊吾县', '3113', '650522', '3');
INSERT INTO `tao_region` VALUES ('3117', '昌吉回族自治州', '3094', '652300', '2');
INSERT INTO `tao_region` VALUES ('3118', '昌吉市', '3117', '652301', '3');
INSERT INTO `tao_region` VALUES ('3119', '阜康市', '3117', '652302', '3');
INSERT INTO `tao_region` VALUES ('3120', '呼图壁县', '3117', '652323', '3');
INSERT INTO `tao_region` VALUES ('3121', '玛纳斯县', '3117', '652324', '3');
INSERT INTO `tao_region` VALUES ('3122', '奇台县', '3117', '652325', '3');
INSERT INTO `tao_region` VALUES ('3123', '吉木萨尔县', '3117', '652327', '3');
INSERT INTO `tao_region` VALUES ('3124', '木垒哈萨克自治县', '3117', '652328', '3');
INSERT INTO `tao_region` VALUES ('3125', '博尔塔拉蒙古自治州', '3094', '652700', '2');
INSERT INTO `tao_region` VALUES ('3126', '博乐市', '3125', '652701', '3');
INSERT INTO `tao_region` VALUES ('3127', '阿拉山口市', '3125', '652702', '3');
INSERT INTO `tao_region` VALUES ('3128', '精河县', '3125', '652722', '3');
INSERT INTO `tao_region` VALUES ('3129', '温泉县', '3125', '652723', '3');
INSERT INTO `tao_region` VALUES ('3130', '巴音郭楞蒙古自治州', '3094', '652800', '2');
INSERT INTO `tao_region` VALUES ('3131', '库尔勒市', '3130', '652801', '3');
INSERT INTO `tao_region` VALUES ('3132', '轮台县', '3130', '652822', '3');
INSERT INTO `tao_region` VALUES ('3133', '尉犁县', '3130', '652823', '3');
INSERT INTO `tao_region` VALUES ('3134', '若羌县', '3130', '652824', '3');
INSERT INTO `tao_region` VALUES ('3135', '且末县', '3130', '652825', '3');
INSERT INTO `tao_region` VALUES ('3136', '焉耆回族自治县', '3130', '652826', '3');
INSERT INTO `tao_region` VALUES ('3137', '和静县', '3130', '652827', '3');
INSERT INTO `tao_region` VALUES ('3138', '和硕县', '3130', '652828', '3');
INSERT INTO `tao_region` VALUES ('3139', '博湖县', '3130', '652829', '3');
INSERT INTO `tao_region` VALUES ('3140', '阿克苏地区', '3094', '652900', '2');
INSERT INTO `tao_region` VALUES ('3141', '阿克苏市', '3140', '652901', '3');
INSERT INTO `tao_region` VALUES ('3142', '库车市', '3140', '652902', '3');
INSERT INTO `tao_region` VALUES ('3143', '温宿县', '3140', '652922', '3');
INSERT INTO `tao_region` VALUES ('3144', '沙雅县', '3140', '652924', '3');
INSERT INTO `tao_region` VALUES ('3145', '新和县', '3140', '652925', '3');
INSERT INTO `tao_region` VALUES ('3146', '拜城县', '3140', '652926', '3');
INSERT INTO `tao_region` VALUES ('3147', '乌什县', '3140', '652927', '3');
INSERT INTO `tao_region` VALUES ('3148', '阿瓦提县', '3140', '652928', '3');
INSERT INTO `tao_region` VALUES ('3149', '柯坪县', '3140', '652929', '3');
INSERT INTO `tao_region` VALUES ('3150', '克孜勒苏柯尔克孜自治州', '3094', '653000', '2');
INSERT INTO `tao_region` VALUES ('3151', '阿图什市', '3150', '653001', '3');
INSERT INTO `tao_region` VALUES ('3152', '阿克陶县', '3150', '653022', '3');
INSERT INTO `tao_region` VALUES ('3153', '阿合奇县', '3150', '653023', '3');
INSERT INTO `tao_region` VALUES ('3154', '乌恰县', '3150', '653024', '3');
INSERT INTO `tao_region` VALUES ('3155', '喀什地区', '3094', '653100', '2');
INSERT INTO `tao_region` VALUES ('3156', '喀什市', '3155', '653101', '3');
INSERT INTO `tao_region` VALUES ('3157', '疏附县', '3155', '653121', '3');
INSERT INTO `tao_region` VALUES ('3158', '疏勒县', '3155', '653122', '3');
INSERT INTO `tao_region` VALUES ('3159', '英吉沙县', '3155', '653123', '3');
INSERT INTO `tao_region` VALUES ('3160', '泽普县', '3155', '653124', '3');
INSERT INTO `tao_region` VALUES ('3161', '莎车县', '3155', '653125', '3');
INSERT INTO `tao_region` VALUES ('3162', '叶城县', '3155', '653126', '3');
INSERT INTO `tao_region` VALUES ('3163', '麦盖提县', '3155', '653127', '3');
INSERT INTO `tao_region` VALUES ('3164', '岳普湖县', '3155', '653128', '3');
INSERT INTO `tao_region` VALUES ('3165', '伽师县', '3155', '653129', '3');
INSERT INTO `tao_region` VALUES ('3166', '巴楚县', '3155', '653130', '3');
INSERT INTO `tao_region` VALUES ('3167', '塔什库尔干塔吉克自治县', '3155', '653131', '3');
INSERT INTO `tao_region` VALUES ('3168', '和田地区', '3094', '653200', '2');
INSERT INTO `tao_region` VALUES ('3169', '和田市', '3168', '653201', '3');
INSERT INTO `tao_region` VALUES ('3170', '和田县', '3168', '653221', '3');
INSERT INTO `tao_region` VALUES ('3171', '墨玉县', '3168', '653222', '3');
INSERT INTO `tao_region` VALUES ('3172', '皮山县', '3168', '653223', '3');
INSERT INTO `tao_region` VALUES ('3173', '洛浦县', '3168', '653224', '3');
INSERT INTO `tao_region` VALUES ('3174', '策勒县', '3168', '653225', '3');
INSERT INTO `tao_region` VALUES ('3175', '于田县', '3168', '653226', '3');
INSERT INTO `tao_region` VALUES ('3176', '民丰县', '3168', '653227', '3');
INSERT INTO `tao_region` VALUES ('3177', '伊犁哈萨克自治州', '3094', '654000', '2');
INSERT INTO `tao_region` VALUES ('3178', '伊宁市', '3177', '654002', '3');
INSERT INTO `tao_region` VALUES ('3179', '奎屯市', '3177', '654003', '3');
INSERT INTO `tao_region` VALUES ('3180', '霍尔果斯市', '3177', '654004', '3');
INSERT INTO `tao_region` VALUES ('3181', '伊宁县', '3177', '654021', '3');
INSERT INTO `tao_region` VALUES ('3182', '察布查尔锡伯自治县', '3177', '654022', '3');
INSERT INTO `tao_region` VALUES ('3183', '霍城县', '3177', '654023', '3');
INSERT INTO `tao_region` VALUES ('3184', '巩留县', '3177', '654024', '3');
INSERT INTO `tao_region` VALUES ('3185', '新源县', '3177', '654025', '3');
INSERT INTO `tao_region` VALUES ('3186', '昭苏县', '3177', '654026', '3');
INSERT INTO `tao_region` VALUES ('3187', '特克斯县', '3177', '654027', '3');
INSERT INTO `tao_region` VALUES ('3188', '尼勒克县', '3177', '654028', '3');
INSERT INTO `tao_region` VALUES ('3189', '塔城地区', '3094', '654200', '2');
INSERT INTO `tao_region` VALUES ('3190', '塔城市', '3189', '654201', '3');
INSERT INTO `tao_region` VALUES ('3191', '乌苏市', '3189', '654202', '3');
INSERT INTO `tao_region` VALUES ('3192', '额敏县', '3189', '654221', '3');
INSERT INTO `tao_region` VALUES ('3194', '托里县', '3189', '654224', '3');
INSERT INTO `tao_region` VALUES ('3195', '裕民县', '3189', '654225', '3');
INSERT INTO `tao_region` VALUES ('3196', '和布克赛尔蒙古自治县', '3189', '654226', '3');
INSERT INTO `tao_region` VALUES ('3197', '阿勒泰地区', '3094', '654300', '2');
INSERT INTO `tao_region` VALUES ('3198', '阿勒泰市', '3197', '654301', '3');
INSERT INTO `tao_region` VALUES ('3199', '布尔津县', '3197', '654321', '3');
INSERT INTO `tao_region` VALUES ('3200', '富蕴县', '3197', '654322', '3');
INSERT INTO `tao_region` VALUES ('3201', '福海县', '3197', '654323', '3');
INSERT INTO `tao_region` VALUES ('3202', '哈巴河县', '3197', '654324', '3');
INSERT INTO `tao_region` VALUES ('3203', '青河县', '3197', '654325', '3');
INSERT INTO `tao_region` VALUES ('3204', '吉木乃县', '3197', '654326', '3');
INSERT INTO `tao_region` VALUES ('3215', '台湾省', '0', '710000', '1');
INSERT INTO `tao_region` VALUES ('3216', '台北市', '3215', '710100', '2');
INSERT INTO `tao_region` VALUES ('3217', '中正区', '3216', '710101', '3');
INSERT INTO `tao_region` VALUES ('3218', '大同区', '3216', '710102', '3');
INSERT INTO `tao_region` VALUES ('3219', '中山区', '3216', '710103', '3');
INSERT INTO `tao_region` VALUES ('3220', '松山区', '3216', '710104', '3');
INSERT INTO `tao_region` VALUES ('3221', '大安区', '3216', '710105', '3');
INSERT INTO `tao_region` VALUES ('3222', '万华区', '3216', '710106', '3');
INSERT INTO `tao_region` VALUES ('3223', '信义区', '3216', '710107', '3');
INSERT INTO `tao_region` VALUES ('3224', '士林区', '3216', '710108', '3');
INSERT INTO `tao_region` VALUES ('3225', '北投区', '3216', '710109', '3');
INSERT INTO `tao_region` VALUES ('3226', '内湖区', '3216', '710110', '3');
INSERT INTO `tao_region` VALUES ('3227', '南港区', '3216', '710111', '3');
INSERT INTO `tao_region` VALUES ('3228', '文山区', '3216', '710112', '3');
INSERT INTO `tao_region` VALUES ('3229', '高雄市', '3215', '710200', '2');
INSERT INTO `tao_region` VALUES ('3230', '新兴区', '3229', '710201', '3');
INSERT INTO `tao_region` VALUES ('3231', '前金区', '3229', '710202', '3');
INSERT INTO `tao_region` VALUES ('3232', '苓雅区', '3229', '710203', '3');
INSERT INTO `tao_region` VALUES ('3233', '盐埕区', '3229', '710204', '3');
INSERT INTO `tao_region` VALUES ('3234', '鼓山区', '3229', '710205', '3');
INSERT INTO `tao_region` VALUES ('3235', '旗津区', '3229', '710206', '3');
INSERT INTO `tao_region` VALUES ('3236', '前镇区', '3229', '710207', '3');
INSERT INTO `tao_region` VALUES ('3237', '三民区', '3229', '710208', '3');
INSERT INTO `tao_region` VALUES ('3238', '左营区', '3229', '710209', '3');
INSERT INTO `tao_region` VALUES ('3239', '楠梓区', '3229', '710210', '3');
INSERT INTO `tao_region` VALUES ('3240', '小港区', '3229', '710211', '3');
INSERT INTO `tao_region` VALUES ('3241', '仁武区', '3229', '710242', '3');
INSERT INTO `tao_region` VALUES ('3242', '大社区', '3229', '710243', '3');
INSERT INTO `tao_region` VALUES ('3243', '冈山区', '3229', '710244', '3');
INSERT INTO `tao_region` VALUES ('3244', '路竹区', '3229', '710245', '3');
INSERT INTO `tao_region` VALUES ('3245', '阿莲区', '3229', '710246', '3');
INSERT INTO `tao_region` VALUES ('3246', '田寮区', '3229', '710247', '3');
INSERT INTO `tao_region` VALUES ('3247', '燕巢区', '3229', '710248', '3');
INSERT INTO `tao_region` VALUES ('3248', '桥头区', '3229', '710249', '3');
INSERT INTO `tao_region` VALUES ('3249', '梓官区', '3229', '710250', '3');
INSERT INTO `tao_region` VALUES ('3250', '弥陀区', '3229', '710251', '3');
INSERT INTO `tao_region` VALUES ('3251', '永安区', '3229', '710252', '3');
INSERT INTO `tao_region` VALUES ('3252', '湖内区', '3229', '710253', '3');
INSERT INTO `tao_region` VALUES ('3253', '凤山区', '3229', '710254', '3');
INSERT INTO `tao_region` VALUES ('3254', '大寮区', '3229', '710255', '3');
INSERT INTO `tao_region` VALUES ('3255', '林园区', '3229', '710256', '3');
INSERT INTO `tao_region` VALUES ('3256', '鸟松区', '3229', '710257', '3');
INSERT INTO `tao_region` VALUES ('3257', '大树区', '3229', '710258', '3');
INSERT INTO `tao_region` VALUES ('3258', '旗山区', '3229', '710259', '3');
INSERT INTO `tao_region` VALUES ('3259', '美浓区', '3229', '710260', '3');
INSERT INTO `tao_region` VALUES ('3260', '六龟区', '3229', '710261', '3');
INSERT INTO `tao_region` VALUES ('3261', '内门区', '3229', '710262', '3');
INSERT INTO `tao_region` VALUES ('3262', '杉林区', '3229', '710263', '3');
INSERT INTO `tao_region` VALUES ('3263', '甲仙区', '3229', '710264', '3');
INSERT INTO `tao_region` VALUES ('3264', '桃源区', '3229', '710265', '3');
INSERT INTO `tao_region` VALUES ('3265', '那玛夏区', '3229', '710266', '3');
INSERT INTO `tao_region` VALUES ('3266', '茂林区', '3229', '710267', '3');
INSERT INTO `tao_region` VALUES ('3267', '茄萣区', '3229', '710268', '3');
INSERT INTO `tao_region` VALUES ('3268', '台南市', '3215', '710300', '2');
INSERT INTO `tao_region` VALUES ('3269', '中西区', '3268', '710301', '3');
INSERT INTO `tao_region` VALUES ('3270', '东区', '3268', '710302', '3');
INSERT INTO `tao_region` VALUES ('3271', '南区', '3268', '710303', '3');
INSERT INTO `tao_region` VALUES ('3272', '北区', '3268', '710304', '3');
INSERT INTO `tao_region` VALUES ('3273', '安平区', '3268', '710305', '3');
INSERT INTO `tao_region` VALUES ('3274', '安南区', '3268', '710306', '3');
INSERT INTO `tao_region` VALUES ('3275', '永康区', '3268', '710339', '3');
INSERT INTO `tao_region` VALUES ('3276', '归仁区', '3268', '710340', '3');
INSERT INTO `tao_region` VALUES ('3277', '新化区', '3268', '710341', '3');
INSERT INTO `tao_region` VALUES ('3278', '左镇区', '3268', '710342', '3');
INSERT INTO `tao_region` VALUES ('3279', '玉井区', '3268', '710343', '3');
INSERT INTO `tao_region` VALUES ('3280', '楠西区', '3268', '710344', '3');
INSERT INTO `tao_region` VALUES ('3281', '南化区', '3268', '710345', '3');
INSERT INTO `tao_region` VALUES ('3282', '仁德区', '3268', '710346', '3');
INSERT INTO `tao_region` VALUES ('3283', '关庙区', '3268', '710347', '3');
INSERT INTO `tao_region` VALUES ('3284', '龙崎区', '3268', '710348', '3');
INSERT INTO `tao_region` VALUES ('3285', '官田区', '3268', '710349', '3');
INSERT INTO `tao_region` VALUES ('3286', '麻豆区', '3268', '710350', '3');
INSERT INTO `tao_region` VALUES ('3287', '佳里区', '3268', '710351', '3');
INSERT INTO `tao_region` VALUES ('3288', '西港区', '3268', '710352', '3');
INSERT INTO `tao_region` VALUES ('3289', '七股区', '3268', '710353', '3');
INSERT INTO `tao_region` VALUES ('3290', '将军区', '3268', '710354', '3');
INSERT INTO `tao_region` VALUES ('3291', '学甲区', '3268', '710355', '3');
INSERT INTO `tao_region` VALUES ('3292', '北门区', '3268', '710356', '3');
INSERT INTO `tao_region` VALUES ('3293', '新营区', '3268', '710357', '3');
INSERT INTO `tao_region` VALUES ('3294', '后壁区', '3268', '710358', '3');
INSERT INTO `tao_region` VALUES ('3295', '白河区', '3268', '710359', '3');
INSERT INTO `tao_region` VALUES ('3296', '东山区', '3268', '710360', '3');
INSERT INTO `tao_region` VALUES ('3297', '六甲区', '3268', '710361', '3');
INSERT INTO `tao_region` VALUES ('3298', '下营区', '3268', '710362', '3');
INSERT INTO `tao_region` VALUES ('3299', '柳营区', '3268', '710363', '3');
INSERT INTO `tao_region` VALUES ('3300', '盐水区', '3268', '710364', '3');
INSERT INTO `tao_region` VALUES ('3301', '善化区', '3268', '710365', '3');
INSERT INTO `tao_region` VALUES ('3302', '大内区', '3268', '710366', '3');
INSERT INTO `tao_region` VALUES ('3303', '山上区', '3268', '710367', '3');
INSERT INTO `tao_region` VALUES ('3304', '新市区', '3268', '710368', '3');
INSERT INTO `tao_region` VALUES ('3305', '安定区', '3268', '710369', '3');
INSERT INTO `tao_region` VALUES ('3306', '台中市', '3215', '710400', '2');
INSERT INTO `tao_region` VALUES ('3307', '中区', '3306', '710401', '3');
INSERT INTO `tao_region` VALUES ('3308', '东区', '3306', '710402', '3');
INSERT INTO `tao_region` VALUES ('3309', '南区', '3306', '710403', '3');
INSERT INTO `tao_region` VALUES ('3310', '西区', '3306', '710404', '3');
INSERT INTO `tao_region` VALUES ('3311', '北区', '3306', '710405', '3');
INSERT INTO `tao_region` VALUES ('3312', '北屯区', '3306', '710406', '3');
INSERT INTO `tao_region` VALUES ('3313', '西屯区', '3306', '710407', '3');
INSERT INTO `tao_region` VALUES ('3314', '南屯区', '3306', '710408', '3');
INSERT INTO `tao_region` VALUES ('3315', '太平区', '3306', '710431', '3');
INSERT INTO `tao_region` VALUES ('3316', '大里区', '3306', '710432', '3');
INSERT INTO `tao_region` VALUES ('3317', '雾峰区', '3306', '710433', '3');
INSERT INTO `tao_region` VALUES ('3318', '乌日区', '3306', '710434', '3');
INSERT INTO `tao_region` VALUES ('3319', '丰原区', '3306', '710435', '3');
INSERT INTO `tao_region` VALUES ('3320', '后里区', '3306', '710436', '3');
INSERT INTO `tao_region` VALUES ('3321', '石冈区', '3306', '710437', '3');
INSERT INTO `tao_region` VALUES ('3322', '东势区', '3306', '710438', '3');
INSERT INTO `tao_region` VALUES ('3323', '和平区', '3306', '710439', '3');
INSERT INTO `tao_region` VALUES ('3324', '新社区', '3306', '710440', '3');
INSERT INTO `tao_region` VALUES ('3325', '潭子区', '3306', '710441', '3');
INSERT INTO `tao_region` VALUES ('3326', '大雅区', '3306', '710442', '3');
INSERT INTO `tao_region` VALUES ('3327', '神冈区', '3306', '710443', '3');
INSERT INTO `tao_region` VALUES ('3328', '大肚区', '3306', '710444', '3');
INSERT INTO `tao_region` VALUES ('3329', '沙鹿区', '3306', '710445', '3');
INSERT INTO `tao_region` VALUES ('3330', '龙井区', '3306', '710446', '3');
INSERT INTO `tao_region` VALUES ('3331', '梧栖区', '3306', '710447', '3');
INSERT INTO `tao_region` VALUES ('3332', '清水区', '3306', '710448', '3');
INSERT INTO `tao_region` VALUES ('3333', '大甲区', '3306', '710449', '3');
INSERT INTO `tao_region` VALUES ('3334', '外埔区', '3306', '710450', '3');
INSERT INTO `tao_region` VALUES ('3335', '大安区', '3306', '710451', '3');
INSERT INTO `tao_region` VALUES ('3336', '南投县', '3215', '710600', '2');
INSERT INTO `tao_region` VALUES ('3337', '南投市', '3336', '710614', '3');
INSERT INTO `tao_region` VALUES ('3338', '中寮乡', '3336', '710615', '3');
INSERT INTO `tao_region` VALUES ('3339', '草屯镇', '3336', '710616', '3');
INSERT INTO `tao_region` VALUES ('3340', '国姓乡', '3336', '710617', '3');
INSERT INTO `tao_region` VALUES ('3341', '埔里镇', '3336', '710618', '3');
INSERT INTO `tao_region` VALUES ('3342', '仁爱乡', '3336', '710619', '3');
INSERT INTO `tao_region` VALUES ('3343', '名间乡', '3336', '710620', '3');
INSERT INTO `tao_region` VALUES ('3344', '集集镇', '3336', '710621', '3');
INSERT INTO `tao_region` VALUES ('3345', '水里乡', '3336', '710622', '3');
INSERT INTO `tao_region` VALUES ('3346', '鱼池乡', '3336', '710623', '3');
INSERT INTO `tao_region` VALUES ('3347', '信义乡', '3336', '710624', '3');
INSERT INTO `tao_region` VALUES ('3348', '竹山镇', '3336', '710625', '3');
INSERT INTO `tao_region` VALUES ('3349', '鹿谷乡', '3336', '710626', '3');
INSERT INTO `tao_region` VALUES ('3350', '基隆市', '3215', '710700', '2');
INSERT INTO `tao_region` VALUES ('3351', '仁爱区', '3350', '710701', '3');
INSERT INTO `tao_region` VALUES ('3352', '信义区', '3350', '710702', '3');
INSERT INTO `tao_region` VALUES ('3353', '中正区', '3350', '710703', '3');
INSERT INTO `tao_region` VALUES ('3354', '中山区', '3350', '710704', '3');
INSERT INTO `tao_region` VALUES ('3355', '安乐区', '3350', '710705', '3');
INSERT INTO `tao_region` VALUES ('3356', '暖暖区', '3350', '710706', '3');
INSERT INTO `tao_region` VALUES ('3357', '七堵区', '3350', '710707', '3');
INSERT INTO `tao_region` VALUES ('3358', '新竹市', '3215', '710800', '2');
INSERT INTO `tao_region` VALUES ('3359', '东区', '3358', '710801', '3');
INSERT INTO `tao_region` VALUES ('3360', '北区', '3358', '710802', '3');
INSERT INTO `tao_region` VALUES ('3361', '香山区', '3358', '710803', '3');
INSERT INTO `tao_region` VALUES ('3362', '嘉义市', '3215', '710900', '2');
INSERT INTO `tao_region` VALUES ('3363', '东区', '3362', '710901', '3');
INSERT INTO `tao_region` VALUES ('3364', '西区', '3362', '710902', '3');
INSERT INTO `tao_region` VALUES ('3365', '新北市', '3215', '711100', '2');
INSERT INTO `tao_region` VALUES ('3366', '万里区', '3365', '711130', '3');
INSERT INTO `tao_region` VALUES ('3367', '金山区', '3365', '711131', '3');
INSERT INTO `tao_region` VALUES ('3368', '板桥区', '3365', '711132', '3');
INSERT INTO `tao_region` VALUES ('3369', '汐止区', '3365', '711133', '3');
INSERT INTO `tao_region` VALUES ('3370', '深坑区', '3365', '711134', '3');
INSERT INTO `tao_region` VALUES ('3371', '石碇区', '3365', '711135', '3');
INSERT INTO `tao_region` VALUES ('3372', '瑞芳区', '3365', '711136', '3');
INSERT INTO `tao_region` VALUES ('3373', '平溪区', '3365', '711137', '3');
INSERT INTO `tao_region` VALUES ('3374', '双溪区', '3365', '711138', '3');
INSERT INTO `tao_region` VALUES ('3375', '贡寮区', '3365', '711139', '3');
INSERT INTO `tao_region` VALUES ('3376', '新店区', '3365', '711140', '3');
INSERT INTO `tao_region` VALUES ('3377', '坪林区', '3365', '711141', '3');
INSERT INTO `tao_region` VALUES ('3378', '乌来区', '3365', '711142', '3');
INSERT INTO `tao_region` VALUES ('3379', '永和区', '3365', '711143', '3');
INSERT INTO `tao_region` VALUES ('3380', '中和区', '3365', '711144', '3');
INSERT INTO `tao_region` VALUES ('3381', '土城区', '3365', '711145', '3');
INSERT INTO `tao_region` VALUES ('3382', '三峡区', '3365', '711146', '3');
INSERT INTO `tao_region` VALUES ('3383', '树林区', '3365', '711147', '3');
INSERT INTO `tao_region` VALUES ('3384', '莺歌区', '3365', '711148', '3');
INSERT INTO `tao_region` VALUES ('3385', '三重区', '3365', '711149', '3');
INSERT INTO `tao_region` VALUES ('3386', '新庄区', '3365', '711150', '3');
INSERT INTO `tao_region` VALUES ('3387', '泰山区', '3365', '711151', '3');
INSERT INTO `tao_region` VALUES ('3388', '林口区', '3365', '711152', '3');
INSERT INTO `tao_region` VALUES ('3389', '芦洲区', '3365', '711153', '3');
INSERT INTO `tao_region` VALUES ('3390', '五股区', '3365', '711154', '3');
INSERT INTO `tao_region` VALUES ('3391', '八里区', '3365', '711155', '3');
INSERT INTO `tao_region` VALUES ('3392', '淡水区', '3365', '711156', '3');
INSERT INTO `tao_region` VALUES ('3393', '三芝区', '3365', '711157', '3');
INSERT INTO `tao_region` VALUES ('3394', '石门区', '3365', '711158', '3');
INSERT INTO `tao_region` VALUES ('3395', '宜兰县', '3215', '711200', '2');
INSERT INTO `tao_region` VALUES ('3396', '宜兰市', '3395', '711214', '3');
INSERT INTO `tao_region` VALUES ('3397', '头城镇', '3395', '711215', '3');
INSERT INTO `tao_region` VALUES ('3398', '礁溪乡', '3395', '711216', '3');
INSERT INTO `tao_region` VALUES ('3399', '壮围乡', '3395', '711217', '3');
INSERT INTO `tao_region` VALUES ('3400', '员山乡', '3395', '711218', '3');
INSERT INTO `tao_region` VALUES ('3401', '罗东镇', '3395', '711219', '3');
INSERT INTO `tao_region` VALUES ('3402', '三星乡', '3395', '711220', '3');
INSERT INTO `tao_region` VALUES ('3403', '大同乡', '3395', '711221', '3');
INSERT INTO `tao_region` VALUES ('3404', '五结乡', '3395', '711222', '3');
INSERT INTO `tao_region` VALUES ('3405', '冬山乡', '3395', '711223', '3');
INSERT INTO `tao_region` VALUES ('3406', '苏澳镇', '3395', '711224', '3');
INSERT INTO `tao_region` VALUES ('3407', '南澳乡', '3395', '711225', '3');
INSERT INTO `tao_region` VALUES ('3408', '新竹县', '3215', '711300', '2');
INSERT INTO `tao_region` VALUES ('3409', '竹北市', '3408', '711314', '3');
INSERT INTO `tao_region` VALUES ('3410', '湖口乡', '3408', '711315', '3');
INSERT INTO `tao_region` VALUES ('3411', '新丰乡', '3408', '711316', '3');
INSERT INTO `tao_region` VALUES ('3412', '新埔镇', '3408', '711317', '3');
INSERT INTO `tao_region` VALUES ('3413', '关西镇', '3408', '711318', '3');
INSERT INTO `tao_region` VALUES ('3414', '芎林乡', '3408', '711319', '3');
INSERT INTO `tao_region` VALUES ('3415', '宝山乡', '3408', '711320', '3');
INSERT INTO `tao_region` VALUES ('3416', '竹东镇', '3408', '711321', '3');
INSERT INTO `tao_region` VALUES ('3417', '五峰乡', '3408', '711322', '3');
INSERT INTO `tao_region` VALUES ('3418', '横山乡', '3408', '711323', '3');
INSERT INTO `tao_region` VALUES ('3419', '尖石乡', '3408', '711324', '3');
INSERT INTO `tao_region` VALUES ('3420', '北埔乡', '3408', '711325', '3');
INSERT INTO `tao_region` VALUES ('3421', '峨眉乡', '3408', '711326', '3');
INSERT INTO `tao_region` VALUES ('3422', '桃园市', '3215', '711400', '2');
INSERT INTO `tao_region` VALUES ('3423', '中坜区', '3422', '711414', '3');
INSERT INTO `tao_region` VALUES ('3424', '平镇区', '3422', '711415', '3');
INSERT INTO `tao_region` VALUES ('3425', '龙潭区', '3422', '711416', '3');
INSERT INTO `tao_region` VALUES ('3426', '杨梅区', '3422', '711417', '3');
INSERT INTO `tao_region` VALUES ('3427', '新屋区', '3422', '711418', '3');
INSERT INTO `tao_region` VALUES ('3428', '观音区', '3422', '711419', '3');
INSERT INTO `tao_region` VALUES ('3429', '桃园区', '3422', '711420', '3');
INSERT INTO `tao_region` VALUES ('3430', '龟山区', '3422', '711421', '3');
INSERT INTO `tao_region` VALUES ('3431', '八德区', '3422', '711422', '3');
INSERT INTO `tao_region` VALUES ('3432', '大溪区', '3422', '711423', '3');
INSERT INTO `tao_region` VALUES ('3433', '复兴区', '3422', '711424', '3');
INSERT INTO `tao_region` VALUES ('3434', '大园区', '3422', '711425', '3');
INSERT INTO `tao_region` VALUES ('3435', '芦竹区', '3422', '711426', '3');
INSERT INTO `tao_region` VALUES ('3436', '苗栗县', '3215', '711500', '2');
INSERT INTO `tao_region` VALUES ('3437', '竹南镇', '3436', '711519', '3');
INSERT INTO `tao_region` VALUES ('3438', '头份市', '3436', '711520', '3');
INSERT INTO `tao_region` VALUES ('3439', '三湾乡', '3436', '711521', '3');
INSERT INTO `tao_region` VALUES ('3440', '南庄乡', '3436', '711522', '3');
INSERT INTO `tao_region` VALUES ('3441', '狮潭乡', '3436', '711523', '3');
INSERT INTO `tao_region` VALUES ('3442', '后龙镇', '3436', '711524', '3');
INSERT INTO `tao_region` VALUES ('3443', '通霄镇', '3436', '711525', '3');
INSERT INTO `tao_region` VALUES ('3444', '苑里镇', '3436', '711526', '3');
INSERT INTO `tao_region` VALUES ('3445', '苗栗市', '3436', '711527', '3');
INSERT INTO `tao_region` VALUES ('3446', '造桥乡', '3436', '711528', '3');
INSERT INTO `tao_region` VALUES ('3447', '头屋乡', '3436', '711529', '3');
INSERT INTO `tao_region` VALUES ('3448', '公馆乡', '3436', '711530', '3');
INSERT INTO `tao_region` VALUES ('3449', '大湖乡', '3436', '711531', '3');
INSERT INTO `tao_region` VALUES ('3450', '泰安乡', '3436', '711532', '3');
INSERT INTO `tao_region` VALUES ('3451', '铜锣乡', '3436', '711533', '3');
INSERT INTO `tao_region` VALUES ('3452', '三义乡', '3436', '711534', '3');
INSERT INTO `tao_region` VALUES ('3453', '西湖乡', '3436', '711535', '3');
INSERT INTO `tao_region` VALUES ('3454', '卓兰镇', '3436', '711536', '3');
INSERT INTO `tao_region` VALUES ('3455', '彰化县', '3215', '711700', '2');
INSERT INTO `tao_region` VALUES ('3456', '彰化市', '3455', '711727', '3');
INSERT INTO `tao_region` VALUES ('3457', '芬园乡', '3455', '711728', '3');
INSERT INTO `tao_region` VALUES ('3458', '花坛乡', '3455', '711729', '3');
INSERT INTO `tao_region` VALUES ('3459', '秀水乡', '3455', '711730', '3');
INSERT INTO `tao_region` VALUES ('3460', '鹿港镇', '3455', '711731', '3');
INSERT INTO `tao_region` VALUES ('3461', '福兴乡', '3455', '711732', '3');
INSERT INTO `tao_region` VALUES ('3462', '线西乡', '3455', '711733', '3');
INSERT INTO `tao_region` VALUES ('3463', '和美镇', '3455', '711734', '3');
INSERT INTO `tao_region` VALUES ('3464', '伸港乡', '3455', '711735', '3');
INSERT INTO `tao_region` VALUES ('3465', '员林市', '3455', '711736', '3');
INSERT INTO `tao_region` VALUES ('3466', '社头乡', '3455', '711737', '3');
INSERT INTO `tao_region` VALUES ('3467', '永靖乡', '3455', '711738', '3');
INSERT INTO `tao_region` VALUES ('3468', '埔心乡', '3455', '711739', '3');
INSERT INTO `tao_region` VALUES ('3469', '溪湖镇', '3455', '711740', '3');
INSERT INTO `tao_region` VALUES ('3470', '大村乡', '3455', '711741', '3');
INSERT INTO `tao_region` VALUES ('3471', '埔盐乡', '3455', '711742', '3');
INSERT INTO `tao_region` VALUES ('3472', '田中镇', '3455', '711743', '3');
INSERT INTO `tao_region` VALUES ('3473', '北斗镇', '3455', '711744', '3');
INSERT INTO `tao_region` VALUES ('3474', '田尾乡', '3455', '711745', '3');
INSERT INTO `tao_region` VALUES ('3475', '埤头乡', '3455', '711746', '3');
INSERT INTO `tao_region` VALUES ('3476', '溪州乡', '3455', '711747', '3');
INSERT INTO `tao_region` VALUES ('3477', '竹塘乡', '3455', '711748', '3');
INSERT INTO `tao_region` VALUES ('3478', '二林镇', '3455', '711749', '3');
INSERT INTO `tao_region` VALUES ('3479', '大城乡', '3455', '711750', '3');
INSERT INTO `tao_region` VALUES ('3480', '芳苑乡', '3455', '711751', '3');
INSERT INTO `tao_region` VALUES ('3481', '二水乡', '3455', '711752', '3');
INSERT INTO `tao_region` VALUES ('3482', '嘉义县', '3215', '711900', '2');
INSERT INTO `tao_region` VALUES ('3483', '番路乡', '3482', '711919', '3');
INSERT INTO `tao_region` VALUES ('3484', '梅山乡', '3482', '711920', '3');
INSERT INTO `tao_region` VALUES ('3485', '竹崎乡', '3482', '711921', '3');
INSERT INTO `tao_region` VALUES ('3486', '阿里山乡', '3482', '711922', '3');
INSERT INTO `tao_region` VALUES ('3487', '中埔乡', '3482', '711923', '3');
INSERT INTO `tao_region` VALUES ('3488', '大埔乡', '3482', '711924', '3');
INSERT INTO `tao_region` VALUES ('3489', '水上乡', '3482', '711925', '3');
INSERT INTO `tao_region` VALUES ('3490', '鹿草乡', '3482', '711926', '3');
INSERT INTO `tao_region` VALUES ('3491', '太保市', '3482', '711927', '3');
INSERT INTO `tao_region` VALUES ('3492', '朴子市', '3482', '711928', '3');
INSERT INTO `tao_region` VALUES ('3493', '东石乡', '3482', '711929', '3');
INSERT INTO `tao_region` VALUES ('3494', '六脚乡', '3482', '711930', '3');
INSERT INTO `tao_region` VALUES ('3495', '新港乡', '3482', '711931', '3');
INSERT INTO `tao_region` VALUES ('3496', '民雄乡', '3482', '711932', '3');
INSERT INTO `tao_region` VALUES ('3497', '大林镇', '3482', '711933', '3');
INSERT INTO `tao_region` VALUES ('3498', '溪口乡', '3482', '711934', '3');
INSERT INTO `tao_region` VALUES ('3499', '义竹乡', '3482', '711935', '3');
INSERT INTO `tao_region` VALUES ('3500', '布袋镇', '3482', '711936', '3');
INSERT INTO `tao_region` VALUES ('3501', '云林县', '3215', '712100', '2');
INSERT INTO `tao_region` VALUES ('3502', '斗南镇', '3501', '712121', '3');
INSERT INTO `tao_region` VALUES ('3503', '大埤乡', '3501', '712122', '3');
INSERT INTO `tao_region` VALUES ('3504', '虎尾镇', '3501', '712123', '3');
INSERT INTO `tao_region` VALUES ('3505', '土库镇', '3501', '712124', '3');
INSERT INTO `tao_region` VALUES ('3506', '褒忠乡', '3501', '712125', '3');
INSERT INTO `tao_region` VALUES ('3507', '东势乡', '3501', '712126', '3');
INSERT INTO `tao_region` VALUES ('3508', '台西乡', '3501', '712127', '3');
INSERT INTO `tao_region` VALUES ('3509', '仑背乡', '3501', '712128', '3');
INSERT INTO `tao_region` VALUES ('3510', '麦寮乡', '3501', '712129', '3');
INSERT INTO `tao_region` VALUES ('3511', '斗六市', '3501', '712130', '3');
INSERT INTO `tao_region` VALUES ('3512', '林内乡', '3501', '712131', '3');
INSERT INTO `tao_region` VALUES ('3513', '古坑乡', '3501', '712132', '3');
INSERT INTO `tao_region` VALUES ('3514', '莿桐乡', '3501', '712133', '3');
INSERT INTO `tao_region` VALUES ('3515', '西螺镇', '3501', '712134', '3');
INSERT INTO `tao_region` VALUES ('3516', '二仑乡', '3501', '712135', '3');
INSERT INTO `tao_region` VALUES ('3517', '北港镇', '3501', '712136', '3');
INSERT INTO `tao_region` VALUES ('3518', '水林乡', '3501', '712137', '3');
INSERT INTO `tao_region` VALUES ('3519', '口湖乡', '3501', '712138', '3');
INSERT INTO `tao_region` VALUES ('3520', '四湖乡', '3501', '712139', '3');
INSERT INTO `tao_region` VALUES ('3521', '元长乡', '3501', '712140', '3');
INSERT INTO `tao_region` VALUES ('3522', '屏东县', '3215', '712400', '2');
INSERT INTO `tao_region` VALUES ('3523', '屏东市', '3522', '712434', '3');
INSERT INTO `tao_region` VALUES ('3524', '三地门乡', '3522', '712435', '3');
INSERT INTO `tao_region` VALUES ('3525', '雾台乡', '3522', '712436', '3');
INSERT INTO `tao_region` VALUES ('3526', '玛家乡', '3522', '712437', '3');
INSERT INTO `tao_region` VALUES ('3527', '九如乡', '3522', '712438', '3');
INSERT INTO `tao_region` VALUES ('3528', '里港乡', '3522', '712439', '3');
INSERT INTO `tao_region` VALUES ('3529', '高树乡', '3522', '712440', '3');
INSERT INTO `tao_region` VALUES ('3530', '盐埔乡', '3522', '712441', '3');
INSERT INTO `tao_region` VALUES ('3531', '长治乡', '3522', '712442', '3');
INSERT INTO `tao_region` VALUES ('3532', '麟洛乡', '3522', '712443', '3');
INSERT INTO `tao_region` VALUES ('3533', '竹田乡', '3522', '712444', '3');
INSERT INTO `tao_region` VALUES ('3534', '内埔乡', '3522', '712445', '3');
INSERT INTO `tao_region` VALUES ('3535', '万丹乡', '3522', '712446', '3');
INSERT INTO `tao_region` VALUES ('3536', '潮州镇', '3522', '712447', '3');
INSERT INTO `tao_region` VALUES ('3537', '泰武乡', '3522', '712448', '3');
INSERT INTO `tao_region` VALUES ('3538', '来义乡', '3522', '712449', '3');
INSERT INTO `tao_region` VALUES ('3539', '万峦乡', '3522', '712450', '3');
INSERT INTO `tao_region` VALUES ('3540', '崁顶乡', '3522', '712451', '3');
INSERT INTO `tao_region` VALUES ('3541', '新埤乡', '3522', '712452', '3');
INSERT INTO `tao_region` VALUES ('3542', '南州乡', '3522', '712453', '3');
INSERT INTO `tao_region` VALUES ('3543', '林边乡', '3522', '712454', '3');
INSERT INTO `tao_region` VALUES ('3544', '东港镇', '3522', '712455', '3');
INSERT INTO `tao_region` VALUES ('3545', '琉球乡', '3522', '712456', '3');
INSERT INTO `tao_region` VALUES ('3546', '佳冬乡', '3522', '712457', '3');
INSERT INTO `tao_region` VALUES ('3547', '新园乡', '3522', '712458', '3');
INSERT INTO `tao_region` VALUES ('3548', '枋寮乡', '3522', '712459', '3');
INSERT INTO `tao_region` VALUES ('3549', '枋山乡', '3522', '712460', '3');
INSERT INTO `tao_region` VALUES ('3550', '春日乡', '3522', '712461', '3');
INSERT INTO `tao_region` VALUES ('3551', '狮子乡', '3522', '712462', '3');
INSERT INTO `tao_region` VALUES ('3552', '车城乡', '3522', '712463', '3');
INSERT INTO `tao_region` VALUES ('3553', '牡丹乡', '3522', '712464', '3');
INSERT INTO `tao_region` VALUES ('3554', '恒春镇', '3522', '712465', '3');
INSERT INTO `tao_region` VALUES ('3555', '满州乡', '3522', '712466', '3');
INSERT INTO `tao_region` VALUES ('3556', '台东县', '3215', '712500', '2');
INSERT INTO `tao_region` VALUES ('3557', '台东市', '3556', '712517', '3');
INSERT INTO `tao_region` VALUES ('3558', '绿岛乡', '3556', '712518', '3');
INSERT INTO `tao_region` VALUES ('3559', '兰屿乡', '3556', '712519', '3');
INSERT INTO `tao_region` VALUES ('3560', '延平乡', '3556', '712520', '3');
INSERT INTO `tao_region` VALUES ('3561', '卑南乡', '3556', '712521', '3');
INSERT INTO `tao_region` VALUES ('3562', '鹿野乡', '3556', '712522', '3');
INSERT INTO `tao_region` VALUES ('3563', '关山镇', '3556', '712523', '3');
INSERT INTO `tao_region` VALUES ('3564', '海端乡', '3556', '712524', '3');
INSERT INTO `tao_region` VALUES ('3565', '池上乡', '3556', '712525', '3');
INSERT INTO `tao_region` VALUES ('3566', '东河乡', '3556', '712526', '3');
INSERT INTO `tao_region` VALUES ('3567', '成功镇', '3556', '712527', '3');
INSERT INTO `tao_region` VALUES ('3568', '长滨乡', '3556', '712528', '3');
INSERT INTO `tao_region` VALUES ('3569', '金峰乡', '3556', '712529', '3');
INSERT INTO `tao_region` VALUES ('3570', '大武乡', '3556', '712530', '3');
INSERT INTO `tao_region` VALUES ('3571', '达仁乡', '3556', '712531', '3');
INSERT INTO `tao_region` VALUES ('3572', '太麻里乡', '3556', '712532', '3');
INSERT INTO `tao_region` VALUES ('3573', '花莲县', '3215', '712600', '2');
INSERT INTO `tao_region` VALUES ('3574', '花莲市', '3573', '712615', '3');
INSERT INTO `tao_region` VALUES ('3575', '新城乡', '3573', '712616', '3');
INSERT INTO `tao_region` VALUES ('3576', '秀林乡', '3573', '712618', '3');
INSERT INTO `tao_region` VALUES ('3577', '吉安乡', '3573', '712619', '3');
INSERT INTO `tao_region` VALUES ('3578', '寿丰乡', '3573', '712620', '3');
INSERT INTO `tao_region` VALUES ('3579', '凤林镇', '3573', '712621', '3');
INSERT INTO `tao_region` VALUES ('3580', '光复乡', '3573', '712622', '3');
INSERT INTO `tao_region` VALUES ('3581', '丰滨乡', '3573', '712623', '3');
INSERT INTO `tao_region` VALUES ('3582', '瑞穗乡', '3573', '712624', '3');
INSERT INTO `tao_region` VALUES ('3583', '万荣乡', '3573', '712625', '3');
INSERT INTO `tao_region` VALUES ('3584', '玉里镇', '3573', '712626', '3');
INSERT INTO `tao_region` VALUES ('3585', '卓溪乡', '3573', '712627', '3');
INSERT INTO `tao_region` VALUES ('3586', '富里乡', '3573', '712628', '3');
INSERT INTO `tao_region` VALUES ('3587', '澎湖县', '3215', '712700', '2');
INSERT INTO `tao_region` VALUES ('3588', '马公市', '3587', '712707', '3');
INSERT INTO `tao_region` VALUES ('3589', '西屿乡', '3587', '712708', '3');
INSERT INTO `tao_region` VALUES ('3590', '望安乡', '3587', '712709', '3');
INSERT INTO `tao_region` VALUES ('3591', '七美乡', '3587', '712710', '3');
INSERT INTO `tao_region` VALUES ('3592', '白沙乡', '3587', '712711', '3');
INSERT INTO `tao_region` VALUES ('3593', '湖西乡', '3587', '712712', '3');
INSERT INTO `tao_region` VALUES ('3594', '香港特别行政区', '0', '810000', '1');
INSERT INTO `tao_region` VALUES ('3595', '香港特别行政区', '3594', '810100', '2');
INSERT INTO `tao_region` VALUES ('3596', '中西区', '3595', '810101', '3');
INSERT INTO `tao_region` VALUES ('3597', '东区', '3595', '810102', '3');
INSERT INTO `tao_region` VALUES ('3598', '九龙城区', '3595', '810103', '3');
INSERT INTO `tao_region` VALUES ('3599', '观塘区', '3595', '810104', '3');
INSERT INTO `tao_region` VALUES ('3600', '南区', '3595', '810105', '3');
INSERT INTO `tao_region` VALUES ('3601', '深水埗区', '3595', '810106', '3');
INSERT INTO `tao_region` VALUES ('3602', '湾仔区', '3595', '810107', '3');
INSERT INTO `tao_region` VALUES ('3603', '黄大仙区', '3595', '810108', '3');
INSERT INTO `tao_region` VALUES ('3604', '油尖旺区', '3595', '810109', '3');
INSERT INTO `tao_region` VALUES ('3605', '离岛区', '3595', '810110', '3');
INSERT INTO `tao_region` VALUES ('3606', '葵青区', '3595', '810111', '3');
INSERT INTO `tao_region` VALUES ('3607', '北区', '3595', '810112', '3');
INSERT INTO `tao_region` VALUES ('3608', '西贡区', '3595', '810113', '3');
INSERT INTO `tao_region` VALUES ('3609', '沙田区', '3595', '810114', '3');
INSERT INTO `tao_region` VALUES ('3610', '屯门区', '3595', '810115', '3');
INSERT INTO `tao_region` VALUES ('3611', '大埔区', '3595', '810116', '3');
INSERT INTO `tao_region` VALUES ('3612', '荃湾区', '3595', '810117', '3');
INSERT INTO `tao_region` VALUES ('3613', '元朗区', '3595', '810118', '3');
INSERT INTO `tao_region` VALUES ('3614', '澳门特别行政区', '0', '820000', '1');
INSERT INTO `tao_region` VALUES ('3615', '澳门特别行政区', '3614', '820100', '2');
INSERT INTO `tao_region` VALUES ('3616', '澳门半岛', '3615', '820101', '3');
INSERT INTO `tao_region` VALUES ('3617', '凼仔', '3615', '820102', '3');
INSERT INTO `tao_region` VALUES ('3618', '路凼城', '3615', '820103', '3');
INSERT INTO `tao_region` VALUES ('3619', '路环', '3615', '820104', '3');
INSERT INTO `tao_region` VALUES ('3621', '南城街道', '2051', '441900', '3');
INSERT INTO `tao_region` VALUES ('3622', '万江街道', '2051', '441900', '3');
INSERT INTO `tao_region` VALUES ('3623', '莞城街道', '2051', '441900', '3');
INSERT INTO `tao_region` VALUES ('3624', '石碣镇', '2051', '441900', '3');
INSERT INTO `tao_region` VALUES ('3625', '石龙镇', '2051', '441900', '3');
INSERT INTO `tao_region` VALUES ('3626', '茶山镇', '2051', '441900', '3');
INSERT INTO `tao_region` VALUES ('3627', '石排镇', '2051', '441900', '3');
INSERT INTO `tao_region` VALUES ('3628', '企石镇', '2051', '441900', '3');
INSERT INTO `tao_region` VALUES ('3629', '横沥镇', '2051', '441900', '3');
INSERT INTO `tao_region` VALUES ('3630', '桥头镇', '2051', '441900', '3');
INSERT INTO `tao_region` VALUES ('3631', '谢岗镇', '2051', '441900', '3');
INSERT INTO `tao_region` VALUES ('3632', '东坑镇', '2051', '441900', '3');
INSERT INTO `tao_region` VALUES ('3633', '常平镇', '2051', '441900', '3');
INSERT INTO `tao_region` VALUES ('3634', '寮步镇', '2051', '441900', '3');
INSERT INTO `tao_region` VALUES ('3635', '樟木头镇', '2051', '441900', '3');
INSERT INTO `tao_region` VALUES ('3636', '大朗镇', '2051', '441900', '3');
INSERT INTO `tao_region` VALUES ('3637', '黄江镇', '2051', '441900', '3');
INSERT INTO `tao_region` VALUES ('3638', '清溪镇', '2051', '441900', '3');
INSERT INTO `tao_region` VALUES ('3639', '塘厦镇', '2051', '441900', '3');
INSERT INTO `tao_region` VALUES ('3640', '凤岗镇', '2051', '441900', '3');
INSERT INTO `tao_region` VALUES ('3641', '大岭山镇', '2051', '441900', '3');
INSERT INTO `tao_region` VALUES ('3642', '长安镇', '2051', '441900', '3');
INSERT INTO `tao_region` VALUES ('3643', '虎门镇', '2051', '441900', '3');
INSERT INTO `tao_region` VALUES ('3644', '厚街镇', '2051', '441900', '3');
INSERT INTO `tao_region` VALUES ('3645', '沙田镇', '2051', '441900', '3');
INSERT INTO `tao_region` VALUES ('3646', '道滘镇', '2051', '441900', '3');
INSERT INTO `tao_region` VALUES ('3647', '洪梅镇', '2051', '441900', '3');
INSERT INTO `tao_region` VALUES ('3648', '麻涌镇', '2051', '441900', '3');
INSERT INTO `tao_region` VALUES ('3649', '望牛墩镇', '2051', '441900', '3');
INSERT INTO `tao_region` VALUES ('3650', '中堂镇', '2051', '441900', '3');
INSERT INTO `tao_region` VALUES ('3651', '高埗镇', '2051', '441900', '3');
INSERT INTO `tao_region` VALUES ('3652', '松山湖', '2051', '441900', '3');
INSERT INTO `tao_region` VALUES ('3653', '东莞港', '2051', '441900', '3');
INSERT INTO `tao_region` VALUES ('3654', '东莞生态园', '2051', '441900', '3');
INSERT INTO `tao_region` VALUES ('3656', '东区街道', '2052', '442000', '3');
INSERT INTO `tao_region` VALUES ('3657', '中山港街道', '2052', '442000', '3');
INSERT INTO `tao_region` VALUES ('3658', '西区街道', '2052', '442000', '3');
INSERT INTO `tao_region` VALUES ('3659', '南区街道', '2052', '442000', '3');
INSERT INTO `tao_region` VALUES ('3660', '五桂山街道', '2052', '442000', '3');
INSERT INTO `tao_region` VALUES ('3661', '小榄镇', '2052', '442000', '3');
INSERT INTO `tao_region` VALUES ('3662', '黄圃镇', '2052', '442000', '3');
INSERT INTO `tao_region` VALUES ('3664', '东凤镇', '2052', '442000', '3');
INSERT INTO `tao_region` VALUES ('3666', '古镇镇', '2052', '442000', '3');
INSERT INTO `tao_region` VALUES ('3667', '沙溪镇', '2052', '442000', '3');
INSERT INTO `tao_region` VALUES ('3668', '坦洲镇', '2052', '442000', '3');
INSERT INTO `tao_region` VALUES ('3669', '港口镇', '2052', '442000', '3');
INSERT INTO `tao_region` VALUES ('3670', '三角镇', '2052', '442000', '3');
INSERT INTO `tao_region` VALUES ('3671', '横栏镇', '2052', '442000', '3');
INSERT INTO `tao_region` VALUES ('3672', '南头镇', '2052', '442000', '3');
INSERT INTO `tao_region` VALUES ('3673', '阜沙镇', '2052', '442000', '3');
INSERT INTO `tao_region` VALUES ('3675', '三乡镇', '2052', '442000', '3');
INSERT INTO `tao_region` VALUES ('3676', '板芙镇', '2052', '442000', '3');
INSERT INTO `tao_region` VALUES ('3677', '大涌镇', '2052', '442000', '3');
INSERT INTO `tao_region` VALUES ('3678', '神湾镇', '2052', '442000', '3');
INSERT INTO `tao_region` VALUES ('3680', '南沙群岛', '2206', '460322', '3');
INSERT INTO `tao_region` VALUES ('3681', '中沙群岛的岛礁及其海域', '2206', '460323', '3');
INSERT INTO `tao_region` VALUES ('3683', '和庆镇', '2207', '460400', '3');
INSERT INTO `tao_region` VALUES ('3684', '南丰镇', '2207', '460400', '3');
INSERT INTO `tao_region` VALUES ('3685', '大成镇', '2207', '460400', '3');
INSERT INTO `tao_region` VALUES ('3686', '雅星镇', '2207', '460400', '3');
INSERT INTO `tao_region` VALUES ('3687', '兰洋镇', '2207', '460400', '3');
INSERT INTO `tao_region` VALUES ('3688', '光村镇', '2207', '460400', '3');
INSERT INTO `tao_region` VALUES ('3689', '木棠镇', '2207', '460400', '3');
INSERT INTO `tao_region` VALUES ('3690', '海头镇', '2207', '460400', '3');
INSERT INTO `tao_region` VALUES ('3691', '峨蔓镇', '2207', '460400', '3');
INSERT INTO `tao_region` VALUES ('3692', '王五镇', '2207', '460400', '3');
INSERT INTO `tao_region` VALUES ('3693', '白马井镇', '2207', '460400', '3');
INSERT INTO `tao_region` VALUES ('3694', '中和镇', '2207', '460400', '3');
INSERT INTO `tao_region` VALUES ('3695', '排浦镇', '2207', '460400', '3');
INSERT INTO `tao_region` VALUES ('3696', '东成镇', '2207', '460400', '3');
INSERT INTO `tao_region` VALUES ('3697', '新州镇', '2207', '460400', '3');
INSERT INTO `tao_region` VALUES ('3698', '洋浦经济开发区', '2207', '460400', '3');
INSERT INTO `tao_region` VALUES ('3699', '华南热作学院', '2207', '460400', '3');
INSERT INTO `tao_region` VALUES ('3706', '省直辖县级行政区划', '1495', '419000', '2');
INSERT INTO `tao_region` VALUES ('3707', '省直辖县级行政区划', '1671', '429000', '2');
INSERT INTO `tao_region` VALUES ('3708', '省直辖县级行政区划', '2195', '469000', '2');
INSERT INTO `tao_region` VALUES ('3709', '县', '2223', '500200', '2');
INSERT INTO `tao_region` VALUES ('3710', '自治区直辖县级行政区划', '3094', '659000', '2');
INSERT INTO `tao_region` VALUES ('3711', '石家庄高新技术产业开发区', '38', '130171', '3');
INSERT INTO `tao_region` VALUES ('3712', '石家庄循环化工园区', '38', '130172', '3');
INSERT INTO `tao_region` VALUES ('3713', '河北唐山芦台经济开发区', '61', '130271', '3');
INSERT INTO `tao_region` VALUES ('3714', '唐山市汉沽管理区', '61', '130272', '3');
INSERT INTO `tao_region` VALUES ('3715', '唐山高新技术产业开发区', '61', '130273', '3');
INSERT INTO `tao_region` VALUES ('3716', '河北唐山海港经济开发区', '61', '130274', '3');
INSERT INTO `tao_region` VALUES ('3717', '秦皇岛市经济技术开发区', '76', '130371', '3');
INSERT INTO `tao_region` VALUES ('3718', '北戴河新区', '76', '130372', '3');
INSERT INTO `tao_region` VALUES ('3719', '邯郸经济技术开发区', '84', '130471', '3');
INSERT INTO `tao_region` VALUES ('3720', '邯郸冀南新区', '84', '130473', '3');
INSERT INTO `tao_region` VALUES ('3721', '襄都区', '103', '130502', '3');
INSERT INTO `tao_region` VALUES ('3722', '信都区', '103', '130503', '3');
INSERT INTO `tao_region` VALUES ('3723', '任泽区', '103', '130505', '3');
INSERT INTO `tao_region` VALUES ('3724', '南和区', '103', '130506', '3');
INSERT INTO `tao_region` VALUES ('3725', '河北邢台经济开发区', '103', '130571', '3');
INSERT INTO `tao_region` VALUES ('3726', '保定高新技术产业开发区', '123', '130671', '3');
INSERT INTO `tao_region` VALUES ('3727', '保定白沟新城', '123', '130672', '3');
INSERT INTO `tao_region` VALUES ('3728', '张家口经济开发区', '148', '130771', '3');
INSERT INTO `tao_region` VALUES ('3729', '张家口市察北管理区', '148', '130772', '3');
INSERT INTO `tao_region` VALUES ('3730', '张家口市塞北管理区', '148', '130773', '3');
INSERT INTO `tao_region` VALUES ('3731', '承德高新技术产业开发区', '165', '130871', '3');
INSERT INTO `tao_region` VALUES ('3732', '河北沧州经济开发区', '177', '130971', '3');
INSERT INTO `tao_region` VALUES ('3733', '沧州高新技术产业开发区', '177', '130972', '3');
INSERT INTO `tao_region` VALUES ('3734', '沧州渤海新区', '177', '130973', '3');
INSERT INTO `tao_region` VALUES ('3735', '廊坊经济技术开发区', '194', '131071', '3');
INSERT INTO `tao_region` VALUES ('3736', '河北衡水高新技术产业开发区', '205', '131171', '3');
INSERT INTO `tao_region` VALUES ('3737', '衡水滨湖新区', '205', '131172', '3');
INSERT INTO `tao_region` VALUES ('3738', '山西转型综合改革示范区', '218', '140171', '3');
INSERT INTO `tao_region` VALUES ('3739', '山西大同经济开发区', '229', '140271', '3');
INSERT INTO `tao_region` VALUES ('3740', '山西长治高新技术产业园区', '246', '140471', '3');
INSERT INTO `tao_region` VALUES ('3741', '山西朔州经济开发区', '266', '140671', '3');
INSERT INTO `tao_region` VALUES ('3742', '五台山风景名胜区', '299', '140971', '3');
INSERT INTO `tao_region` VALUES ('3743', '呼和浩特经济技术开发区', '347', '150172', '3');
INSERT INTO `tao_region` VALUES ('3744', '包头稀土高新技术产业开发区', '357', '150271', '3');
INSERT INTO `tao_region` VALUES ('3745', '通辽经济技术开发区', '384', '150571', '3');
INSERT INTO `tao_region` VALUES ('3746', '乌拉盖管委会', '445', '152571', '3');
INSERT INTO `tao_region` VALUES ('3747', '内蒙古阿拉善高新技术产业开发区', '458', '152971', '3');
INSERT INTO `tao_region` VALUES ('3748', '长春经济技术开发区', '578', '220171', '3');
INSERT INTO `tao_region` VALUES ('3749', '长春净月高新技术产业开发区', '578', '220172', '3');
INSERT INTO `tao_region` VALUES ('3750', '长春高新技术产业开发区', '578', '220173', '3');
INSERT INTO `tao_region` VALUES ('3751', '长春汽车经济技术开发区', '578', '220174', '3');
INSERT INTO `tao_region` VALUES ('3752', '公主岭市', '578', '220184', '3');
INSERT INTO `tao_region` VALUES ('3753', '吉林经济开发区', '589', '220271', '3');
INSERT INTO `tao_region` VALUES ('3754', '吉林高新技术产业开发区', '589', '220272', '3');
INSERT INTO `tao_region` VALUES ('3755', '吉林中国新加坡食品区', '589', '220273', '3');
INSERT INTO `tao_region` VALUES ('3756', '吉林松原经济开发区', '626', '220771', '3');
INSERT INTO `tao_region` VALUES ('3757', '吉林白城经济开发区', '632', '220871', '3');
INSERT INTO `tao_region` VALUES ('3758', '大庆高新技术产业开发区', '712', '230671', '3');
INSERT INTO `tao_region` VALUES ('3759', '牡丹江经济技术开发区', '749', '231071', '3');
INSERT INTO `tao_region` VALUES ('3760', '加格达奇区', '778', '232761', '3');
INSERT INTO `tao_region` VALUES ('3761', '松岭区', '778', '232762', '3');
INSERT INTO `tao_region` VALUES ('3762', '新林区', '778', '232763', '3');
INSERT INTO `tao_region` VALUES ('3763', '呼中区', '778', '232764', '3');
INSERT INTO `tao_region` VALUES ('3764', '徐州经济技术开发区', '821', '320371', '3');
INSERT INTO `tao_region` VALUES ('3765', '苏州工业园区', '839', '320571', '3');
INSERT INTO `tao_region` VALUES ('3766', '崇川区', '849', '320613', '3');
INSERT INTO `tao_region` VALUES ('3767', '海门区', '849', '320614', '3');
INSERT INTO `tao_region` VALUES ('3768', '南通经济技术开发区', '849', '320671', '3');
INSERT INTO `tao_region` VALUES ('3769', '连云港经济技术开发区', '858', '320771', '3');
INSERT INTO `tao_region` VALUES ('3770', '连云港高新技术产业开发区', '858', '320772', '3');
INSERT INTO `tao_region` VALUES ('3771', '淮安经济技术开发区', '865', '320871', '3');
INSERT INTO `tao_region` VALUES ('3772', '盐城经济技术开发区', '873', '320971', '3');
INSERT INTO `tao_region` VALUES ('3773', '扬州经济技术开发区', '883', '321071', '3');
INSERT INTO `tao_region` VALUES ('3774', '镇江新区', '890', '321171', '3');
INSERT INTO `tao_region` VALUES ('3775', '泰州医药高新技术产业开发区', '897', '321271', '3');
INSERT INTO `tao_region` VALUES ('3776', '宿迁经济技术开发区', '904', '321371', '3');
INSERT INTO `tao_region` VALUES ('3777', '临平区', '911', '330113', '3');
INSERT INTO `tao_region` VALUES ('3778', '钱塘区', '911', '330114', '3');
INSERT INTO `tao_region` VALUES ('3779', '合肥高新技术产业开发区', '1013', '340171', '3');
INSERT INTO `tao_region` VALUES ('3780', '合肥经济技术开发区', '1013', '340172', '3');
INSERT INTO `tao_region` VALUES ('3781', '合肥新站高新技术产业开发区', '1013', '340173', '3');
INSERT INTO `tao_region` VALUES ('3782', '弋江区', '1023', '340209', '3');
INSERT INTO `tao_region` VALUES ('3783', '湾沚区', '1023', '340210', '3');
INSERT INTO `tao_region` VALUES ('3784', '繁昌区', '1023', '340212', '3');
INSERT INTO `tao_region` VALUES ('3785', '芜湖经济技术开发区', '1023', '340271', '3');
INSERT INTO `tao_region` VALUES ('3786', '安徽芜湖三山经济开发区', '1023', '340272', '3');
INSERT INTO `tao_region` VALUES ('3787', '蚌埠市高新技术开发区', '1032', '340371', '3');
INSERT INTO `tao_region` VALUES ('3788', '蚌埠市经济开发区', '1032', '340372', '3');
INSERT INTO `tao_region` VALUES ('3789', '安徽安庆经济开发区', '1065', '340871', '3');
INSERT INTO `tao_region` VALUES ('3790', '中新苏滁高新技术产业开发区', '1084', '341171', '3');
INSERT INTO `tao_region` VALUES ('3791', '滁州经济技术开发区', '1084', '341172', '3');
INSERT INTO `tao_region` VALUES ('3792', '阜阳合肥现代产业园区', '1093', '341271', '3');
INSERT INTO `tao_region` VALUES ('3793', '阜阳经济技术开发区', '1093', '341272', '3');
INSERT INTO `tao_region` VALUES ('3794', '宿州马鞍山现代产业园区', '1102', '341371', '3');
INSERT INTO `tao_region` VALUES ('3795', '宿州经济技术开发区', '1102', '341372', '3');
INSERT INTO `tao_region` VALUES ('3796', '宣城市经济开发区', '1126', '341871', '3');
INSERT INTO `tao_region` VALUES ('3797', '三元区', '1162', '350404', '3');
INSERT INTO `tao_region` VALUES ('3798', '沙县区', '1162', '350405', '3');
INSERT INTO `tao_region` VALUES ('3799', '龙海区', '1188', '350604', '3');
INSERT INTO `tao_region` VALUES ('3800', '长泰区', '1188', '350605', '3');
INSERT INTO `tao_region` VALUES ('3801', '龙南市', '1272', '360783', '3');
INSERT INTO `tao_region` VALUES ('3802', '济南高新技术产业开发区', '1342', '370171', '3');
INSERT INTO `tao_region` VALUES ('3803', '青岛高新技术产业开发区', '1355', '370271', '3');
INSERT INTO `tao_region` VALUES ('3804', '东营经济技术开发区', '1382', '370571', '3');
INSERT INTO `tao_region` VALUES ('3805', '东营港经济开发区', '1382', '370572', '3');
INSERT INTO `tao_region` VALUES ('3806', '蓬莱区', '1388', '370614', '3');
INSERT INTO `tao_region` VALUES ('3807', '烟台高新技术产业开发区', '1388', '370671', '3');
INSERT INTO `tao_region` VALUES ('3808', '烟台经济技术开发区', '1388', '370672', '3');
INSERT INTO `tao_region` VALUES ('3809', '潍坊滨海经济技术开发区', '1401', '370772', '3');
INSERT INTO `tao_region` VALUES ('3810', '济宁高新技术产业开发区', '1414', '370871', '3');
INSERT INTO `tao_region` VALUES ('3811', '威海火炬高技术产业开发区', '1433', '371071', '3');
INSERT INTO `tao_region` VALUES ('3812', '威海经济技术开发区', '1433', '371072', '3');
INSERT INTO `tao_region` VALUES ('3813', '威海临港经济技术开发区', '1433', '371073', '3');
INSERT INTO `tao_region` VALUES ('3814', '日照经济技术开发区', '1438', '371171', '3');
INSERT INTO `tao_region` VALUES ('3815', '临沂高新技术产业开发区', '1443', '371371', '3');
INSERT INTO `tao_region` VALUES ('3816', '德州天衢新区', '1456', '371471', '3');
INSERT INTO `tao_region` VALUES ('3817', '菏泽经济技术开发区', '1485', '371771', '3');
INSERT INTO `tao_region` VALUES ('3818', '菏泽高新技术开发区', '1485', '371772', '3');
INSERT INTO `tao_region` VALUES ('3819', '郑州经济技术开发区', '1496', '410171', '3');
INSERT INTO `tao_region` VALUES ('3820', '郑州高新技术产业开发区', '1496', '410172', '3');
INSERT INTO `tao_region` VALUES ('3821', '郑州航空港经济综合实验区', '1496', '410173', '3');
INSERT INTO `tao_region` VALUES ('3822', '偃师区', '1519', '410307', '3');
INSERT INTO `tao_region` VALUES ('3823', '孟津区', '1519', '410308', '3');
INSERT INTO `tao_region` VALUES ('3824', '洛阳高新技术产业开发区', '1519', '410371', '3');
INSERT INTO `tao_region` VALUES ('3825', '平顶山高新技术产业开发区', '1535', '410471', '3');
INSERT INTO `tao_region` VALUES ('3826', '平顶山市城乡一体化示范区', '1535', '410472', '3');
INSERT INTO `tao_region` VALUES ('3827', '安阳高新技术产业开发区', '1546', '410571', '3');
INSERT INTO `tao_region` VALUES ('3828', '鹤壁经济技术开发区', '1556', '410671', '3');
INSERT INTO `tao_region` VALUES ('3829', '新乡高新技术产业开发区', '1562', '410771', '3');
INSERT INTO `tao_region` VALUES ('3830', '新乡经济技术开发区', '1562', '410772', '3');
INSERT INTO `tao_region` VALUES ('3831', '新乡市平原城乡一体化示范区', '1562', '410773', '3');
INSERT INTO `tao_region` VALUES ('3832', '焦作城乡一体化示范区', '1575', '410871', '3');
INSERT INTO `tao_region` VALUES ('3833', '河南濮阳工业园区', '1586', '410971', '3');
INSERT INTO `tao_region` VALUES ('3834', '濮阳经济技术开发区', '1586', '410972', '3');
INSERT INTO `tao_region` VALUES ('3835', '许昌经济技术开发区', '1593', '411071', '3');
INSERT INTO `tao_region` VALUES ('3836', '漯河经济技术开发区', '1600', '411171', '3');
INSERT INTO `tao_region` VALUES ('3837', '河南三门峡经济开发区', '1606', '411271', '3');
INSERT INTO `tao_region` VALUES ('3838', '南阳高新技术产业开发区', '1613', '411371', '3');
INSERT INTO `tao_region` VALUES ('3839', '南阳市城乡一体化示范区', '1613', '411372', '3');
INSERT INTO `tao_region` VALUES ('3840', '豫东综合物流产业聚集区', '1627', '411471', '3');
INSERT INTO `tao_region` VALUES ('3841', '河南商丘经济开发区', '1627', '411472', '3');
INSERT INTO `tao_region` VALUES ('3842', '信阳高新技术产业开发区', '1637', '411571', '3');
INSERT INTO `tao_region` VALUES ('3843', '河南周口经济开发区', '1648', '411671', '3');
INSERT INTO `tao_region` VALUES ('3844', '河南驻马店经济开发区', '1659', '411771', '3');
INSERT INTO `tao_region` VALUES ('3845', '济源市', '3706', '419001', '3');
INSERT INTO `tao_region` VALUES ('3846', '荆州经济技术开发区', '1744', '421071', '3');
INSERT INTO `tao_region` VALUES ('3847', '监利市', '1744', '421088', '3');
INSERT INTO `tao_region` VALUES ('3848', '龙感湖管理区', '1753', '421171', '3');
INSERT INTO `tao_region` VALUES ('3849', '仙桃市', '3707', '429004', '3');
INSERT INTO `tao_region` VALUES ('3850', '潜江市', '3707', '429005', '3');
INSERT INTO `tao_region` VALUES ('3851', '天门市', '3707', '429006', '3');
INSERT INTO `tao_region` VALUES ('3852', '神农架林区', '3707', '429021', '3');
INSERT INTO `tao_region` VALUES ('3853', '湖南湘潭高新技术产业园区', '1809', '430371', '3');
INSERT INTO `tao_region` VALUES ('3854', '湘潭昭山示范区', '1809', '430372', '3');
INSERT INTO `tao_region` VALUES ('3855', '湘潭九华示范区', '1809', '430373', '3');
INSERT INTO `tao_region` VALUES ('3856', '衡阳综合保税区', '1815', '430471', '3');
INSERT INTO `tao_region` VALUES ('3857', '湖南衡阳高新技术产业园区', '1815', '430472', '3');
INSERT INTO `tao_region` VALUES ('3858', '湖南衡阳松木经济开发区', '1815', '430473', '3');
INSERT INTO `tao_region` VALUES ('3859', '岳阳市屈原管理区', '1841', '430671', '3');
INSERT INTO `tao_region` VALUES ('3860', '常德市西洞庭管理区', '1851', '430771', '3');
INSERT INTO `tao_region` VALUES ('3861', '益阳市大通湖管理区', '1866', '430971', '3');
INSERT INTO `tao_region` VALUES ('3862', '湖南益阳高新技术产业园区', '1866', '430972', '3');
INSERT INTO `tao_region` VALUES ('3863', '永州经济技术开发区', '1885', '431171', '3');
INSERT INTO `tao_region` VALUES ('3864', '永州市回龙圩管理区', '1885', '431173', '3');
INSERT INTO `tao_region` VALUES ('3865', '祁阳市', '1885', '431181', '3');
INSERT INTO `tao_region` VALUES ('3866', '怀化市洪江管理区', '1897', '431271', '3');
INSERT INTO `tao_region` VALUES ('3867', '东城街道', '2051', '441900', '3');
INSERT INTO `tao_region` VALUES ('3868', '东莞滨海湾新区', '2051', '441900', '3');
INSERT INTO `tao_region` VALUES ('3869', '石岐街道', '2052', '442000', '3');
INSERT INTO `tao_region` VALUES ('3870', '民众街道', '2052', '442000', '3');
INSERT INTO `tao_region` VALUES ('3871', '南朗街道', '2052', '442000', '3');
INSERT INTO `tao_region` VALUES ('3872', '横州市', '2070', '450181', '3');
INSERT INTO `tao_region` VALUES ('3873', '西沙群岛', '2206', '460321', '3');
INSERT INTO `tao_region` VALUES ('3874', '那大镇', '2207', '460400', '3');
INSERT INTO `tao_region` VALUES ('3875', '五指山市', '3708', '469001', '3');
INSERT INTO `tao_region` VALUES ('3876', '琼海市', '3708', '469002', '3');
INSERT INTO `tao_region` VALUES ('3877', '文昌市', '3708', '469005', '3');
INSERT INTO `tao_region` VALUES ('3878', '万宁市', '3708', '469006', '3');
INSERT INTO `tao_region` VALUES ('3879', '东方市', '3708', '469007', '3');
INSERT INTO `tao_region` VALUES ('3880', '定安县', '3708', '469021', '3');
INSERT INTO `tao_region` VALUES ('3881', '屯昌县', '3708', '469022', '3');
INSERT INTO `tao_region` VALUES ('3882', '澄迈县', '3708', '469023', '3');
INSERT INTO `tao_region` VALUES ('3883', '临高县', '3708', '469024', '3');
INSERT INTO `tao_region` VALUES ('3884', '白沙黎族自治县', '3708', '469025', '3');
INSERT INTO `tao_region` VALUES ('3885', '昌江黎族自治县', '3708', '469026', '3');
INSERT INTO `tao_region` VALUES ('3886', '乐东黎族自治县', '3708', '469027', '3');
INSERT INTO `tao_region` VALUES ('3887', '陵水黎族自治县', '3708', '469028', '3');
INSERT INTO `tao_region` VALUES ('3888', '保亭黎族苗族自治县', '3708', '469029', '3');
INSERT INTO `tao_region` VALUES ('3889', '琼中黎族苗族自治县', '3708', '469030', '3');
INSERT INTO `tao_region` VALUES ('3890', '城口县', '3709', '500229', '3');
INSERT INTO `tao_region` VALUES ('3891', '丰都县', '3709', '500230', '3');
INSERT INTO `tao_region` VALUES ('3892', '垫江县', '3709', '500231', '3');
INSERT INTO `tao_region` VALUES ('3893', '忠县', '3709', '500233', '3');
INSERT INTO `tao_region` VALUES ('3894', '云阳县', '3709', '500235', '3');
INSERT INTO `tao_region` VALUES ('3895', '奉节县', '3709', '500236', '3');
INSERT INTO `tao_region` VALUES ('3896', '巫山县', '3709', '500237', '3');
INSERT INTO `tao_region` VALUES ('3897', '巫溪县', '3709', '500238', '3');
INSERT INTO `tao_region` VALUES ('3898', '石柱土家族自治县', '3709', '500240', '3');
INSERT INTO `tao_region` VALUES ('3899', '秀山土家族苗族自治县', '3709', '500241', '3');
INSERT INTO `tao_region` VALUES ('3900', '酉阳土家族苗族自治县', '3709', '500242', '3');
INSERT INTO `tao_region` VALUES ('3901', '彭水苗族土家族自治县', '3709', '500243', '3');
INSERT INTO `tao_region` VALUES ('3902', '新津区', '2264', '510118', '3');
INSERT INTO `tao_region` VALUES ('3903', '会理市', '2450', '513402', '3');
INSERT INTO `tao_region` VALUES ('3904', '水城区', '2480', '520204', '3');
INSERT INTO `tao_region` VALUES ('3905', '黔西市', '2507', '520581', '3');
INSERT INTO `tao_region` VALUES ('3906', '禄丰市', '2646', '532302', '3');
INSERT INTO `tao_region` VALUES ('3907', '格尔木藏青工业园区', '2713', '540171', '3');
INSERT INTO `tao_region` VALUES ('3908', '拉萨经济技术开发区', '2713', '540172', '3');
INSERT INTO `tao_region` VALUES ('3909', '西藏文化旅游创意园区', '2713', '540173', '3');
INSERT INTO `tao_region` VALUES ('3910', '达孜工业园区', '2713', '540174', '3');
INSERT INTO `tao_region` VALUES ('3911', '凤翔区', '2814', '610305', '3');
INSERT INTO `tao_region` VALUES ('3912', '旬阳市', '2893', '610981', '3');
INSERT INTO `tao_region` VALUES ('3913', '兰州新区', '2913', '620171', '3');
INSERT INTO `tao_region` VALUES ('3914', '市辖区', '2922', '620201', '3');
INSERT INTO `tao_region` VALUES ('3915', '同仁市', '3034', '632301', '3');
INSERT INTO `tao_region` VALUES ('3916', '大柴旦行政委员会', '3059', '632857', '3');
INSERT INTO `tao_region` VALUES ('3917', '库尔勒经济技术开发区', '3130', '652871', '3');
INSERT INTO `tao_region` VALUES ('3918', '沙湾市', '3189', '654203', '3');
INSERT INTO `tao_region` VALUES ('3919', '石河子市', '3710', '659001', '3');
INSERT INTO `tao_region` VALUES ('3920', '阿拉尔市', '3710', '659002', '3');
INSERT INTO `tao_region` VALUES ('3921', '图木舒克市', '3710', '659003', '3');
INSERT INTO `tao_region` VALUES ('3922', '五家渠市', '3710', '659004', '3');
INSERT INTO `tao_region` VALUES ('3923', '北屯市', '3710', '659005', '3');
INSERT INTO `tao_region` VALUES ('3924', '铁门关市', '3710', '659006', '3');
INSERT INTO `tao_region` VALUES ('3925', '双河市', '3710', '659007', '3');
INSERT INTO `tao_region` VALUES ('3926', '可克达拉市', '3710', '659008', '3');
INSERT INTO `tao_region` VALUES ('3927', '昆玉市', '3710', '659009', '3');
INSERT INTO `tao_region` VALUES ('3928', '胡杨河市', '3710', '659010', '3');
INSERT INTO `tao_region` VALUES ('3929', '新星市', '3710', '659011', '3');

-- ----------------------------
-- Table structure for tao_signin_active
-- ----------------------------
DROP TABLE IF EXISTS `tao_signin_active`;
CREATE TABLE `tao_signin_active` (
  `active_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '活动ID',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '活动名称',
  `type` tinyint(3) unsigned NOT NULL DEFAULT '10' COMMENT '签到类型(10周期签到 20活动签到)',
  `start_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '开始时间',
  `end_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '结束时间',
  `total_days` int(11) unsigned NOT NULL DEFAULT '7' COMMENT '签到天数(周期签到用)',
  `user_scope` varchar(500) NOT NULL DEFAULT '' COMMENT '参与人群(json)',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '10' COMMENT '状态(10启用 20禁用)',
  `sort` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `store_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商城ID',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`active_id`),
  KEY `store_id` (`store_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COMMENT='签到活动表';

-- ----------------------------
-- Records of tao_signin_active
-- ----------------------------
INSERT INTO `tao_signin_active` VALUES ('1', '1212', '10', '1778601600', '1780156800', '7', '', '10', '0', '10001', '1778662897', '1778729478');

-- ----------------------------
-- Table structure for tao_signin_log
-- ----------------------------
DROP TABLE IF EXISTS `tao_signin_log`;
CREATE TABLE `tao_signin_log` (
  `log_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '记录ID',
  `active_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '活动ID',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `sign_date` varchar(20) NOT NULL DEFAULT '' COMMENT '签到日期',
  `consecutive_days` int(11) unsigned NOT NULL DEFAULT '1' COMMENT '连续签到天数',
  `reward_type` tinyint(3) unsigned NOT NULL DEFAULT '10' COMMENT '奖励类型',
  `reward_value` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '奖励数值',
  `store_id` int(11) unsigned NOT NULL DEFAULT '0',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`log_id`),
  UNIQUE KEY `uk_active_user_date` (`active_id`,`user_id`,`sign_date`),
  KEY `user_id` (`user_id`),
  KEY `store_id` (`store_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COMMENT='用户签到记录表';

-- ----------------------------
-- Records of tao_signin_log
-- ----------------------------
INSERT INTO `tao_signin_log` VALUES ('1', '1', '10004', '2026-05-14', '1', '10', '10', '10001', '1778730896');

-- ----------------------------
-- Table structure for tao_signin_reward
-- ----------------------------
DROP TABLE IF EXISTS `tao_signin_reward`;
CREATE TABLE `tao_signin_reward` (
  `reward_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '奖励ID',
  `active_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '活动ID',
  `day_num` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '第几天(周期签到用)',
  `sign_date` varchar(20) NOT NULL DEFAULT '' COMMENT '签到日期(活动签到用)',
  `reward_type` tinyint(3) unsigned NOT NULL DEFAULT '10' COMMENT '奖励类型(10积分 20优惠券)',
  `reward_value` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '奖励数值',
  `store_id` int(11) unsigned NOT NULL DEFAULT '0',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`reward_id`),
  KEY `active_id` (`active_id`),
  KEY `store_id` (`store_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8 COMMENT='签到奖励规则表';

-- ----------------------------
-- Records of tao_signin_reward
-- ----------------------------
INSERT INTO `tao_signin_reward` VALUES ('1', '1', '1', '', '10', '10', '10001', '1778729657');
INSERT INTO `tao_signin_reward` VALUES ('2', '1', '2', '', '10', '20', '10001', '1778729657');
INSERT INTO `tao_signin_reward` VALUES ('3', '1', '3', '', '10', '30', '10001', '1778729657');
INSERT INTO `tao_signin_reward` VALUES ('4', '1', '4', '', '10', '40', '10001', '1778729657');
INSERT INTO `tao_signin_reward` VALUES ('5', '1', '5', '', '10', '50', '10001', '1778729657');
INSERT INTO `tao_signin_reward` VALUES ('6', '1', '6', '', '10', '60', '10001', '1778729657');
INSERT INTO `tao_signin_reward` VALUES ('7', '1', '7', '', '10', '70', '10001', '1778729657');

-- ----------------------------
-- Table structure for tao_skill_migration
-- ----------------------------
DROP TABLE IF EXISTS `tao_skill_migration`;
CREATE TABLE `tao_skill_migration` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `skill_name` varchar(64) NOT NULL COMMENT '技能包名',
  `version` varchar(20) NOT NULL COMMENT '迁移版本号',
  `filename` varchar(128) NOT NULL COMMENT '迁移文件名',
  `executed_at` int(11) NOT NULL COMMENT '执行时间',
  `store_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_skill_version` (`skill_name`,`version`),
  KEY `idx_skill_name` (`skill_name`)
) ENGINE=InnoDB AUTO_INCREMENT=1704 DEFAULT CHARSET=utf8mb4 COMMENT='技能包迁移记录';

-- ----------------------------
-- Records of tao_skill_migration
-- ----------------------------
INSERT INTO `tao_skill_migration` VALUES ('1', 'quote-cn', '1.0.0', '001_1.0.0_init.sql', '1780218208', '0');
INSERT INTO `tao_skill_migration` VALUES ('2', 'quote-cn', '1.0.1', '002_1.0.1_logo.sql', '1780218208', '0');
INSERT INTO `tao_skill_migration` VALUES ('3', 'content-engine', '1.0.0', '001_1.0.0_init.sql', '1780278614', '0');
INSERT INTO `tao_skill_migration` VALUES ('4', 'content-engine', '1.1.0', '002_1.1.0_upgrade.sql', '1780383292', '0');
INSERT INTO `tao_skill_migration` VALUES ('5', 'crm', '2.3.1', '001_2.3.1_customer_add_page.sql', '1780456547', '0');
INSERT INTO `tao_skill_migration` VALUES ('6', 'contentengine', '1.0.0', '001_1.0.0_init.sql', '1780628083', '0');
INSERT INTO `tao_skill_migration` VALUES ('7', 'contentengine', '1.1.0', '002_1.1.0_upgrade.sql', '1780628083', '0');
INSERT INTO `tao_skill_migration` VALUES ('8', 'quotecn', '1.0.1', '002_1.0.1_logo.sql', '1780628083', '0');
INSERT INTO `tao_skill_migration` VALUES ('9', 'energy', 'energy_v1_install', 'energy_v1_install.sql', '1780730622', '0');
INSERT INTO `tao_skill_migration` VALUES ('10', 'crm', '2.3.2', '002_2.3.2_claim_log_add_store_id.sql', '1780968118', '0');
INSERT INTO `tao_skill_migration` VALUES ('14', 'crm', '0.1.0', '003_0.1.0_crm_menus.sql', '1779740500', '0');
INSERT INTO `tao_skill_migration` VALUES ('15', 'platform', '0.1.0', '001_0.1.0_platform_notification.sql', '1781163655', '0');
INSERT INTO `tao_skill_migration` VALUES ('31', 'invoice', '01_create_tables', '01_create_tables.sql', '1781247260', '0');
INSERT INTO `tao_skill_migration` VALUES ('1048', 'crm', '0.2.0', '004_0.2.0_menu_reorg.sql', '1781262291', '0');
INSERT INTO `tao_skill_migration` VALUES ('1049', 'crm', '0.2.1', '005_0.2.1_menu_reorg_fix.sql', '1781262291', '0');
INSERT INTO `tao_skill_migration` VALUES ('1051', 'crm', '0.3.0', '006_0.3.0_invoice_menu.sql', '1781315669', '0');

-- ----------------------------
-- Table structure for tao_store
-- ----------------------------
DROP TABLE IF EXISTS `tao_store`;
CREATE TABLE `tao_store` (
  `store_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '商城ID',
  `store_name` varchar(50) NOT NULL DEFAULT '' COMMENT '商城名称',
  `describe` varchar(500) NOT NULL DEFAULT '' COMMENT '商城简介',
  `logo_image_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商城logo文件ID',
  `sort` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '排序(数字越小越靠前)',
  `wework_corpid` varchar(100) DEFAULT NULL COMMENT '企业微信企业ID',
  `wework_secret` varchar(200) DEFAULT NULL COMMENT '企业微信应用Secret',
  `wework_agentid` varchar(50) DEFAULT NULL COMMENT '企业微信应用AgentId',
  `wework_site` varchar(255) DEFAULT NULL,
  `wework_enabled` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否启用企业微信登录',
  `is_recycle` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否回收',
  `is_delete` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否删除',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`store_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10003 DEFAULT CHARSET=utf8 COMMENT='商家(商户)记录表';

-- ----------------------------
-- Records of tao_store
-- ----------------------------
INSERT INTO `tao_store` VALUES ('10001', '雄韬CRM', '感谢您选择我们，希望我们的努力能为您提供一款适用于企业级电商零售的利器。', '0', '100', '23', '23', '1000017', '23', '1', '0', '0', '1614556800', '1766884939');

-- ----------------------------
-- Table structure for tao_store_department
-- ----------------------------
DROP TABLE IF EXISTS `tao_store_department`;
CREATE TABLE `tao_store_department` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '部门ID',
  `store_id` int(11) NOT NULL DEFAULT '0' COMMENT '租户ID',
  `parent_id` int(11) NOT NULL DEFAULT '0' COMMENT '父部门ID，0表示顶级部门',
  `department_name` varchar(50) NOT NULL DEFAULT '' COMMENT '部门名称',
  `department_code` varchar(20) DEFAULT NULL COMMENT '部门编码（可选）',
  `sort` int(11) NOT NULL DEFAULT '100' COMMENT '排序(数字越小越靠前)',
  `manager_id` int(11) DEFAULT NULL COMMENT '部门主管ID，关联tao_store_user表',
  `description` varchar(255) DEFAULT NULL COMMENT '部门描述',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '状态：1=启用，0=禁用',
  `is_delete` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否删除',
  `wxwork_id` int(11) DEFAULT NULL COMMENT '企业微信部门ID',
  `wxwork_name` varchar(50) DEFAULT NULL COMMENT '企业微信部门名称（用于同步校验）',
  `wxwork_parentid` int(11) DEFAULT NULL COMMENT '企业微信父部门ID（用于同步校验）',
  `wxwork_order` int(11) DEFAULT NULL COMMENT '企业微信部门排序（用于同步校验）',
  `sync_direction` tinyint(1) DEFAULT '0' COMMENT '同步方向：0=未设置，1=系统到企业微信，2=企业微信到系统，3=双向同步',
  `sync_status` tinyint(1) DEFAULT '0' COMMENT '同步状态：0=未同步，1=已同步，2=同步失败，3=同步中',
  `sync_error` varchar(500) DEFAULT NULL COMMENT '最后一次同步错误信息',
  `sync_time` int(11) DEFAULT NULL COMMENT '最后同步时间',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_store_id` (`store_id`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_store_parent` (`store_id`,`parent_id`),
  KEY `idx_wxwork_id` (`wxwork_id`),
  KEY `idx_sync_status` (`sync_status`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COMMENT='部门表';

-- ----------------------------
-- Records of tao_store_department
-- ----------------------------

-- ----------------------------
-- Table structure for tao_store_menu
-- ----------------------------
DROP TABLE IF EXISTS `tao_store_menu`;
CREATE TABLE `tao_store_menu` (
  `menu_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '菜单ID',
  `module` tinyint(3) NOT NULL DEFAULT '10' COMMENT '模块类型(10菜单 20操作)',
  `fun_type` varchar(20) DEFAULT 'shop' COMMENT '归属: shop/crm/ai/fiscal',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '菜单名称',
  `path` varchar(255) NOT NULL DEFAULT '' COMMENT '菜单路径(唯一)',
  `action_mark` varchar(255) NOT NULL DEFAULT '' COMMENT '操作标识',
  `parent_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '上级菜单ID',
  `sort` int(11) unsigned NOT NULL DEFAULT '100' COMMENT '排序(数字越小越靠前)',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`menu_id`)
) ENGINE=InnoDB AUTO_INCREMENT=21009 DEFAULT CHARSET=utf8 COMMENT='商家后台菜单记录表';

-- ----------------------------
-- Records of tao_store_menu
-- ----------------------------
INSERT INTO `tao_store_menu` VALUES ('10742', '20', 'crm', '保存内容', '/crm.editor/save', 'save', '10701', '130', '1780628083', '1780628083');
INSERT INTO `tao_store_menu` VALUES ('19901', '10', 'crm', '销售管理', '/crm/quotation', '', '0', '120', '1781258214', '1781258214');
INSERT INTO `tao_store_menu` VALUES ('20001', '10', 'crm', '工作台', '/crm', '', '0', '10', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('20002', '10', 'crm', '仪表盘', '/crm/dashboard/index', '', '20001', '100', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('20010', '10', 'crm', '客户管理', '/crm/customer', '', '0', '20', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('20011', '10', 'crm', '客户列表', '/crm/customer/lists', '', '20010', '100', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('20012', '10', 'crm', '公海池', '/crm/customer/poolLists', '', '20010', '110', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('20013', '10', 'crm', '协作客户', '/crm/customer/collabLists', '', '20010', '120', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('20014', '10', 'crm', '客户详情', '/crm/customer/detail', '', '20010', '130', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('20015', '10', 'crm', '新增客户', '/crm/customer/create', '', '20010', '140', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('20016', '20', 'crm', '客户查询', '/crm/customer/select', 'select', '20011', '50', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('20017', '20', 'crm', '编辑客户', '/crm/customer/edit', 'edit', '20011', '100', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('20018', '20', 'crm', '删除客户', '/crm/customer/delete', 'delete', '20011', '110', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('20019', '20', 'crm', 'AI分析', '/crm/customer/analyze', 'analyze', '20012', '100', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('20020', '10', 'crm', '线索管理', '/crm/lead', '', '20010', '150', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('20021', '20', 'crm', '认领客户', '/crm/customer/claim', 'claim', '20012', '110', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('20022', '20', 'crm', '释放客户', '/crm/customer/release', 'release', '20012', '120', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('20023', '10', 'crm', '线索列表', '/crm/lead/lists', '', '20020', '100', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('20024', '10', 'crm', '线索详情', '/crm/lead/detail', '', '20020', '110', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('20025', '20', 'crm', '新增线索', '/crm/lead/add', 'add', '20023', '100', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('20026', '20', 'crm', 'AI画像', '/crm/customer/portrait', 'portrait', '20014', '100', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('20027', '20', 'crm', '编辑线索', '/crm/lead/edit', 'edit', '20023', '110', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('20028', '20', 'crm', '删除线索', '/crm/lead/delete', 'delete', '20023', '120', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('20029', '20', 'crm', '转为客户', '/crm/lead/convert', 'convert', '20023', '130', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('20030', '10', 'crm', '报价管理', '/crm/quotation', '', '19901', '40', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('20031', '10', 'crm', '报价列表', '/crm/quotation/lists', '', '20030', '100', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('20032', '10', 'crm', '报价详情', '/crm/quotation/detail', '', '20030', '110', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('20033', '20', 'crm', '新增报价', '/crm/quotation/add', 'add', '20031', '100', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('20034', '20', 'crm', '编辑报价', '/crm/quotation/edit', 'edit', '20031', '110', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('20035', '20', 'crm', '删除报价', '/crm/quotation/delete', 'delete', '20031', '120', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('20036', '20', 'crm', '发送报价', '/crm/quotation/send', 'send', '20032', '110', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('20037', '20', 'crm', '确认报价', '/crm/quotation/confirm', 'confirm', '20032', '120', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('20038', '20', 'crm', '转为订单', '/crm/quotation/convert', 'convert', '20032', '130', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('20039', '20', 'crm', '中文报价预览', '/crm/quotation_cn/preview', 'preview', '20031', '50', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('20040', '10', 'crm', '订单管理', '/crm/order', '', '19901', '50', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('20041', '10', 'crm', '订单列表', '/crm/order/lists', '', '20040', '100', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('20042', '10', 'crm', '订单详情', '/crm/order/detail', '', '20040', '110', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('20043', '20', 'crm', '新增订单', '/crm/order/add', 'add', '20041', '100', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('20044', '20', 'crm', '编辑订单', '/crm/order/edit', 'edit', '20041', '110', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('20045', '20', 'crm', '删除订单', '/crm/order/delete', 'delete', '20041', '120', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('20046', '20', 'crm', '状态变更', '/crm/order/changeStatus', 'changeStatus', '20042', '100', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('20047', '20', 'crm', '导出中文报价', '/crm/quotation_cn/exportPdf', 'exportPdf', '20031', '60', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('20048', '20', 'crm', '保存报价模板', '/crm/quotation_cn/saveTemplate', 'saveTemplate', '20031', '70', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('20050', '10', 'crm', '合同管理', '/crm/contract', '', '19901', '60', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('20051', '10', 'crm', '合同列表', '/crm/contract/lists', '', '20050', '100', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('20052', '10', 'crm', '合同详情', '/crm/contract/detail', '', '20050', '110', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('20053', '20', 'crm', '新增合同', '/crm/contract/add', 'add', '20051', '100', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('20054', '20', 'crm', '编辑合同', '/crm/contract/edit', 'edit', '20051', '110', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('20055', '20', 'crm', '删除合同', '/crm/contract/delete', 'delete', '20051', '120', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('20060', '10', 'crm', '产品管理', '/crm/product', '', '20080', '140', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('20061', '10', 'crm', '产品列表', '/crm/product/lists', '', '20060', '100', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('20062', '20', 'crm', '新增产品', '/crm/product/add', 'add', '20061', '100', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('20063', '20', 'crm', '编辑产品', '/crm/product/edit', 'edit', '20061', '110', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('20064', '20', 'crm', '删除产品', '/crm/product/delete', 'delete', '20061', '120', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('20065', '20', 'crm', '产品查询', '/crm/product/select', 'select', '20061', '50', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('20070', '10', 'crm', '知识库', '/crm/knowledge', '', '0', '80', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('20071', '10', 'crm', '知识列表', '/crm/knowledge/lists', '', '20070', '100', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('20072', '20', 'crm', '新增知识', '/crm/knowledge/add', 'add', '20071', '100', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('20073', '20', 'crm', '编辑知识', '/crm/knowledge/edit', 'edit', '20071', '110', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('20074', '20', 'crm', '删除知识', '/crm/knowledge/delete', 'delete', '20071', '120', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('20080', '10', 'crm', '系统设置', '/manage', '', '0', '200', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('20081', '10', 'crm', '管理员列表', '/crm/admin/user/list', '', '20080', '100', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('20082', '10', 'crm', '角色管理', '/crm/admin/role/list', '', '20080', '110', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('20083', '10', 'crm', '部门管理', '/crm/manage/department/tree', '', '20080', '120', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('20084', '10', 'crm', '菜单管理', '/crm/admin/menu/list', '', '20080', '130', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('20085', '20', 'crm', '新增管理员', '/crm/admin/user/add', 'add', '20081', '100', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('20086', '20', 'crm', '编辑管理员', '/crm/admin/user/edit', 'edit', '20081', '110', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('20087', '20', 'crm', '删除管理员', '/crm/admin/user/delete', 'delete', '20081', '120', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('20088', '20', 'crm', '新增角色', '/crm/admin/role/add', 'add', '20082', '100', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('20089', '20', 'crm', '编辑角色', '/crm/admin/role/edit', 'edit', '20082', '110', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('20090', '20', 'crm', '删除角色', '/crm/admin/role/delete', 'delete', '20082', '120', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('20091', '20', 'crm', '新增部门', '/crm/manage/department/add', 'add', '20083', '100', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('20092', '20', 'crm', '编辑部门', '/crm/manage/department/edit', 'edit', '20083', '110', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('20093', '20', 'crm', '删除部门', '/crm/manage/department/delete', 'delete', '20083', '120', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('20094', '20', 'crm', '分配员工', '/crm/manage/department/assignUser', 'assignUser', '20083', '130', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('20095', '20', 'crm', '移除员工', '/crm/manage/department/removeUser', 'removeUser', '20083', '140', '0', '0');
INSERT INTO `tao_store_menu` VALUES ('21000', '10', 'crm', '应用', '/app', '', '0', '200', '1781247179', '1781247179');
INSERT INTO `tao_store_menu` VALUES ('21001', '10', 'crm', '代开发票', '/app/invoice', '', '21000', '100', '1781247186', '1781247186');
INSERT INTO `tao_store_menu` VALUES ('21002', '10', 'crm', '发票管理', '/app/invoice/list', '', '21001', '100', '1781247186', '1781247186');
INSERT INTO `tao_store_menu` VALUES ('21003', '10', 'crm', '发票详情', '/app/invoice/detail', '', '21001', '105', '1781247186', '1781247186');
INSERT INTO `tao_store_menu` VALUES ('21004', '10', 'crm', '开票公司', '/app/invoice/company', '', '21001', '110', '1781247186', '1781247186');
INSERT INTO `tao_store_menu` VALUES ('21005', '10', 'crm', '公司详情', '/app/invoice/company/detail', '', '21001', '115', '1781247186', '1781247186');
INSERT INTO `tao_store_menu` VALUES ('21006', '10', 'crm', '短信配置', '/app/invoice/sms', '', '21001', '120', '1781247186', '1781247186');
INSERT INTO `tao_store_menu` VALUES ('21007', '10', 'crm', 'OCR 配置', '/app/invoice/ocr', '', '21001', '125', '1781251950', '1781251950');
INSERT INTO `tao_store_menu` VALUES ('21008', '10', 'crm', '会员列表', '/app/invoice/member', '', '21001', '130', '1781262314', '1781262314');

-- ----------------------------
-- Table structure for tao_store_menu_api
-- ----------------------------
DROP TABLE IF EXISTS `tao_store_menu_api`;
CREATE TABLE `tao_store_menu_api` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `menu_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '菜单ID',
  `api_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户角色ID',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `menu_id` (`menu_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11323 DEFAULT CHARSET=utf8 COMMENT='商家后台用户角色与菜单权限关系表';

-- ----------------------------
-- Records of tao_store_menu_api
-- ----------------------------
INSERT INTO `tao_store_menu_api` VALUES ('10112', '10002', '10005', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10113', '10002', '10003', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10114', '10002', '10002', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10124', '10003', '10004', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10125', '10003', '10003', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10126', '10003', '10002', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10127', '10005', '10005', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10128', '10005', '10003', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10129', '10005', '10002', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10130', '10008', '10007', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10131', '10008', '10003', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10132', '10008', '10002', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10133', '10006', '10006', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10134', '10006', '10003', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10135', '10006', '10002', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10142', '10011', '10012', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10143', '10011', '10008', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10144', '10011', '10002', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10148', '10004', '10009', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10149', '10004', '11005', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10150', '10004', '11004', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10151', '10004', '10008', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10152', '10004', '10002', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10153', '10009', '10010', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10154', '10009', '11006', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10155', '10009', '10008', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10156', '10009', '10002', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10157', '10010', '10011', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10158', '10010', '11006', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10159', '10010', '10008', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10160', '10010', '10002', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10161', '10001', '10001', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10162', '10013', '11021', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10163', '10013', '11020', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10168', '10014', '11023', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10169', '10014', '11022', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10170', '10014', '11020', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10171', '10017', '11027', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10172', '10017', '11022', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10173', '10017', '11020', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10174', '10016', '11026', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10175', '10016', '11022', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10176', '10016', '11020', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10177', '10019', '11029', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10178', '10019', '11028', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10179', '10020', '11032', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10180', '10020', '11028', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10181', '10021', '11031', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10182', '10021', '11033', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10183', '10021', '11028', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10184', '10022', '11034', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10185', '10022', '11028', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10189', '10024', '11038', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10190', '10024', '11035', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10191', '10024', '11028', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10192', '10025', '11039', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10193', '10025', '11035', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10194', '10025', '11028', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10195', '10026', '11040', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10196', '10026', '11035', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10197', '10026', '11028', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10200', '10029', '11043', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10201', '10029', '11042', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10202', '10029', '11050', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10203', '10030', '11046', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10204', '10030', '11044', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10205', '10030', '11042', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10206', '10030', '11050', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10207', '10031', '11047', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10208', '10031', '11045', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10209', '10031', '11044', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10210', '10031', '11042', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10211', '10031', '11050', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10212', '10032', '11048', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10213', '10032', '11042', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10214', '10032', '11050', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10215', '10140', '11049', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10216', '10140', '11042', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10217', '10140', '11050', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10218', '10033', '11156', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10219', '10033', '11155', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10220', '10033', '11157', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10221', '10033', '11154', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10222', '10035', '11053', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10223', '10035', '11061', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10224', '10035', '11052', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10225', '10035', '11051', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10226', '10035', '11060', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10247', '10036', '11056', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10248', '10036', '11061', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10249', '10036', '11098', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10250', '10036', '11178', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10251', '10036', '11163', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10252', '10036', '11052', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10253', '10036', '11051', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10254', '10036', '11060', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10255', '10036', '11091', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10256', '10036', '11089', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10257', '10036', '11065', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10258', '10036', '11158', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10259', '10036', '11154', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10272', '10040', '11062', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10273', '10040', '11060', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10274', '10040', '11051', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10275', '10041', '11063', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10276', '10041', '11060', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10277', '10041', '11051', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10278', '10042', '11064', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10279', '10042', '11060', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10280', '10042', '11051', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10281', '10039', '11061', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10282', '10039', '11060', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10283', '10039', '11051', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10284', '10043', '11066', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10285', '10043', '11065', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10286', '10043', '11051', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10287', '10044', '11067', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10288', '10044', '11065', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10289', '10044', '11051', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10290', '10045', '11068', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10291', '10045', '11065', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10292', '10045', '11051', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10293', '10046', '11069', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10294', '10046', '11065', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10295', '10046', '11051', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10296', '10047', '11071', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10297', '10047', '11070', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10298', '10047', '11051', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10302', '10049', '11074', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10303', '10049', '11070', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10304', '10049', '11051', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10305', '10048', '11073', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10306', '10048', '11179', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10307', '10048', '11070', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10308', '10048', '11051', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10309', '10051', '11077', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10310', '10051', '11169', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10311', '10051', '11037', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10312', '10051', '11132', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10313', '10051', '11076', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10314', '10051', '11164', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10315', '10051', '11154', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10316', '10051', '11035', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10317', '10051', '11028', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10318', '10052', '11079', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10319', '10052', '11132', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10320', '10052', '11076', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10321', '10053', '11082', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10322', '10053', '11132', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10323', '10053', '11076', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10324', '10054', '11081', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10325', '10054', '11132', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10326', '10054', '11076', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10327', '10139', '11080', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10328', '10139', '11132', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10329', '10139', '11076', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10336', '10058', '11087', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10337', '10058', '11084', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10338', '10058', '11076', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10339', '10059', '11088', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10340', '10059', '11084', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10341', '10059', '11076', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10348', '10055', '11078', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10349', '10055', '11180', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10350', '10055', '11132', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10351', '10055', '11076', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10352', '10055', '11170', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10353', '10055', '11154', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10354', '10056', '11085', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10355', '10056', '11024', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10356', '10056', '11084', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10357', '10056', '11076', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10358', '10056', '11022', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10359', '10056', '11020', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10360', '10138', '11083', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10361', '10138', '11180', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10362', '10138', '11132', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10363', '10138', '11076', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10364', '10138', '11170', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10365', '10138', '11154', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10366', '10057', '11086', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10367', '10057', '11024', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10368', '10057', '11084', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10369', '10057', '11076', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10370', '10057', '11022', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10371', '10057', '11020', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10372', '10061', '11092', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10373', '10061', '11098', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10374', '10061', '11090', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10375', '10061', '11089', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10376', '10061', '11091', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10377', '10062', '11094', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10378', '10062', '11090', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10379', '10062', '11089', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10380', '10063', '11098', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10381', '10063', '11095', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10382', '10063', '11091', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10383', '10063', '11089', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10384', '10063', '11090', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10385', '10064', '11096', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10386', '10064', '11090', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10387', '10064', '11089', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10388', '10066', '11099', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10389', '10066', '11091', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10390', '10066', '11089', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10391', '10065', '11097', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10392', '10065', '11091', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10393', '10065', '11089', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10394', '10067', '11100', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10395', '10067', '11091', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10396', '10067', '11089', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10397', '10068', '11101', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10398', '10068', '11091', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10399', '10068', '11089', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10400', '10070', '11103', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10401', '10070', '11102', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10402', '10070', '11089', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10403', '10071', '11104', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10404', '10071', '11102', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10405', '10071', '11089', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10409', '10075', '11108', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10410', '10075', '11106', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10411', '10075', '11105', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10415', '10077', '11110', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10416', '10077', '11106', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10417', '10077', '11105', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10418', '10078', '11112', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10419', '10078', '11111', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10420', '10078', '11106', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10421', '10078', '11105', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10422', '10079', '11113', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10423', '10079', '11111', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10424', '10079', '11106', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10425', '10079', '11105', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10426', '10080', '11114', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10427', '10080', '11111', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10428', '10080', '11106', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10429', '10080', '11105', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10434', '10074', '11107', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10435', '10074', '11112', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10436', '10074', '11106', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10437', '10074', '11105', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10438', '10074', '11111', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10442', '10083', '11118', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10443', '10083', '11123', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10444', '10083', '11117', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10445', '10083', '11116', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10446', '10083', '11122', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10447', '10084', '11119', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10448', '10084', '11117', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10449', '10084', '11116', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10450', '10085', '11120', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10451', '10085', '11117', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10452', '10085', '11116', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10453', '10137', '11121', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10454', '10137', '11117', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10455', '10137', '11116', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10456', '10087', '11124', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10457', '10087', '11122', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10458', '10087', '11116', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10459', '10086', '11123', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10460', '10086', '11122', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10461', '10086', '11116', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10462', '10089', '11126', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10463', '10089', '11122', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10464', '10089', '11116', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10465', '10088', '11125', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10466', '10088', '11122', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10467', '10088', '11116', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10468', '10090', '11128', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10469', '10090', '11127', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10470', '10090', '11105', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10471', '10091', '11129', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10472', '10091', '11127', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10473', '10091', '11105', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10474', '10092', '11130', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10475', '10092', '11127', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10476', '10092', '11105', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10477', '10093', '11115', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10478', '10093', '11131', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10479', '10093', '11111', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10480', '10093', '11106', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10481', '10093', '11105', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10482', '10093', '11127', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10486', '10096', '11135', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10487', '10096', '11134', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10488', '10096', '11133', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10489', '10099', '11138', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10490', '10099', '11134', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10491', '10099', '11133', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10498', '10100', '11139', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10499', '10100', '11134', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10500', '10100', '11133', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10514', '10098', '11137', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10515', '10098', '11181', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10516', '10098', '11054', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10517', '10098', '11061', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10518', '10098', '11053', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10519', '10098', '11134', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10520', '10098', '11133', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10521', '10098', '11052', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10522', '10098', '11051', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10523', '10098', '11060', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10524', '10097', '11136', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10525', '10097', '11053', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10526', '10097', '11061', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10527', '10097', '11134', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10528', '10097', '11133', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10529', '10097', '11052', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10530', '10097', '11051', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10531', '10097', '11060', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10532', '10102', '11142', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10533', '10102', '11141', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10534', '10102', '11140', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10535', '10102', '11133', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10536', '10103', '11143', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10537', '10103', '11141', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10538', '10103', '11140', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10539', '10103', '11133', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10540', '10104', '11144', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10541', '10104', '11141', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10542', '10104', '11140', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10543', '10104', '11133', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10544', '10105', '11145', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10545', '10105', '11141', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10546', '10105', '11140', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10547', '10105', '11133', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10548', '10106', '11155', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10549', '10106', '11156', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10550', '10106', '11157', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10551', '10106', '11154', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10552', '10108', '11155', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10553', '10108', '11156', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10554', '10108', '11157', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10555', '10108', '11154', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10556', '10109', '11147', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10557', '10109', '11146', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10558', '10109', '11133', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10559', '10110', '11155', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10560', '10110', '11156', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10561', '10110', '11157', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10562', '10110', '11154', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10563', '10111', '11148', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10564', '10111', '11149', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10565', '10111', '11150', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10569', '10114', '11153', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10570', '10114', '11152', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10571', '10114', '11151', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10572', '10114', '11182', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10573', '10116', '11155', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10574', '10116', '11156', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10575', '10116', '11157', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10576', '10116', '11154', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10577', '10117', '11155', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10578', '10117', '11156', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10579', '10117', '11157', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10580', '10117', '11154', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10581', '10118', '11155', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10582', '10118', '11156', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10583', '10118', '11157', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10584', '10118', '11154', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10585', '10120', '11155', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10586', '10120', '11156', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10587', '10120', '11157', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10588', '10120', '11154', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10589', '10124', '11162', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10590', '10124', '11158', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10591', '10124', '11154', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10592', '10121', '11159', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10593', '10121', '11158', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10594', '10121', '11154', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10595', '10122', '11160', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10596', '10122', '11158', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10597', '10122', '11154', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10604', '10123', '11161', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10605', '10123', '11183', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10606', '10123', '11158', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10607', '10123', '11154', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10608', '10125', '11165', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10609', '10125', '11164', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10610', '10125', '11154', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10611', '10126', '11166', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10612', '10126', '11164', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10613', '10126', '11154', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10614', '10127', '11167', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10615', '10127', '11164', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10616', '10127', '11154', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10617', '10128', '11168', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10618', '10128', '11164', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10619', '10128', '11154', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10624', '10130', '11171', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10625', '10130', '11170', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10626', '10130', '11154', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10627', '10134', '11156', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10628', '10134', '11155', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10629', '10134', '11157', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10630', '10134', '11180', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10631', '10134', '11154', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10632', '10134', '11170', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10633', '10131', '11172', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10634', '10131', '11170', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10635', '10131', '11154', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10636', '10132', '11173', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10637', '10132', '11170', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10638', '10132', '11154', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10639', '10133', '11174', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10640', '10133', '11170', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10641', '10133', '11154', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10642', '10136', '11175', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10643', '10136', '11176', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10644', '10136', '11177', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10645', '10136', '11154', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10650', '10023', '11036', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10651', '10023', '11030', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10652', '10023', '11035', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10653', '10023', '11028', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10654', '10027', '11041', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10655', '10027', '11030', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10656', '10027', '11028', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10657', '10076', '11109', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10658', '10076', '11184', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10659', '10076', '11106', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10660', '10076', '11105', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10661', '10141', '11055', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10662', '10141', '11056', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10663', '10141', '11178', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10664', '10141', '11098', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10665', '10141', '11163', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10666', '10141', '11052', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10667', '10141', '11051', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10668', '10141', '11065', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10669', '10141', '11091', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10670', '10141', '11089', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10671', '10141', '11158', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10672', '10141', '11154', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10673', '10144', '11188', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10674', '10144', '11187', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10675', '10144', '11186', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10676', '10144', '11185', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10681', '10145', '11191', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10682', '10145', '11192', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10683', '10145', '11190', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10684', '10145', '11186', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10685', '10145', '11185', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10686', '10151', '11193', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10687', '10151', '11190', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10688', '10151', '11186', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10689', '10151', '11185', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10690', '10152', '11194', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10691', '10152', '11190', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10692', '10152', '11186', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10693', '10152', '11185', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10694', '10153', '11195', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10695', '10153', '11190', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10696', '10153', '11186', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10697', '10153', '11185', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10698', '10146', '11197', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10699', '10146', '11196', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10700', '10146', '11186', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10701', '10146', '11185', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10702', '10147', '11199', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10703', '10147', '11198', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10704', '10147', '11186', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10705', '10147', '11185', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10706', '10156', '11202', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10707', '10156', '11198', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10708', '10156', '11186', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10709', '10156', '11185', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10710', '10155', '11201', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10711', '10155', '11198', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10712', '10155', '11186', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10713', '10155', '11185', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10714', '10154', '11200', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10715', '10154', '11198', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10716', '10154', '11186', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10717', '10154', '11185', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10718', '10148', '11203', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10719', '10148', '11204', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10720', '10148', '11205', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10721', '10148', '11206', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10722', '10148', '11186', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10723', '10148', '11185', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10724', '10149', '11203', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10725', '10149', '11204', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10726', '10149', '11205', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10727', '10149', '11206', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10728', '10149', '11186', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10729', '10149', '11185', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10730', '10150', '11189', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10731', '10150', '11187', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10732', '10150', '11186', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10733', '10150', '11185', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10734', '10161', '11212', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10735', '10161', '11213', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10736', '10161', '11207', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10737', '10161', '11185', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10743', '10160', '11209', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10744', '10160', '11211', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10745', '10160', '11213', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10746', '10160', '11207', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10747', '10160', '11185', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10748', '10159', '11210', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10749', '10159', '11213', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10750', '10159', '11207', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10751', '10159', '11185', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10756', '10165', '11215', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10757', '10165', '11214', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10758', '10165', '11207', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10759', '10165', '11185', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10760', '10166', '11216', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10761', '10166', '11214', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10762', '10166', '11207', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10763', '10166', '11185', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10764', '10167', '11217', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10765', '10167', '11214', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10766', '10167', '11207', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10767', '10167', '11185', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10768', '10164', '11218', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10769', '10164', '11219', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10770', '10164', '11220', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10771', '10164', '11207', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10772', '10164', '11185', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10773', '10173', '11229', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10774', '10173', '11222', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10775', '10173', '11221', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10776', '10173', '11185', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10777', '10170', '11225', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10778', '10170', '11222', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10779', '10170', '11221', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10780', '10170', '11185', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10785', '10171', '11227', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10786', '10171', '11222', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10787', '10171', '11221', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10788', '10171', '11185', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10789', '10172', '11228', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10790', '10172', '11226', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10791', '10172', '11222', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10792', '10172', '11221', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10793', '10172', '11185', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10798', '10177', '11232', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10799', '10177', '11223', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10800', '10177', '11221', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10801', '10177', '11185', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10802', '10176', '11233', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10803', '10176', '11223', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10804', '10176', '11221', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10805', '10176', '11185', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10806', '10178', '11234', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10807', '10178', '11223', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10808', '10178', '11221', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10809', '10178', '11185', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10810', '10183', '11242', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10811', '10183', '11235', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10812', '10183', '11223', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10813', '10183', '11221', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10814', '10183', '11185', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10819', '10184', '11239', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10820', '10184', '11235', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10821', '10184', '11223', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10822', '10184', '11221', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10823', '10184', '11185', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10835', '10182', '11241', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10836', '10182', '11238', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10837', '10182', '11235', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10838', '10182', '11223', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10839', '10182', '11221', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10840', '10182', '11185', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10841', '10185', '11224', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10842', '10185', '11243', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10843', '10185', '11244', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10844', '10185', '11221', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10845', '10185', '11185', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10846', '10158', '11208', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10847', '10158', '11213', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10848', '10158', '11207', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10849', '10158', '11185', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10850', '10037', '11055', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10851', '10037', '11057', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10852', '10037', '11178', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10853', '10037', '11163', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10854', '10037', '11098', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10855', '10037', '11061', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10856', '10037', '11245', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10857', '10037', '11052', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10858', '10037', '11051', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10859', '10037', '11065', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10860', '10037', '11158', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10861', '10037', '11154', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10862', '10037', '11091', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10863', '10037', '11089', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10864', '10037', '11060', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10865', '10175', '11230', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10866', '10175', '11223', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10867', '10175', '11221', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10868', '10175', '11185', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10869', '10180', '11236', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10870', '10180', '11235', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10871', '10180', '11223', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10872', '10180', '11221', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10873', '10180', '11185', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10874', '10181', '11237', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10875', '10181', '11240', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10876', '10181', '11231', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10877', '10181', '11235', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10878', '10181', '11223', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10879', '10181', '11221', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10880', '10181', '11185', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10881', '10186', '11155', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10882', '10186', '11156', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10883', '10186', '11157', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10884', '10186', '11154', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10885', '10188', '11247', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10886', '10188', '11248', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10887', '10188', '11249', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10888', '10188', '11250', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10889', '10188', '11246', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10890', '10188', '11185', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10891', '10189', '11251', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10892', '10189', '11132', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10893', '10189', '11076', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10894', '10191', '11253', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10895', '10191', '11254', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10896', '10191', '11252', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10897', '10191', '11020', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10898', '10194', '11256', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10899', '10194', '11252', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10900', '10194', '11020', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10901', '10195', '11255', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10902', '10195', '11257', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10903', '10195', '11252', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10904', '10195', '11020', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10905', '10200', '11258', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10906', '10200', '11252', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10907', '10200', '11020', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10908', '10192', '11260', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10909', '10192', '11261', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10910', '10192', '11259', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10911', '10192', '11252', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10912', '10192', '11020', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10913', '10196', '11262', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10914', '10196', '11259', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10915', '10196', '11252', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10916', '10196', '11020', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10917', '10197', '11263', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10918', '10197', '11259', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10919', '10197', '11252', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10920', '10197', '11020', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10921', '10198', '11264', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10922', '10198', '11259', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10923', '10198', '11252', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10924', '10198', '11020', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10925', '10193', '11265', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10926', '10193', '11252', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10927', '10193', '11020', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10928', '10202', '11267', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10929', '10202', '11266', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10930', '10202', '11268', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10931', '10202', '11076', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10932', '10204', '11059', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10933', '10204', '11052', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10934', '10204', '11051', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10935', '10038', '11058', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10936', '10038', '11052', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10937', '10038', '11051', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10938', '10205', '11269', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10939', '10205', '11253', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10940', '10205', '11260', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10941', '10205', '11261', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10942', '10205', '11254', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10943', '10205', '11132', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10944', '10205', '11076', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10945', '10205', '11252', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10946', '10205', '11020', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10947', '10205', '11259', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('10952', '10015', '11025', '1636213180');
INSERT INTO `tao_store_menu_api` VALUES ('10953', '10015', '11022', '1636213180');
INSERT INTO `tao_store_menu_api` VALUES ('10954', '10015', '11020', '1636213180');
INSERT INTO `tao_store_menu_api` VALUES ('10985', '10051', '11077', '1636213180');
INSERT INTO `tao_store_menu_api` VALUES ('10986', '10051', '11169', '1636213180');
INSERT INTO `tao_store_menu_api` VALUES ('10987', '10051', '11253', '1636213180');
INSERT INTO `tao_store_menu_api` VALUES ('10988', '10051', '11254', '1636213180');
INSERT INTO `tao_store_menu_api` VALUES ('10989', '10051', '11260', '1636213180');
INSERT INTO `tao_store_menu_api` VALUES ('10990', '10051', '11261', '1636213180');
INSERT INTO `tao_store_menu_api` VALUES ('10991', '10051', '11180', '1636213180');
INSERT INTO `tao_store_menu_api` VALUES ('10992', '10051', '11171', '1636213180');
INSERT INTO `tao_store_menu_api` VALUES ('10993', '10051', '11132', '1636213180');
INSERT INTO `tao_store_menu_api` VALUES ('10994', '10051', '11076', '1636213180');
INSERT INTO `tao_store_menu_api` VALUES ('10995', '10051', '11164', '1636213180');
INSERT INTO `tao_store_menu_api` VALUES ('10996', '10051', '11154', '1636213180');
INSERT INTO `tao_store_menu_api` VALUES ('10997', '10051', '11252', '1636213180');
INSERT INTO `tao_store_menu_api` VALUES ('10998', '10051', '11020', '1636213180');
INSERT INTO `tao_store_menu_api` VALUES ('10999', '10051', '11259', '1636213180');
INSERT INTO `tao_store_menu_api` VALUES ('11000', '10051', '11170', '1636213180');
INSERT INTO `tao_store_menu_api` VALUES ('11001', '10055', '11078', '1636213180');
INSERT INTO `tao_store_menu_api` VALUES ('11002', '10055', '11165', '1636213180');
INSERT INTO `tao_store_menu_api` VALUES ('11003', '10055', '11169', '1636213180');
INSERT INTO `tao_store_menu_api` VALUES ('11004', '10055', '11180', '1636213180');
INSERT INTO `tao_store_menu_api` VALUES ('11005', '10055', '11171', '1636213180');
INSERT INTO `tao_store_menu_api` VALUES ('11006', '10055', '11253', '1636213180');
INSERT INTO `tao_store_menu_api` VALUES ('11007', '10055', '11254', '1636213180');
INSERT INTO `tao_store_menu_api` VALUES ('11008', '10055', '11132', '1636213180');
INSERT INTO `tao_store_menu_api` VALUES ('11009', '10055', '11076', '1636213180');
INSERT INTO `tao_store_menu_api` VALUES ('11010', '10055', '11164', '1636213180');
INSERT INTO `tao_store_menu_api` VALUES ('11011', '10055', '11154', '1636213180');
INSERT INTO `tao_store_menu_api` VALUES ('11012', '10055', '11170', '1636213180');
INSERT INTO `tao_store_menu_api` VALUES ('11013', '10055', '11252', '1636213180');
INSERT INTO `tao_store_menu_api` VALUES ('11014', '10055', '11020', '1636213180');
INSERT INTO `tao_store_menu_api` VALUES ('11015', '10207', '11274', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11016', '10207', '11275', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11017', '10207', '11276', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11018', '10207', '11279', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11019', '10207', '11273', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11020', '10207', '11154', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11021', '10207', '11277', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11022', '10209', '11278', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11023', '10209', '11279', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11024', '10209', '11277', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11025', '10209', '11273', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11026', '10209', '11154', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11027', '10210', '11281', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11028', '10210', '11277', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11029', '10210', '11273', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11030', '10210', '11154', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11031', '10213', '11283', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11032', '10213', '11277', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11033', '10213', '11273', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11034', '10213', '11154', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11035', '10211', '11280', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11036', '10211', '11282', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11037', '10211', '11277', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11038', '10211', '11273', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11060', '10214', '11077', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11061', '10214', '11169', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11062', '10214', '11165', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11063', '10214', '11287', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11064', '10214', '11288', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11065', '10214', '11132', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11066', '10214', '11076', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11067', '10214', '11164', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11068', '10214', '11154', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11069', '10214', '11286', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11070', '10214', '11285', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11071', '10214', '11185', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11072', '10222', '11293', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11073', '10222', '11294', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11074', '10222', '11295', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11075', '10222', '11285', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11076', '10222', '11185', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11082', '10219', '11287', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11083', '10219', '11288', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11084', '10219', '11165', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11085', '10219', '11169', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11086', '10219', '11286', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11087', '10219', '11285', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11088', '10219', '11185', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11089', '10219', '11164', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11090', '10219', '11154', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11091', '10220', '11165', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11092', '10220', '11169', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11093', '10220', '11290', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11094', '10220', '11164', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11095', '10220', '11154', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11096', '10220', '11286', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11097', '10220', '11285', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11098', '10220', '11185', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11099', '10221', '11291', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11100', '10221', '11289', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11101', '10221', '11165', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11102', '10221', '11169', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11103', '10221', '11286', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11104', '10221', '11285', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11105', '10221', '11185', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11106', '10221', '11164', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11107', '10221', '11154', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11108', '10052', '11079', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11109', '10052', '11024', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11110', '10052', '11023', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11111', '10052', '11132', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11112', '10052', '11076', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11113', '10052', '11022', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11114', '10052', '11020', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11115', '10215', '11079', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11116', '10215', '11023', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11117', '10215', '11024', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11118', '10215', '11132', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11119', '10215', '11076', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11120', '10215', '11022', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11121', '10215', '11020', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11122', '10216', '11284', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11123', '10216', '11023', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11124', '10216', '11024', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11125', '10216', '11132', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11126', '10216', '11076', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11127', '10216', '11022', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11128', '10216', '11020', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11129', '10223', '11296', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11130', '10223', '11132', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11131', '10223', '11076', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11132', '10235', '11301', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11133', '10235', '11313', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11134', '10235', '11314', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11135', '10235', '11297', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11136', '10235', '11185', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11137', '10231', '11309', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11138', '10231', '11300', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11139', '10231', '11297', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11140', '10231', '11185', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11141', '10232', '11310', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11142', '10232', '11300', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11143', '10232', '11297', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11144', '10232', '11185', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11145', '10233', '11311', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11146', '10233', '11300', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11147', '10233', '11297', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11148', '10233', '11185', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11149', '10234', '11312', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11150', '10234', '11300', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11151', '10234', '11297', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11152', '10234', '11185', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11153', '10230', '11307', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11154', '10230', '11299', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11155', '10230', '11308', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11156', '10230', '11297', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11157', '10230', '11185', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11163', '10229', '11306', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11164', '10229', '11298', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11165', '10229', '11297', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11166', '10229', '11185', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11184', '10227', '11304', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11185', '10227', '11245', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11186', '10227', '11298', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11187', '10227', '11297', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11188', '10227', '11185', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11189', '10227', '11052', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11190', '10227', '11051', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11191', '10228', '11305', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11192', '10228', '11303', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11193', '10228', '11245', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11194', '10228', '11298', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11195', '10228', '11297', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11196', '10228', '11185', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11197', '10228', '11052', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11198', '10228', '11051', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11199', '10110', '11156', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11200', '10110', '11155', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11201', '10110', '11157', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11202', '10110', '11054', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11203', '10110', '11061', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11204', '10110', '11154', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11205', '10110', '11052', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11206', '10110', '11051', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11207', '10110', '11060', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11208', '10226', '11302', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11209', '10226', '11303', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11210', '10226', '11245', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11211', '10226', '11053', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11212', '10226', '11061', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11213', '10226', '11298', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11214', '10226', '11297', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11215', '10226', '11185', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11216', '10226', '11052', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11217', '10226', '11051', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11218', '10226', '11060', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11219', '10236', '11317', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11220', '10236', '11134', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11221', '10236', '11133', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11222', '10237', '11155', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11223', '10237', '11156', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11224', '10237', '11157', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11225', '10237', '11154', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11261', '10245', '11326', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11262', '10245', '11324', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11263', '10245', '11051', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11264', '10244', '11325', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11265', '10244', '11324', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11266', '10244', '11051', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11267', '10246', '11327', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11268', '10246', '11324', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11269', '10246', '11051', '1614556800');
INSERT INTO `tao_store_menu_api` VALUES ('11270', '10500', '10100', '1779787084');
INSERT INTO `tao_store_menu_api` VALUES ('11271', '10501', '10101', '1779787084');
INSERT INTO `tao_store_menu_api` VALUES ('11272', '10502', '10102', '1779787084');
INSERT INTO `tao_store_menu_api` VALUES ('11273', '10503', '10103', '1779787084');
INSERT INTO `tao_store_menu_api` VALUES ('11274', '10504', '10104', '1779787084');
INSERT INTO `tao_store_menu_api` VALUES ('11275', '10505', '10105', '1779787084');
INSERT INTO `tao_store_menu_api` VALUES ('11276', '10506', '10106', '1779787084');
INSERT INTO `tao_store_menu_api` VALUES ('11277', '10507', '10107', '1779787084');
INSERT INTO `tao_store_menu_api` VALUES ('11278', '10508', '10108', '1779787084');
INSERT INTO `tao_store_menu_api` VALUES ('11279', '10509', '10109', '1779787084');
INSERT INTO `tao_store_menu_api` VALUES ('11280', '10510', '10110', '1779787084');
INSERT INTO `tao_store_menu_api` VALUES ('11281', '10511', '10111', '1779787084');
INSERT INTO `tao_store_menu_api` VALUES ('11282', '10512', '10112', '1779787084');
INSERT INTO `tao_store_menu_api` VALUES ('11283', '10513', '10113', '1779787084');
INSERT INTO `tao_store_menu_api` VALUES ('11284', '10514', '10114', '1779787084');
INSERT INTO `tao_store_menu_api` VALUES ('11285', '10515', '10115', '1779787084');
INSERT INTO `tao_store_menu_api` VALUES ('11286', '10516', '10116', '1779787084');
INSERT INTO `tao_store_menu_api` VALUES ('11287', '10517', '10117', '1779787084');
INSERT INTO `tao_store_menu_api` VALUES ('11288', '10518', '10118', '1779787084');
INSERT INTO `tao_store_menu_api` VALUES ('11289', '10519', '10119', '1779787084');
INSERT INTO `tao_store_menu_api` VALUES ('11290', '10520', '10120', '1779787084');
INSERT INTO `tao_store_menu_api` VALUES ('11291', '10521', '10121', '1779787084');
INSERT INTO `tao_store_menu_api` VALUES ('11292', '10522', '10122', '1779787084');
INSERT INTO `tao_store_menu_api` VALUES ('11293', '10523', '10123', '1779787084');
INSERT INTO `tao_store_menu_api` VALUES ('11294', '10524', '10124', '1779787084');
INSERT INTO `tao_store_menu_api` VALUES ('11295', '10525', '10125', '1779787084');
INSERT INTO `tao_store_menu_api` VALUES ('11296', '10526', '10126', '1779787084');
INSERT INTO `tao_store_menu_api` VALUES ('11297', '10527', '10127', '1779787084');
INSERT INTO `tao_store_menu_api` VALUES ('11298', '10528', '10128', '1779787084');
INSERT INTO `tao_store_menu_api` VALUES ('11299', '10529', '10129', '1779787084');
INSERT INTO `tao_store_menu_api` VALUES ('11300', '10530', '10130', '1779787084');
INSERT INTO `tao_store_menu_api` VALUES ('11301', '10531', '10131', '1779787084');
INSERT INTO `tao_store_menu_api` VALUES ('11302', '10532', '10132', '1779787084');
INSERT INTO `tao_store_menu_api` VALUES ('11303', '10533', '10133', '1779787084');
INSERT INTO `tao_store_menu_api` VALUES ('11304', '10534', '10134', '1779787084');
INSERT INTO `tao_store_menu_api` VALUES ('11305', '10535', '10135', '1779787084');
INSERT INTO `tao_store_menu_api` VALUES ('11306', '10536', '10136', '1779787084');
INSERT INTO `tao_store_menu_api` VALUES ('11307', '10537', '10137', '1779787084');
INSERT INTO `tao_store_menu_api` VALUES ('11308', '10538', '10138', '1779787084');
INSERT INTO `tao_store_menu_api` VALUES ('11309', '10539', '10139', '1779787084');
INSERT INTO `tao_store_menu_api` VALUES ('11310', '10540', '10140', '1779787084');
INSERT INTO `tao_store_menu_api` VALUES ('11311', '10541', '10141', '1779787084');
INSERT INTO `tao_store_menu_api` VALUES ('11312', '10542', '10142', '1779787084');
INSERT INTO `tao_store_menu_api` VALUES ('11313', '10543', '10143', '1779787084');
INSERT INTO `tao_store_menu_api` VALUES ('11314', '10544', '10144', '1779787084');
INSERT INTO `tao_store_menu_api` VALUES ('11315', '10545', '10145', '1779787084');
INSERT INTO `tao_store_menu_api` VALUES ('11316', '10546', '10146', '1779787084');
INSERT INTO `tao_store_menu_api` VALUES ('11317', '10547', '10147', '1779787084');
INSERT INTO `tao_store_menu_api` VALUES ('11318', '10548', '10148', '1779787084');
INSERT INTO `tao_store_menu_api` VALUES ('11319', '10549', '10149', '1779787084');
INSERT INTO `tao_store_menu_api` VALUES ('11320', '10550', '10150', '1779787084');
INSERT INTO `tao_store_menu_api` VALUES ('11321', '10551', '10151', '1779787084');
INSERT INTO `tao_store_menu_api` VALUES ('11322', '10552', '10152', '1779787084');

-- ----------------------------
-- Table structure for tao_store_role
-- ----------------------------
DROP TABLE IF EXISTS `tao_store_role`;
CREATE TABLE `tao_store_role` (
  `role_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '角色ID',
  `role_name` varchar(50) NOT NULL DEFAULT '' COMMENT '角色名称',
  `parent_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '父级角色ID',
  `sort` int(11) unsigned NOT NULL DEFAULT '100' COMMENT '排序(数字越小越靠前)',
  `store_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商城ID',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`role_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10005 DEFAULT CHARSET=utf8 COMMENT='商家用户角色表';

-- ----------------------------
-- Records of tao_store_role
-- ----------------------------
INSERT INTO `tao_store_role` VALUES ('10004', '销售经理', '0', '100', '10001', '1779783106', '1779957646');

-- ----------------------------
-- Table structure for tao_store_role_menu
-- ----------------------------
DROP TABLE IF EXISTS `tao_store_role_menu`;
CREATE TABLE `tao_store_role_menu` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `role_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户角色ID',
  `menu_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '菜单ID',
  `store_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商城ID',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `role_id` (`role_id`),
  KEY `store_id` (`store_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11363 DEFAULT CHARSET=utf8 COMMENT='商家后台用户角色与菜单权限关系表';

-- ----------------------------
-- Records of tao_store_role_menu
-- ----------------------------
INSERT INTO `tao_store_role_menu` VALUES ('10350', '1', '10300', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10351', '1', '10301', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10352', '1', '10302', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10353', '1', '10303', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10354', '1', '10304', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10355', '1', '10305', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10356', '1', '10306', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10357', '1', '10307', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10358', '1', '10308', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10359', '1', '10309', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10360', '1', '10310', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10361', '1', '10311', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10362', '1', '10312', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10363', '1', '10313', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10364', '1', '10314', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10365', '1', '10315', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10366', '1', '10316', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10367', '1', '10317', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10368', '1', '10318', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10369', '1', '10319', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10370', '1', '10320', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10371', '1', '10321', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10372', '1', '10322', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10373', '1', '10323', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10374', '1', '10324', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10375', '1', '10325', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10376', '1', '10326', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10377', '1', '10500', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10378', '1', '10501', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10379', '1', '10502', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10380', '1', '10503', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10381', '1', '10504', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10382', '1', '10505', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10383', '1', '10506', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10384', '1', '10507', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10385', '1', '10508', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10386', '1', '10509', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10387', '1', '10510', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10388', '1', '10511', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10389', '1', '10512', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10390', '1', '10513', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10391', '1', '10514', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10392', '1', '10515', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10393', '1', '10516', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10394', '1', '10517', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10395', '1', '10518', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10396', '1', '10519', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10397', '1', '10520', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10398', '1', '10521', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10399', '1', '10522', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10400', '1', '10523', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10401', '1', '10524', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10402', '1', '10525', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10403', '1', '10526', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10404', '1', '10527', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10405', '1', '10528', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10406', '1', '10529', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10407', '1', '10530', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10408', '1', '10531', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10409', '1', '10532', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10410', '1', '10533', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10411', '1', '10534', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10412', '1', '10535', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10413', '1', '10536', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10414', '1', '10537', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10415', '1', '10538', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10416', '1', '10539', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10417', '1', '10540', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10418', '1', '10541', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10419', '1', '10542', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10420', '1', '10543', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10421', '1', '10544', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10422', '1', '10545', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10423', '1', '10546', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10424', '1', '10547', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10425', '1', '10548', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10426', '1', '10549', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10427', '1', '10550', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10428', '1', '10551', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10429', '1', '10552', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10487', '1', '10553', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10489', '1', '10554', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10491', '1', '10555', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10493', '1', '10556', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10495', '1', '10557', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10497', '1', '10558', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10499', '1', '10317', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10501', '1', '10318', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10503', '1', '10559', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10505', '1', '10560', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10507', '1', '10561', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10509', '1', '10562', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10511', '1', '10563', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10513', '1', '10564', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10515', '1', '10565', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10517', '1', '10566', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10519', '1', '10567', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10521', '1', '10568', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10523', '1', '10559', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10525', '1', '10560', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10527', '1', '10561', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10529', '1', '10562', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10531', '1', '10563', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10533', '1', '10564', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10535', '1', '10565', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10536', '1', '10566', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10537', '1', '10567', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10538', '1', '10568', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10558', '1', '10700', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10564', '1', '10700', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10580', '1', '10701', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10581', '1', '10710', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10582', '1', '10702', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10583', '1', '10703', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10584', '1', '10711', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10585', '1', '10712', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10586', '1', '10721', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10587', '1', '10722', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10588', '1', '10723', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10589', '1', '10731', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10590', '1', '10732', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10591', '1', '10741', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10592', '1', '10742', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10593', '1', '20001', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10594', '1', '20002', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10595', '1', '20010', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10596', '1', '20011', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10597', '1', '20012', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10598', '1', '20013', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10599', '1', '20014', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10600', '1', '20015', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10601', '1', '20017', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10602', '1', '20018', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10603', '1', '20019', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10604', '1', '20020', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10605', '1', '20021', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10606', '1', '20022', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10607', '1', '20023', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10608', '1', '20024', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10609', '1', '20025', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10610', '1', '20026', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10611', '1', '20027', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10612', '1', '20028', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10613', '1', '20029', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10614', '1', '20030', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10615', '1', '20031', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10616', '1', '20032', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10617', '1', '20033', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10618', '1', '20034', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10619', '1', '20035', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10620', '1', '20036', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10621', '1', '20037', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10622', '1', '20038', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10623', '1', '20040', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10624', '1', '20041', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10625', '1', '20042', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10626', '1', '20043', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10627', '1', '20044', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10628', '1', '20045', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10629', '1', '20046', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10630', '1', '20050', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10631', '1', '20051', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10632', '1', '20052', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10633', '1', '20053', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10634', '1', '20054', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10635', '1', '20055', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10636', '1', '20060', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10637', '1', '20061', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10638', '1', '20062', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10639', '1', '20063', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10640', '1', '20064', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10641', '1', '20070', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10642', '1', '20071', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10643', '1', '20072', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10644', '1', '20073', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10645', '1', '20074', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10646', '1', '20080', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10647', '1', '20081', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10648', '1', '20082', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10649', '1', '20083', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10650', '1', '20084', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10651', '1', '20085', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10652', '1', '20086', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10653', '1', '20087', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10654', '1', '20088', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10655', '1', '20089', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10656', '1', '20090', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10657', '1', '20091', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10658', '1', '20092', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10659', '1', '20093', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10660', '1', '20094', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('10661', '1', '20095', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('11325', '10004', '20001', '10001', '1781159896');
INSERT INTO `tao_store_role_menu` VALUES ('11326', '10004', '20002', '10001', '1781159896');
INSERT INTO `tao_store_role_menu` VALUES ('11327', '10004', '20011', '10001', '1781159904');
INSERT INTO `tao_store_role_menu` VALUES ('11328', '10004', '20010', '10001', '1781159904');
INSERT INTO `tao_store_role_menu` VALUES ('11329', '10004', '20018', '10001', '1781159954');
INSERT INTO `tao_store_role_menu` VALUES ('11330', '10004', '20012', '10001', '1781159954');
INSERT INTO `tao_store_role_menu` VALUES ('11331', '10004', '20019', '10001', '1781159954');
INSERT INTO `tao_store_role_menu` VALUES ('11332', '10004', '20021', '10001', '1781159954');
INSERT INTO `tao_store_role_menu` VALUES ('11333', '10004', '20022', '10001', '1781159954');
INSERT INTO `tao_store_role_menu` VALUES ('11334', '10004', '20031', '10001', '1781160225');
INSERT INTO `tao_store_role_menu` VALUES ('11335', '10004', '20033', '10001', '1781160225');
INSERT INTO `tao_store_role_menu` VALUES ('11336', '10004', '20034', '10001', '1781160225');
INSERT INTO `tao_store_role_menu` VALUES ('11337', '10004', '20030', '10001', '1781160225');
INSERT INTO `tao_store_role_menu` VALUES ('11338', '10004', '20060', '10001', '1781160244');
INSERT INTO `tao_store_role_menu` VALUES ('11339', '10004', '20061', '10001', '1781160244');
INSERT INTO `tao_store_role_menu` VALUES ('11340', '10004', '20062', '10001', '1781160244');
INSERT INTO `tao_store_role_menu` VALUES ('11341', '10004', '20063', '10001', '1781160244');
INSERT INTO `tao_store_role_menu` VALUES ('11342', '10004', '20064', '10001', '1781160244');
INSERT INTO `tao_store_role_menu` VALUES ('11343', '10004', '20032', '10001', '1781160322');
INSERT INTO `tao_store_role_menu` VALUES ('11344', '10004', '20036', '10001', '1781160322');
INSERT INTO `tao_store_role_menu` VALUES ('11345', '10004', '20037', '10001', '1781160322');
INSERT INTO `tao_store_role_menu` VALUES ('11346', '10004', '20038', '10001', '1781160322');
INSERT INTO `tao_store_role_menu` VALUES ('11347', '1', '20016', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('11348', '1', '20065', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('11350', '10004', '20016', '10001', '0');
INSERT INTO `tao_store_role_menu` VALUES ('11351', '10004', '20065', '10001', '0');
INSERT INTO `tao_store_role_menu` VALUES ('11353', '1', '20039', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('11354', '1', '20047', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('11355', '1', '20048', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('11356', '10004', '20048', '10001', '1781160546');
INSERT INTO `tao_store_role_menu` VALUES ('11357', '10004', '20047', '10001', '1781160546');
INSERT INTO `tao_store_role_menu` VALUES ('11358', '10004', '20039', '10001', '1781160546');
INSERT INTO `tao_store_role_menu` VALUES ('11359', '10004', '20035', '10001', '1781160568');
INSERT INTO `tao_store_role_menu` VALUES ('11360', '1', '19901', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('11361', '1', '19901', '0', '0');
INSERT INTO `tao_store_role_menu` VALUES ('11362', '1', '19901', '0', '0');

-- ----------------------------
-- Table structure for tao_store_user
-- ----------------------------
DROP TABLE IF EXISTS `tao_store_user`;
CREATE TABLE `tao_store_user` (
  `store_user_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `user_name` varchar(255) NOT NULL DEFAULT '' COMMENT '用户名',
  `wxwork_userid` varchar(64) DEFAULT NULL COMMENT '企业微信用户ID',
  `wxwork_openid` varchar(128) DEFAULT NULL COMMENT '企业微信OpenID',
  `wxwork_avatar` varchar(255) DEFAULT NULL COMMENT '企业微信头像',
  `wxwork_department` varchar(255) DEFAULT NULL COMMENT '企业微信部门',
  `login_type` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '登录方式:1=账号密码,2=企业微信',
  `password` varchar(255) NOT NULL DEFAULT '' COMMENT '登录密码',
  `real_name` varchar(255) NOT NULL DEFAULT '' COMMENT '姓名',
  `mobile` varchar(20) DEFAULT NULL COMMENT '手机号',
  `gender` tinyint(1) DEFAULT '0' COMMENT '性别 0=未知 1=男 2=女',
  `birthday` date DEFAULT NULL COMMENT '生日',
  `avatar_url` varchar(255) DEFAULT NULL COMMENT '头像',
  `is_super` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '是否为超级管理员',
  `is_delete` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否删除',
  `sort` int(11) unsigned NOT NULL DEFAULT '100' COMMENT '排序(数字越小越靠前)',
  `store_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商城ID',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `supplier_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '关联供应商ID(0=非供应商)',
  `department_id` int(11) DEFAULT NULL COMMENT '所属部门ID',
  `is_supplier` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否为供应商用户(0否 1是)',
  PRIMARY KEY (`store_user_id`),
  KEY `user_name` (`user_name`),
  KEY `store_id` (`store_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10073 DEFAULT CHARSET=utf8 COMMENT='商家用户记录表';

-- ----------------------------
-- Records of tao_store_user
-- ----------------------------
INSERT INTO `tao_store_user` VALUES ('10001', 'admin', null, null, null, null, '1', '$2y$10$HYHjzTcgwE2Xvs7hxhPwYes8hTm/K.XfR7077w9HndqUwDyCz1xBG', '系统管理员', null, '0', null, null, '1', '0', '100', '10001', '1614556800', '1781423941', '0', null, '0');

-- ----------------------------
-- Table structure for tao_store_user_department
-- ----------------------------
DROP TABLE IF EXISTS `tao_store_user_department`;
CREATE TABLE `tao_store_user_department` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '关联ID',
  `store_id` int(11) NOT NULL DEFAULT '0' COMMENT '租户ID',
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户ID，关联tao_store_user表',
  `department_id` int(11) NOT NULL DEFAULT '0' COMMENT '部门ID，关联tao_store_department表',
  `is_manager` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否为部门主管：1=是，0=否',
  `wxwork_department_id` int(11) DEFAULT NULL COMMENT '企业微信部门ID',
  `wxwork_userid` varchar(64) DEFAULT NULL COMMENT '企业微信用户ID',
  `sync_status` tinyint(1) DEFAULT '0' COMMENT '同步状态：0=未同步，1=已同步，2=同步失败',
  `sync_error` varchar(500) DEFAULT NULL COMMENT '最后一次同步错误信息',
  `sync_time` int(11) DEFAULT NULL COMMENT '最后同步时间',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_store_user` (`store_id`,`user_id`),
  KEY `idx_store_id` (`store_id`),
  KEY `idx_department_id` (`department_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_wxwork_userid` (`wxwork_userid`),
  KEY `idx_sync_status` (`sync_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='员工部门关联表';

-- ----------------------------
-- Records of tao_store_user_department
-- ----------------------------

-- ----------------------------
-- Table structure for tao_store_user_role
-- ----------------------------
DROP TABLE IF EXISTS `tao_store_user_role`;
CREATE TABLE `tao_store_user_role` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `store_user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '超管用户ID',
  `role_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '角色ID',
  `store_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商城ID',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `store_user_id` (`store_user_id`) USING BTREE,
  KEY `role_id` (`role_id`),
  KEY `store_id` (`store_id`)
) ENGINE=InnoDB AUTO_INCREMENT=71 DEFAULT CHARSET=utf8 COMMENT='商家用户角色记录表';

-- ----------------------------
-- Records of tao_store_user_role
-- ----------------------------
INSERT INTO `tao_store_user_role` VALUES ('13', '10014', '10001', '10001', '0');
INSERT INTO `tao_store_user_role` VALUES ('14', '10015', '10001', '10001', '0');
INSERT INTO `tao_store_user_role` VALUES ('15', '10016', '10001', '10001', '0');
INSERT INTO `tao_store_user_role` VALUES ('16', '10017', '10001', '10001', '0');
INSERT INTO `tao_store_user_role` VALUES ('17', '10018', '10001', '10001', '0');
INSERT INTO `tao_store_user_role` VALUES ('18', '10019', '10001', '10001', '0');
INSERT INTO `tao_store_user_role` VALUES ('19', '10020', '10001', '10001', '0');
INSERT INTO `tao_store_user_role` VALUES ('20', '10021', '10001', '10001', '0');
INSERT INTO `tao_store_user_role` VALUES ('21', '10022', '10001', '10001', '0');
INSERT INTO `tao_store_user_role` VALUES ('22', '10023', '10001', '10001', '0');
INSERT INTO `tao_store_user_role` VALUES ('23', '10024', '10001', '10001', '0');
INSERT INTO `tao_store_user_role` VALUES ('24', '10025', '10001', '10001', '0');
INSERT INTO `tao_store_user_role` VALUES ('25', '10026', '10001', '10001', '0');
INSERT INTO `tao_store_user_role` VALUES ('26', '10027', '10001', '10001', '0');
INSERT INTO `tao_store_user_role` VALUES ('27', '10028', '10001', '10001', '0');
INSERT INTO `tao_store_user_role` VALUES ('28', '10029', '10001', '10001', '0');
INSERT INTO `tao_store_user_role` VALUES ('29', '10030', '10001', '10001', '0');
INSERT INTO `tao_store_user_role` VALUES ('30', '10031', '10001', '10001', '0');
INSERT INTO `tao_store_user_role` VALUES ('31', '10032', '10001', '10001', '0');
INSERT INTO `tao_store_user_role` VALUES ('32', '10033', '10001', '10001', '0');
INSERT INTO `tao_store_user_role` VALUES ('33', '10034', '10001', '10001', '0');
INSERT INTO `tao_store_user_role` VALUES ('34', '10035', '10001', '10001', '0');
INSERT INTO `tao_store_user_role` VALUES ('35', '10036', '10001', '10001', '0');
INSERT INTO `tao_store_user_role` VALUES ('36', '10037', '10001', '10001', '0');
INSERT INTO `tao_store_user_role` VALUES ('37', '10038', '10001', '10001', '0');
INSERT INTO `tao_store_user_role` VALUES ('38', '10039', '10001', '10001', '0');
INSERT INTO `tao_store_user_role` VALUES ('39', '10040', '10001', '10001', '0');
INSERT INTO `tao_store_user_role` VALUES ('40', '10041', '10001', '10001', '0');
INSERT INTO `tao_store_user_role` VALUES ('41', '10042', '10001', '10001', '0');
INSERT INTO `tao_store_user_role` VALUES ('42', '10043', '10001', '10001', '0');
INSERT INTO `tao_store_user_role` VALUES ('43', '10044', '10001', '10001', '0');
INSERT INTO `tao_store_user_role` VALUES ('44', '10045', '10001', '10001', '0');
INSERT INTO `tao_store_user_role` VALUES ('45', '10046', '10001', '10001', '0');
INSERT INTO `tao_store_user_role` VALUES ('46', '10047', '10001', '10001', '0');
INSERT INTO `tao_store_user_role` VALUES ('47', '10048', '10001', '10001', '0');
INSERT INTO `tao_store_user_role` VALUES ('48', '10049', '10001', '10001', '0');
INSERT INTO `tao_store_user_role` VALUES ('49', '10050', '10001', '10001', '0');
INSERT INTO `tao_store_user_role` VALUES ('50', '10051', '10001', '10001', '0');
INSERT INTO `tao_store_user_role` VALUES ('54', '10055', '10001', '10001', '0');
INSERT INTO `tao_store_user_role` VALUES ('55', '10056', '10001', '10001', '0');
INSERT INTO `tao_store_user_role` VALUES ('56', '10057', '10001', '10001', '0');
INSERT INTO `tao_store_user_role` VALUES ('57', '10058', '10001', '10001', '0');
INSERT INTO `tao_store_user_role` VALUES ('58', '10059', '10001', '10001', '0');
INSERT INTO `tao_store_user_role` VALUES ('59', '10060', '10001', '10001', '0');
INSERT INTO `tao_store_user_role` VALUES ('60', '10061', '10001', '10001', '0');
INSERT INTO `tao_store_user_role` VALUES ('61', '10062', '10001', '10001', '0');
INSERT INTO `tao_store_user_role` VALUES ('62', '10063', '10001', '10001', '0');
INSERT INTO `tao_store_user_role` VALUES ('63', '10064', '10001', '10001', '0');
INSERT INTO `tao_store_user_role` VALUES ('64', '10065', '10001', '10001', '0');
INSERT INTO `tao_store_user_role` VALUES ('65', '10066', '10001', '10001', '0');
INSERT INTO `tao_store_user_role` VALUES ('66', '10067', '10001', '10001', '0');
INSERT INTO `tao_store_user_role` VALUES ('67', '10068', '10001', '10001', '0');
INSERT INTO `tao_store_user_role` VALUES ('70', '10071', '10004', '10001', '0');

-- ----------------------------
-- Table structure for tao_upload_file
-- ----------------------------
DROP TABLE IF EXISTS `tao_upload_file`;
CREATE TABLE `tao_upload_file` (
  `file_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '文件ID',
  `group_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '文件分组ID',
  `channel` tinyint(3) unsigned NOT NULL DEFAULT '10' COMMENT '上传来源(10商户后台 20用户端)',
  `storage` varchar(10) NOT NULL DEFAULT '' COMMENT '存储方式',
  `domain` varchar(255) NOT NULL DEFAULT '' COMMENT '存储域名',
  `file_type` tinyint(3) unsigned NOT NULL DEFAULT '10' COMMENT '文件类型(10图片 20附件 30视频)',
  `file_name` varchar(255) NOT NULL DEFAULT '' COMMENT '文件名称(仅显示)',
  `file_path` varchar(255) NOT NULL DEFAULT '' COMMENT '文件路径',
  `file_size` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '文件大小(字节)',
  `file_ext` varchar(20) NOT NULL DEFAULT '' COMMENT '文件扩展名',
  `cover` varchar(255) NOT NULL DEFAULT '' COMMENT '文件封面',
  `uploader_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '上传者用户ID',
  `is_recycle` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否在回收站',
  `is_delete` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否删除',
  `store_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商城ID',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`file_id`),
  KEY `group_id` (`group_id`),
  KEY `is_recycle` (`is_recycle`),
  KEY `store_id` (`store_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10038 DEFAULT CHARSET=utf8 COMMENT='文件库记录表';

-- ----------------------------
-- Records of tao_upload_file
-- ----------------------------
INSERT INTO `tao_upload_file` VALUES ('10037', '0', '10', 'local', '', '10', '5aebb2f7-5992-4653-8a99-83566beff16e.jpg', '10001/20260614/e5d14e627be91b2413aaa6923eec66e3.jpg', '69185', 'jpg', '', '0', '0', '0', '10001', '1781409287', '1781409287');

-- ----------------------------
-- Table structure for tao_upload_group
-- ----------------------------
DROP TABLE IF EXISTS `tao_upload_group`;
CREATE TABLE `tao_upload_group` (
  `group_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '分组ID',
  `name` varchar(30) NOT NULL DEFAULT '' COMMENT '分组名称',
  `parent_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '上级分组ID',
  `sort` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '排序(数字越小越靠前)',
  `is_delete` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否删除',
  `store_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商城ID',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`group_id`),
  KEY `store_id` (`store_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COMMENT='文件库分组记录表';

-- ----------------------------
-- Records of tao_upload_group
-- ----------------------------
INSERT INTO `tao_upload_group` VALUES ('1', 'abc', '0', '100', '0', '10001', '1780883340', '1780883340');

-- ----------------------------
-- Table structure for tao_user
-- ----------------------------
DROP TABLE IF EXISTS `tao_user`;
CREATE TABLE `tao_user` (
  `user_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '用户ID',
  `mobile` varchar(20) NOT NULL DEFAULT '' COMMENT '用户手机号',
  `nick_name` varchar(255) NOT NULL DEFAULT '' COMMENT '用户昵称',
  `remark_name` varchar(255) NOT NULL DEFAULT '' COMMENT '备注名称(如公司名称)',
  `avatar_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '头像文件ID',
  `gender` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '性别',
  `country` varchar(50) NOT NULL DEFAULT '' COMMENT '国家',
  `province` varchar(50) NOT NULL DEFAULT '' COMMENT '省份',
  `city` varchar(50) NOT NULL DEFAULT '' COMMENT '城市',
  `address_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '默认收货地址',
  `balance` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '用户可用余额',
  `points` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户可用积分',
  `pay_money` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '用户总支付的金额',
  `expend_money` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '实际消费的金额(不含退款)',
  `grade_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '会员等级ID',
  `platform` varchar(20) NOT NULL DEFAULT '' COMMENT '注册来源客户端 (APP、H5、小程序等)',
  `last_login_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '最后登录时间',
  `is_delete` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否删除',
  `role` tinyint(1) DEFAULT '0' COMMENT '用户角色：0-普通用户，1-已认证企业，2-服务商，3-双重身份',
  `enterprise_auth_status` tinyint(1) DEFAULT '0' COMMENT '企业认证状态：0-未认证，1-待审核，2-已认证，3-拒绝',
  `is_service_provider` tinyint(1) DEFAULT '0' COMMENT '是否服务商：0-否，1-是',
  `store_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商城ID',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `subsidy_balance` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '补贴余额',
  PRIMARY KEY (`user_id`),
  KEY `mobile` (`mobile`),
  KEY `store_id` (`store_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10007 DEFAULT CHARSET=utf8 COMMENT='用户记录表';

-- ----------------------------
-- Records of tao_user
-- ----------------------------
