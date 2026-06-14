-- ==========================================
-- content-engine v1.0.0 初始安装
-- 首次部署用，建表 + 菜单 + 权限
-- ==========================================

-- 1. 公司档案表
CREATE TABLE IF NOT EXISTS `yoshop_crm_company_profile` (`id` int NOT NULL AUTO_INCREMENT, `store_id` int NOT NULL, `company_intro` text, `core_strengths` text, `tech_capability` text, `service_commitment` text, `contact_info` text, `contact_qrcode` varchar(255) DEFAULT '', `case_stories` text, `brand_voice` varchar(32) DEFAULT '专业', `create_time` int DEFAULT NULL, `update_time` int DEFAULT NULL, PRIMARY KEY (`id`), KEY `idx_store` (`store_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. 关键词表
CREATE TABLE IF NOT EXISTS `yoshop_crm_content_keyword` (`id` int NOT NULL AUTO_INCREMENT, `store_id` int NOT NULL, `keyword` varchar(100) NOT NULL, `type` tinyint DEFAULT 1, `search_volume` int DEFAULT 0, `difficulty` tinyint DEFAULT 0, `group_tag` varchar(32) DEFAULT '', `status` tinyint DEFAULT 1, `create_time` int DEFAULT NULL, `update_time` int DEFAULT NULL, PRIMARY KEY (`id`), KEY `idx_store_type` (`store_id`, `type`), KEY `idx_keyword` (`keyword`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. 解决方案表
CREATE TABLE IF NOT EXISTS `yoshop_crm_solution` (`id` int NOT NULL AUTO_INCREMENT, `store_id` int NOT NULL, `title` varchar(200) NOT NULL, `target_industry` varchar(100), `customer_pain_points` text, `products` text, `advantages` text, `customer_cases` text, `cover_image` varchar(255), `sort_order` int DEFAULT 0, `status` tinyint DEFAULT 1, `create_time` int DEFAULT NULL, `update_time` int DEFAULT NULL, PRIMARY KEY (`id`), KEY `idx_store` (`store_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. 选题表
CREATE TABLE IF NOT EXISTS `yoshop_crm_content_topic` (`id` int NOT NULL AUTO_INCREMENT, `store_id` int NOT NULL, `title` varchar(200) NOT NULL, `content_type` varchar(32) DEFAULT 'article', `source_type` varchar(32), `source_ref_id` int DEFAULT 0, `keywords` varchar(500), `angle` varchar(100), `status` tinyint DEFAULT 1, `priority` tinyint DEFAULT 3, `reviewer_uid` int DEFAULT 0, `creator_uid` int DEFAULT 0, `create_time` int DEFAULT NULL, `update_time` int DEFAULT NULL, PRIMARY KEY (`id`), KEY `idx_store_status` (`store_id`, `status`), KEY `idx_date` (`create_time`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. 内容产出表
CREATE TABLE IF NOT EXISTS `yoshop_crm_content_output` (`id` int NOT NULL AUTO_INCREMENT, `store_id` int NOT NULL, `topic_id` int NOT NULL, `format` varchar(16) NOT NULL, `title` varchar(300) NOT NULL, `content` longtext, `keywords_used` varchar(500), `solution_id` int DEFAULT 0, `word_count` int DEFAULT 0, `status` tinyint DEFAULT 1, `publish_url` varchar(500), `publish_time` int DEFAULT NULL, `views` int DEFAULT 0, `inquiries` int DEFAULT 0, `create_time` int DEFAULT NULL, `update_time` int DEFAULT NULL, PRIMARY KEY (`id`), KEY `idx_topic` (`topic_id`), KEY `idx_store_status` (`store_id`, `status`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. 菜单：内容创作（挂在知识库 10559 下）
INSERT INTO `yoshop_store_menu` (`menu_id`, `name`, `path`, `parent_id`, `module`, `fun_type`, `sort`, `create_time`, `update_time`) VALUES
(10700, '内容创作', '/crm/content', 10559, 10, 'crm', 50, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(10701, '公司档案', '/crm/content/profile', 10700, 10, 'crm', 100, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(10710, '关键词管理', '/crm/content/keywords', 10700, 10, 'crm', 110, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(10720, '解决方案库', '/crm/content/solutions', 10700, 10, 'crm', 120, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(10730, '选题池', '/crm/content/topics', 10700, 10, 'crm', 130, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(10750, '内容日历', '/crm/content/calendar', 10700, 10, 'crm', 140, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(10760, '内容效果', '/crm/content/analytics', 10700, 10, 'crm', 150, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 7. 操作权限（module=20）
INSERT INTO `yoshop_store_menu` (`menu_id`, `name`, `path`, `parent_id`, `module`, `fun_type`, `sort`, `action_mark`, `create_time`, `update_time`) VALUES
(10702, '编辑档案', '/crm.profile/edit', 10701, 20, 'crm', 100, 'edit', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(10703, '查看档案', '/crm.profile/detail', 10701, 20, 'crm', 110, 'detail', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(10711, '添加关键词', '/crm.keyword/add', 10710, 20, 'crm', 100, 'add', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(10712, '删除关键词', '/crm.keyword/delete', 10710, 20, 'crm', 110, 'delete', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(10721, '添加方案', '/crm.solution/add', 10720, 20, 'crm', 100, 'add', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(10722, '编辑方案', '/crm.solution/edit', 10720, 20, 'crm', 110, 'edit', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(10723, '删除方案', '/crm.solution/delete', 10720, 20, 'crm', 120, 'delete', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(10731, 'AI扫描选题', '/crm.topic/scan', 10730, 20, 'crm', 100, 'scan', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(10732, '审核选题', '/crm.topic/approve', 10730, 20, 'crm', 110, 'approve', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(10741, 'AI生成', '/crm.editor/generate', 10740, 20, 'crm', 100, 'generate', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(10742, '保存内容', '/crm.editor/save', 10740, 20, 'crm', 110, 'save', UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 8. 管理员角色绑定
INSERT IGNORE INTO `yoshop_store_role_menu` (`role_id`, `menu_id`) VALUES
(1,10700),(1,10701),(1,10702),(1,10703),
(1,10710),(1,10711),(1,10712),
(1,10720),(1,10721),(1,10722),(1,10723),
(1,10730),(1,10731),(1,10732),
(1,10750),(1,10760);
