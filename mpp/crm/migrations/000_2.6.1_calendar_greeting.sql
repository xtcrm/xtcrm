-- CRM v0.5.0 — 日历祝福语记录表
CREATE TABLE IF NOT EXISTS `yoshop_crm_calendar_greeting` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `store_id` int(11) NOT NULL DEFAULT '0' COMMENT '租户ID',
  `event_type` varchar(20) NOT NULL DEFAULT '' COMMENT 'visit/birthday/established',
  `event_date` varchar(10) NOT NULL DEFAULT '' COMMENT '事件日期 YYYY-MM-DD',
  `target_id` varchar(50) NOT NULL DEFAULT '' COMMENT '关联ID',
  `target_name` varchar(200) DEFAULT '' COMMENT '客户名/联系人名',
  `context` varchar(500) DEFAULT '' COMMENT '生成上下文',
  `greeting` varchar(200) DEFAULT '' COMMENT 'AI祝福语',
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_event` (`store_id`, `event_type`, `event_date`, `target_id`),
  KEY `idx_store_date` (`store_id`, `event_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='日历祝福语记录';
