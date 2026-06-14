-- ==========================================
-- content-engine v1.0.0 → v1.1.0 升级
-- 新增智能体系统 + 菜单简化为创作工坊/创作配置
-- ==========================================

-- 1. 新增表：写作风格
CREATE TABLE IF NOT EXISTS `yoshop_crm_content_style` (`id` int NOT NULL AUTO_INCREMENT, `store_id` int NOT NULL, `name` varchar(100) NOT NULL, `role_desc` varchar(200), `system_prompt` text, `forbidden_words` varchar(500) DEFAULT '', `tone` varchar(32) DEFAULT '专业', `create_time` int DEFAULT NULL, `update_time` int DEFAULT NULL, PRIMARY KEY (`id`), KEY `idx_store` (`store_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. 新增表：智能体
CREATE TABLE IF NOT EXISTS `yoshop_crm_content_agent` (`id` int NOT NULL AUTO_INCREMENT, `store_id` int NOT NULL, `name` varchar(100) NOT NULL, `agent_type` varchar(32) NOT NULL, `style_id` int DEFAULT 0, `preset_style` varchar(32) DEFAULT '', `audience_id` int DEFAULT 0, `allowed_vars` text, `instructions` text, `status` tinyint DEFAULT 1, `create_time` int DEFAULT NULL, `update_time` int DEFAULT NULL, PRIMARY KEY (`id`), KEY `idx_store_type` (`store_id`, `agent_type`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. 清理旧菜单（v1.0 的多个子菜单）
DELETE FROM `yoshop_store_role_menu` WHERE `menu_id` BETWEEN 10701 AND 10780;
DELETE FROM `yoshop_store_menu` WHERE `menu_id` BETWEEN 10701 AND 10780 AND `menu_id` NOT IN (10701, 10710);

-- 4. 更新现有菜单（10701 改名为创作工坊，10710 改名为创作配置）
UPDATE `yoshop_store_menu` SET `name` = '创作工坊', `path` = '/crm/content/create', `sort` = 100 WHERE `menu_id` = 10701;
UPDATE `yoshop_store_menu` SET `name` = '创作配置', `path` = '/crm/content/config', `sort` = 110 WHERE `menu_id` = 10710;
-- 如果 10701/10710 已被删除，重新插入
INSERT IGNORE INTO `yoshop_store_menu` (`menu_id`, `name`, `path`, `parent_id`, `module`, `fun_type`, `sort`, `create_time`, `update_time`) VALUES
(10701, '创作工坊', '/crm/content/create', 10700, 10, 'crm', 100, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(10710, '创作配置', '/crm/content/config', 10700, 10, 'crm', 110, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 5. 插入操作权限（如果不存在）
INSERT IGNORE INTO `yoshop_store_menu` (`menu_id`, `name`, `path`, `parent_id`, `module`, `fun_type`, `sort`, `action_mark`, `create_time`, `update_time`) VALUES
(10702, '编辑档案', '/crm.profile/edit', 10710, 20, 'crm', 100, 'edit', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(10703, '查看档案', '/crm.profile/detail', 10710, 20, 'crm', 110, 'detail', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(10711, '添加关键词', '/crm.keyword/add', 10710, 20, 'crm', 120, 'add', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(10712, '删除关键词', '/crm.keyword/delete', 10710, 20, 'crm', 130, 'delete', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(10721, '添加方案', '/crm.solution/add', 10710, 20, 'crm', 140, 'add', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(10722, '编辑方案', '/crm.solution/edit', 10710, 20, 'crm', 150, 'edit', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(10723, '删除方案', '/crm.solution/delete', 10710, 20, 'crm', 160, 'delete', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(10731, 'AI扫描选题', '/crm.topic/scan', 10701, 20, 'crm', 100, 'scan', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(10732, '审核选题', '/crm.topic/approve', 10701, 20, 'crm', 110, 'approve', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(10741, 'AI生成', '/crm.editor/generate', 10701, 20, 'crm', 120, 'generate', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(10742, '保存内容', '/crm.editor/save', 10701, 20, 'crm', 130, 'save', UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 6. 管理员角色绑定
INSERT IGNORE INTO `yoshop_store_role_menu` (`role_id`, `menu_id`) VALUES
(1,10701),(1,10710),
(1,10702),(1,10703),(1,10711),(1,10712),
(1,10721),(1,10722),(1,10723),
(1,10731),(1,10732),(1,10741),(1,10742);
