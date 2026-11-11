-- 合同产品明细子表
CREATE TABLE IF NOT EXISTS `yoshop_crm_contract_item` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `store_id` int(11) NOT NULL DEFAULT '0',
  `contract_id` int(11) NOT NULL DEFAULT '0' COMMENT '合同ID',
  `product_id` int(11) DEFAULT NULL COMMENT '产品ID',
  `product_name` varchar(200) NOT NULL DEFAULT '' COMMENT '产品名称',
  `specification` varchar(200) DEFAULT '' COMMENT '规格',
  `unit` varchar(20) DEFAULT '' COMMENT '单位',
  `quantity` decimal(12,2) DEFAULT '0.00' COMMENT '数量',
  `unit_price` decimal(12,2) DEFAULT '0.00' COMMENT '单价',
  `amount` decimal(12,2) DEFAULT '0.00' COMMENT '小计',
  `sort_order` int(11) DEFAULT '0' COMMENT '排序',
  `is_delete` tinyint(1) NOT NULL DEFAULT '0',
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_contract_id` (`contract_id`),
  KEY `idx_store_id` (`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='CRM合同产品明细';
