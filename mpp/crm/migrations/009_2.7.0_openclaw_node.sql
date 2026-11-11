-- =============================================
-- CRM v2.7.0 — OpenClaw 多节点管理
-- =============================================

-- 节点注册表
CREATE TABLE IF NOT EXISTS `yoshop_openclaw_node` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `store_id` int unsigned NOT NULL DEFAULT 0 COMMENT '租户ID',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '节点名称',
  `auth_token` varchar(64) NOT NULL DEFAULT '' COMMENT '认证Token',
  `gateway_url` varchar(255) NOT NULL DEFAULT '' COMMENT 'Gateway地址',
  `agent_id` varchar(50) NOT NULL DEFAULT 'main' COMMENT '默认Agent',
  `reg_token` varchar(64) NOT NULL DEFAULT '' COMMENT '注册Token(一次性24h过期)',
  `reg_token_expire` int unsigned NOT NULL DEFAULT 0 COMMENT '注册Token过期时间戳',
  `status` tinyint NOT NULL DEFAULT 0 COMMENT '0=待激活 1=在线 2=离线',
  `last_heartbeat` int unsigned NOT NULL DEFAULT 0 COMMENT '最后心跳',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` int unsigned NOT NULL DEFAULT 0,
  `update_time` int unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_store` (`store_id`),
  KEY `idx_token` (`auth_token`),
  KEY `idx_reg_token` (`reg_token`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='OpenClaw节点注册表';

-- ===== 菜单：AI 助手（一级菜单） =====
INSERT IGNORE INTO `yoshop_store_menu` (`menu_id`, `module`, `fun_type`, `name`, `path`, `action_mark`, `parent_id`, `sort`, `create_time`, `update_time`)
SELECT MAX(menu_id)+1, 10, 'crm', 'AI 助手', '/ai', '', 0, 90, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM yoshop_store_menu;

-- ===== OpenClaw 二级菜单 =====
SET @ai_id = (SELECT menu_id FROM yoshop_store_menu WHERE path='/ai' AND module=10 AND fun_type='crm' LIMIT 1);
INSERT IGNORE INTO `yoshop_store_menu` (`menu_id`, `module`, `fun_type`, `name`, `path`, `action_mark`, `parent_id`, `sort`, `create_time`, `update_time`)
SELECT MAX(menu_id)+1, 10, 'crm', 'OpenClaw', '/ai/openclaw', '', @ai_id, 100, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM yoshop_store_menu;

-- ===== OpenClaw 子页面 =====
SET @openclaw_id = (SELECT menu_id FROM yoshop_store_menu WHERE path='/ai/openclaw' AND module=10 LIMIT 1);
INSERT IGNORE INTO `yoshop_store_menu` (`menu_id`, `module`, `fun_type`, `name`, `path`, `action_mark`, `parent_id`, `sort`, `create_time`, `update_time`)
SELECT MAX(menu_id)+1, 10, 'crm', '控制台', '/ai/openclaw/index', '', @openclaw_id, 100, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM yoshop_store_menu;
INSERT IGNORE INTO `yoshop_store_menu` (`menu_id`, `module`, `fun_type`, `name`, `path`, `action_mark`, `parent_id`, `sort`, `create_time`, `update_time`)
SELECT MAX(menu_id)+1, 10, 'crm', '技能管理', '/ai/openclaw/skills', '', @openclaw_id, 110, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM yoshop_store_menu;
INSERT IGNORE INTO `yoshop_store_menu` (`menu_id`, `module`, `fun_type`, `name`, `path`, `action_mark`, `parent_id`, `sort`, `create_time`, `update_time`)
SELECT MAX(menu_id)+1, 10, 'crm', '设置', '/ai/openclaw/setting', '', @openclaw_id, 120, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM yoshop_store_menu;
INSERT IGNORE INTO `yoshop_store_menu` (`menu_id`, `module`, `fun_type`, `name`, `path`, `action_mark`, `parent_id`, `sort`, `create_time`, `update_time`)
SELECT MAX(menu_id)+1, 10, 'crm', '节点管理', '/ai/openclaw/nodes', '', @openclaw_id, 130, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM yoshop_store_menu;

-- ===== 节点管理操作项（module=20） =====
SET @nodes_id = (SELECT menu_id FROM yoshop_store_menu WHERE path='/ai/openclaw/nodes' AND module=10 LIMIT 1);
INSERT IGNORE INTO `yoshop_store_menu` (`menu_id`, `module`, `fun_type`, `name`, `path`, `action_mark`, `parent_id`, `sort`, `create_time`, `update_time`)
SELECT MAX(menu_id)+1, 20, 'crm', '新增节点', '/ai/openclaw/addNode', 'addNode', @nodes_id, 100, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM yoshop_store_menu;
INSERT IGNORE INTO `yoshop_store_menu` (`menu_id`, `module`, `fun_type`, `name`, `path`, `action_mark`, `parent_id`, `sort`, `create_time`, `update_time`)
SELECT MAX(menu_id)+1, 20, 'crm', '编辑节点', '/ai/openclaw/editNode', 'editNode', @nodes_id, 110, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM yoshop_store_menu;
INSERT IGNORE INTO `yoshop_store_menu` (`menu_id`, `module`, `fun_type`, `name`, `path`, `action_mark`, `parent_id`, `sort`, `create_time`, `update_time`)
SELECT MAX(menu_id)+1, 20, 'crm', '删除节点', '/ai/openclaw/deleteNode', 'deleteNode', @nodes_id, 120, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM yoshop_store_menu;
INSERT IGNORE INTO `yoshop_store_menu` (`menu_id`, `module`, `fun_type`, `name`, `path`, `action_mark`, `parent_id`, `sort`, `create_time`, `update_time`)
SELECT MAX(menu_id)+1, 20, 'crm', '生成脚本', '/ai/openclaw/generateScript', 'generateScript', @nodes_id, 130, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() FROM yoshop_store_menu;

-- ===== 绑定管理员角色 =====
INSERT IGNORE INTO `yoshop_store_role_menu` (`role_id`, `menu_id`, `store_id`)
SELECT 1, menu_id, 0 FROM `yoshop_store_menu` WHERE fun_type='crm' AND path LIKE '/ai%';
