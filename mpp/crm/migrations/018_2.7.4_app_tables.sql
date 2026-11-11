-- CRM v2.7.4 — AI 应用平台表

CREATE TABLE IF NOT EXISTS `yoshop_app_definition` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `app_id` varchar(100) NOT NULL COMMENT '应用标识',
  `store_id` int unsigned NOT NULL DEFAULT 0,
  `name` varchar(200) DEFAULT '',
  `spec` longtext COMMENT '应用Spec JSON',
  `status` tinyint DEFAULT 0 COMMENT '0=草稿 1=发布',
  `create_time` int unsigned DEFAULT 0,
  `update_time` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_app` (`app_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='AI应用定义';

CREATE TABLE IF NOT EXISTS `yoshop_app_page` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `app_id` varchar(100) NOT NULL COMMENT '应用标识',
  `page_key` varchar(100) NOT NULL COMMENT '页面标识',
  `page_name` varchar(100) DEFAULT '' COMMENT '页面名',
  `page_type` varchar(50) DEFAULT 'generated',
  `source_code` longtext COMMENT 'Vue源码',
  `create_time` int unsigned DEFAULT 0,
  `update_time` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_app` (`app_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='AI应用页面';
