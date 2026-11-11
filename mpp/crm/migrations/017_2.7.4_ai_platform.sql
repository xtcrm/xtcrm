-- =============================================
-- CRM v2.7.4 — AI 应用平台基础
-- =============================================

CREATE TABLE IF NOT EXISTS `yoshop_app_entity` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `app_id` varchar(100) NOT NULL COMMENT '应用标识',
  `entity_key` varchar(100) NOT NULL COMMENT '实体标识',
  `entity_name` varchar(100) NOT NULL COMMENT '实体中文名',
  `table_name` varchar(200) NOT NULL COMMENT '对应MySQL表名',
  `entity_schema` text COMMENT '字段定义JSON',
  `app_name` varchar(100) DEFAULT '' COMMENT '应用名称',
  `create_time` int unsigned NOT NULL DEFAULT 0,
  `update_time` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_entity` (`app_id`, `entity_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI应用实体定义';
