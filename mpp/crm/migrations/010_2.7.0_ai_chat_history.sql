-- =============================================
-- CRM v2.7.0 — AI 对话记录
-- =============================================

CREATE TABLE IF NOT EXISTS `yoshop_ai_chat_message` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `store_id` int unsigned NOT NULL DEFAULT 0 COMMENT '租户ID',
  `user_id` int unsigned NOT NULL DEFAULT 0 COMMENT '用户ID',
  `node_id` int unsigned NOT NULL DEFAULT 0 COMMENT '节点ID',
  `role` varchar(10) NOT NULL DEFAULT 'user' COMMENT 'user/ai',
  `content` text COMMENT '消息内容',
  `create_time` int unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_store_node` (`store_id`, `node_id`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI对话记录';

ALTER TABLE yoshop_ai_chat_message CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;