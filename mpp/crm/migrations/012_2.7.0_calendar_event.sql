-- 自定义日历事件
CREATE TABLE IF NOT EXISTS `yoshop_crm_calendar_event` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `store_id` int(11) NOT NULL DEFAULT '0',
  `title` varchar(200) NOT NULL DEFAULT '' COMMENT '事件名称',
  `event_date` date DEFAULT NULL COMMENT '事件日期',
  `end_date` date DEFAULT NULL COMMENT '重复截止日期',
  `repeat_type` varchar(20) NOT NULL DEFAULT 'none' COMMENT 'none/daily/weekly/monthly/yearly',
  `repeat_rule` varchar(500) DEFAULT NULL COMMENT 'JSON: {"interval":1,"days":[1,3,5],"day":15,"month":7}',
  `color` varchar(20) NOT NULL DEFAULT 'blue' COMMENT 'blue/green/orange/purple/pink/cyan/red',
  `content` text COMMENT '备注',
  `customer_id` int(11) NOT NULL DEFAULT '0' COMMENT '关联客户',
  `owner_user_id` int(11) NOT NULL DEFAULT '0',
  `is_delete` tinyint(1) NOT NULL DEFAULT '0',
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_store_id` (`store_id`),
  KEY `idx_customer_id` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='CRM自定义日历事件';
