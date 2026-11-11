-- ============================================================
-- 开源版：闭源 skill 的最小表结构
--
-- 本文件只在开源仓库存在（由 sync/opensource/extra 提供），私有仓库没有。
-- CRM 的项目/交付和「API 密钥」功能会直接读写这些表；
-- 不建表会导到 CRM 的 ALTER 迁移失败、页面报错。
--
-- 只建表，不插入任何菜单/权限 —— 闭源 skill 的菜单会在 999 迁移里清掉。
-- ============================================================

-- =============================================
-- 工单 skill — 完整建表 + 菜单（唯一一个迁移文件）
--
-- 本文件由原先的 001/002/003/004 四个迁移合并而成，内容等价。
-- 新增变更请另起 002_x.y.z_*.sql，不要改本文件（已执行过的文件不会被重跑，
-- 因为 SkillLoader 的幂等键是 (skill_name, version)，改了也不会生效）。
--
-- 全部语句幂等，可重复执行：
--   建表   CREATE TABLE IF NOT EXISTS
--   加列   information_schema 判断 + PREPARE 守卫
--   菜单   INSERT IGNORE / NOT EXISTS
-- 全程只有 INSERT，没有 DELETE / UPDATE。
--
-- 表前缀 yoshop_（Db::name 传裸表名时前缀由框架补；SQL 里要写全名）。
-- 菜单段与 migrations/manual/reapply_menu.sql 保持一致。
-- =============================================


-- =============================================
-- 1. 工单主表
-- =============================================
CREATE TABLE IF NOT EXISTS `yoshop_workorder_ticket` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `store_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '租户ID',
  `ticket_no` varchar(50) NOT NULL DEFAULT '' COMMENT '工单号 WO-YYYYMMDD-NNN',
  `type_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '类型ID 对应 yoshop_workorder_type.id',
  `type_name` varchar(100) NOT NULL DEFAULT '' COMMENT '类型名称 建单时快照一份',
  `title` varchar(200) NOT NULL DEFAULT '' COMMENT '工单标题',
  `content` text COMMENT '需求说明',
  `customer_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '关联客户 crm_customer.id',
  `user_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '提交会员 yoshop_user.user_id',
  `submitter_type` tinyint(3) unsigned NOT NULL DEFAULT 2 COMMENT '提交人类型 1会员 2员工',
  `submitter_user_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '提交员工 store_user.store_user_id',
  `contact_name` varchar(50) NOT NULL DEFAULT '' COMMENT '联系人',
  `contact_mobile` varchar(30) NOT NULL DEFAULT '' COMMENT '联系电话',
  `priority` tinyint(3) unsigned NOT NULL DEFAULT 2 COMMENT '优先级 1低 2中 3高 4紧急',
  `expect_date` int(11) unsigned DEFAULT NULL COMMENT '期望完成日期 时间戳',
  `status` tinyint(3) unsigned NOT NULL DEFAULT 1 COMMENT '状态 1待处理 2处理中 3待补件 4已完成 5已取消',
  `assignee_user_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '处理人 store_user.store_user_id',
  `remark` varchar(500) NOT NULL DEFAULT '' COMMENT '后台备注',
  `finish_time` int(11) unsigned DEFAULT NULL COMMENT '完成时间',
  `is_delete` tinyint(3) unsigned NOT NULL DEFAULT 0 COMMENT '软删除',
  `create_time` int(11) unsigned NOT NULL DEFAULT 0,
  `update_time` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_store_no` (`store_id`,`ticket_no`),
  KEY `idx_store_status` (`store_id`,`status`,`is_delete`),
  KEY `idx_customer` (`store_id`,`customer_id`,`is_delete`),
  KEY `idx_assignee` (`store_id`,`assignee_user_id`,`status`),
  KEY `idx_user` (`store_id`,`user_id`),
  KEY `idx_type` (`store_id`,`type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='工单主表';


-- =============================================
-- 2. 工单材料项（建单时按类型的材料模板快照生成）
-- =============================================
CREATE TABLE IF NOT EXISTS `yoshop_workorder_item` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `store_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '租户ID',
  `ticket_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '工单ID',
  `item_key` varchar(50) NOT NULL DEFAULT '' COMMENT '材料标识',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '材料名称',
  `is_required` tinyint(3) unsigned NOT NULL DEFAULT 1 COMMENT '1必交 0选交',
  `source` tinyint(3) unsigned NOT NULL DEFAULT 1 COMMENT '来源 1模板带出 2建单时手工加',
  `status` tinyint(3) unsigned NOT NULL DEFAULT 0 COMMENT '0待提交 1已提交 2已通过 3已驳回',
  `remark` varchar(500) NOT NULL DEFAULT '' COMMENT '驳回原因或补件要求',
  `review_user_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '审核人',
  `review_time` int(11) unsigned DEFAULT NULL COMMENT '审核时间',
  `sort` int(11) NOT NULL DEFAULT 100 COMMENT '排序',
  `is_delete` tinyint(3) unsigned NOT NULL DEFAULT 0 COMMENT '软删除',
  `create_time` int(11) unsigned NOT NULL DEFAULT 0,
  `update_time` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ticket` (`store_id`,`ticket_id`),
  KEY `idx_status` (`store_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='工单材料项';

-- 老环境补 is_delete（本列原先由 002 单独加；新装时上面建表已含，这里是幂等守卫）
SET @exists_item_del = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='yoshop_workorder_item' AND COLUMN_NAME='is_delete');
SET @stmt_item_del = IF(@exists_item_del = 0, 'ALTER TABLE `yoshop_workorder_item` ADD COLUMN `is_delete` tinyint(3) unsigned NOT NULL DEFAULT 0 COMMENT ''软删除''', 'SELECT 1');
PREPARE s_item_del FROM @stmt_item_del;
EXECUTE s_item_del;
DEALLOCATE PREPARE s_item_del;


-- =============================================
-- 3. 工单附件（挂到材料项 或 挂到某条时间线回复）
--    一个材料项可挂多行 = 多文件；只存 file_id，文件本身统一由 upload_file 管
-- =============================================
CREATE TABLE IF NOT EXISTS `yoshop_workorder_file` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `store_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '租户ID',
  `ticket_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '工单ID',
  `item_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '材料项ID 0为非材料项附件',
  `log_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '时间线回复ID 0为非回复附件',
  `file_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT 'upload_file.file_id',
  `uploader_type` tinyint(3) unsigned NOT NULL DEFAULT 2 COMMENT '上传人类型 1会员 2员工',
  `uploader_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '会员ID或store_user_id',
  `is_delete` tinyint(3) unsigned NOT NULL DEFAULT 0 COMMENT '软删除',
  `create_time` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_ticket` (`store_id`,`ticket_id`,`is_delete`),
  KEY `idx_item` (`store_id`,`item_id`),
  KEY `idx_log` (`store_id`,`log_id`),
  KEY `idx_file` (`file_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='工单附件';


-- =============================================
-- 4. 工单材料模板（类型 → 该类型需要客户提交哪些材料，租户可配）
-- =============================================
CREATE TABLE IF NOT EXISTS `yoshop_workorder_material` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `store_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '租户ID',
  `type_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '类型ID 对应 yoshop_workorder_type.id',
  `item_key` varchar(50) NOT NULL DEFAULT '' COMMENT '材料标识',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '材料名称',
  `is_required` tinyint(3) unsigned NOT NULL DEFAULT 1 COMMENT '1必交 0选交',
  `remark` varchar(500) NOT NULL DEFAULT '' COMMENT '填写说明',
  `sort` int(11) NOT NULL DEFAULT 100 COMMENT '排序',
  `status` tinyint(3) unsigned NOT NULL DEFAULT 1 COMMENT '1启用 0停用',
  `is_delete` tinyint(3) unsigned NOT NULL DEFAULT 0 COMMENT '软删除',
  `create_time` int(11) unsigned NOT NULL DEFAULT 0,
  `update_time` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_store_type_item` (`store_id`,`type_id`,`item_key`),
  KEY `idx_type` (`store_id`,`type_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='工单材料模板';


-- =============================================
-- 5. 工单时间线
-- =============================================
CREATE TABLE IF NOT EXISTS `yoshop_workorder_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `store_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '租户ID',
  `ticket_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '工单ID',
  `action` varchar(30) NOT NULL DEFAULT '' COMMENT 'create/submit/reply/review/assign/status/item',
  `operator_type` tinyint(3) unsigned NOT NULL DEFAULT 2 COMMENT '操作人类型 1会员 2员工',
  `operator_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '操作人ID',
  `operator_name` varchar(50) NOT NULL DEFAULT '' COMMENT '操作人姓名',
  `content` text COMMENT '回复正文或变更说明',
  `status_from` tinyint(3) unsigned NOT NULL DEFAULT 0 COMMENT '变更前状态',
  `status_to` tinyint(3) unsigned NOT NULL DEFAULT 0 COMMENT '变更后状态',
  `create_time` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_ticket` (`store_id`,`ticket_id`),
  KEY `idx_action` (`store_id`,`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='工单时间线';


-- =============================================
-- 6. 工单类型（租户自定义，不在代码里写死）
-- =============================================
CREATE TABLE IF NOT EXISTS `yoshop_workorder_type` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `store_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '租户ID',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '类型名称',
  `code` varchar(50) NOT NULL DEFAULT '' COMMENT '英文标识 可选',
  `remark` varchar(500) NOT NULL DEFAULT '' COMMENT '说明',
  `sort` int(11) NOT NULL DEFAULT 100 COMMENT '排序 越小越前',
  `status` tinyint(3) unsigned NOT NULL DEFAULT 1 COMMENT '1启用 0停用',
  `is_delete` tinyint(3) unsigned NOT NULL DEFAULT 0 COMMENT '软删除',
  `create_time` int(11) unsigned NOT NULL DEFAULT 0,
  `update_time` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_store_name` (`store_id`,`name`),
  KEY `idx_store_status` (`store_id`,`status`,`is_delete`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='工单类型';


-- =============================================

-- ============================================================
-- opencrm：API 密钥表（CRM 设置 → API 密钥 使用）
-- ============================================================
CREATE TABLE IF NOT EXISTS `yoshop_crm_api_key` (
  `id` int NOT NULL AUTO_INCREMENT,
  `store_id` int NOT NULL COMMENT '租户ID',
  `store_user_id` int unsigned NOT NULL DEFAULT 0 COMMENT '绑定的商家用户ID(0=未绑定)',
  `name` varchar(100) DEFAULT '' COMMENT '备注',
  `api_key` varchar(64) NOT NULL COMMENT '密钥',
  `scopes` varchar(100) DEFAULT 'read' COMMENT 'read / write',
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '1启用 0停用',
  `last_used_at` int DEFAULT NULL,
  `create_time` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_key` (`api_key`),
  KEY `idx_store` (`store_id`),
  KEY `idx_store_user` (`store_id`, `store_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='CRM API 密钥';
