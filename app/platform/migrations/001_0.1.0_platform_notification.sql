-- 平台站内通知表
CREATE TABLE IF NOT EXISTS `yoshop_platform_notification` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `store_id` int unsigned NOT NULL DEFAULT '0' COMMENT '租户ID',
  `user_id` int unsigned NOT NULL COMMENT '接收者（store_user_id）',
  `title` varchar(200) NOT NULL DEFAULT '' COMMENT '通知标题',
  `content` text COMMENT '通知内容',
  `type` varchar(50) NOT NULL DEFAULT 'system' COMMENT '通知类型（system/crm/fiscal/...）',
  `is_read` tinyint NOT NULL DEFAULT '0' COMMENT '是否已读 0=未读 1=已读',
  `source_module` varchar(50) NOT NULL DEFAULT '' COMMENT '来源模块',
  `source_id` int unsigned NOT NULL DEFAULT '0' COMMENT '来源记录ID',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_user_unread` (`user_id`, `is_read`),
  KEY `idx_store` (`store_id`),
  KEY `idx_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='平台站内通知表';
