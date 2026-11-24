-- =============================================
-- CRM v2.14.0 — 客户触点（统一时间轴投影）
--
-- 1) 建 crm_touchpoint：所有角色交互记录的统一读模型
-- 2) 回填 crm_event_log 里的客户事件
-- 3) 回填 crm_customer_claim_log 的系统掉公海记录（action=1）
--
-- 业务表仍是真相源；触点可从源表重建。
-- 唯一键 (store_id, source_type, source_id, event_type, dedup_key) 保证重跑不重复。
-- =============================================

CREATE TABLE IF NOT EXISTS `yoshop_crm_touchpoint` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `store_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '租户',
  `customer_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '客户ID',
  `contact_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '联系人ID，0=未指定',
  `project_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '项目ID，0=未关联',
  `category` varchar(20) NOT NULL DEFAULT 'system' COMMENT 'sales/service/aftersales/tech/finance/presales/delivery/marketing/system',
  `source_type` varchar(30) NOT NULL DEFAULT '' COMMENT '来源实体',
  `source_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '来源主键',
  `event_type` varchar(50) NOT NULL DEFAULT '' COMMENT 'created/assigned/status_changed等',
  `title` varchar(200) NOT NULL DEFAULT '' COMMENT '中文标题',
  `summary` varchar(500) NOT NULL DEFAULT '' COMMENT '一句话补充',
  `detail` json DEFAULT NULL COMMENT '源表快照/专有字段',
  `action_url` varchar(255) NOT NULL DEFAULT '' COMMENT '点回源单据',
  `occurred_at` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '业务时间',
  `create_time` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '入库时间',
  `actor_user_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '操作人',
  `owner_user_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '归属人快照',
  `dept_id` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '部门快照',
  `visibility` tinyint(3) unsigned NOT NULL DEFAULT 1 COMMENT '1内部 2团队 3部门 4管理层 5私有',
  `tags` varchar(255) NOT NULL DEFAULT '' COMMENT '根因/兴趣标签',
  `severity` tinyint(3) unsigned NOT NULL DEFAULT 0 COMMENT '0普通 1重要 2紧急',
  `csat` tinyint(3) unsigned NOT NULL DEFAULT 0 COMMENT '满意度0-5',
  `voided_at` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '作废时间，非0时时间轴不显示',
  `dedup_key` varchar(64) NOT NULL DEFAULT '' COMMENT '幂等键',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_source` (`store_id`,`source_type`,`source_id`,`event_type`,`dedup_key`),
  KEY `idx_customer_time` (`store_id`,`customer_id`,`occurred_at`),
  KEY `idx_category_time` (`store_id`,`category`,`occurred_at`),
  KEY `idx_source` (`store_id`,`source_type`,`source_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='客户触点（统一时间轴投影）';

-- ------------------------------------------------------------
-- 回填：客户事件
-- ------------------------------------------------------------
INSERT IGNORE INTO `yoshop_crm_touchpoint`
(`store_id`,`customer_id`,`contact_id`,`project_id`,`category`,`source_type`,`source_id`,`event_type`,
 `title`,`summary`,`detail`,`action_url`,`occurred_at`,`create_time`,`actor_user_id`,`owner_user_id`,
 `dept_id`,`visibility`,`tags`,`severity`,`csat`,`voided_at`,`dedup_key`)
SELECT
  e.`store_id`,
  e.`target_id`,
  0,
  e.`project_id`,
  CASE
    WHEN e.`event_type` LIKE 'payment%' THEN 'finance'
    WHEN e.`event_type` IN ('customer_created','customer_updated','customer_deleted','customer_claimed','customer_released','customer_transferred','customer_cs_assigned','collaborator_added','collaborator_removed') THEN 'system'
    WHEN e.`event_type` LIKE 'follow_up%' OR e.`event_type` LIKE 'contact%' OR e.`event_type` LIKE 'document%'
      OR e.`event_type` LIKE 'customer_tags%' OR e.`event_type` LIKE 'quotation%'
      OR e.`event_type` LIKE 'order%' OR e.`event_type` LIKE 'contract%' THEN 'sales'
    ELSE 'system'
  END,
  'event_log',
  e.`id`,
  e.`event_type`,
  COALESCE(
    NULLIF(JSON_UNQUOTE(JSON_EXTRACT(e.`data`, '$.title')), ''),
    CASE e.`event_type`
      WHEN 'customer_created' THEN '创建客户'
      WHEN 'customer_claimed' THEN '认领客户'
      WHEN 'customer_released' THEN '释放到公海'
      WHEN 'customer_deleted' THEN '删除客户'
      WHEN 'customer_transferred' THEN CONCAT('转让负责人：',
        COALESCE(NULLIF(fu.`real_name`, ''), fu.`user_name`, '未分配'), ' → ',
        COALESCE(NULLIF(tu.`real_name`, ''), tu.`user_name`, '未分配'))
      WHEN 'customer_cs_assigned' THEN CONCAT('客服责任人：',
        COALESCE(NULLIF(cfu.`real_name`, ''), cfu.`user_name`, '未分配'), ' → ',
        COALESCE(NULLIF(ctu.`real_name`, ''), ctu.`user_name`, '未分配'))
      WHEN 'customer_updated' THEN '编辑客户资料'
      WHEN 'follow_up_completed' THEN '新增跟进'
      ELSE e.`event_type`
    END
  ),
  '',
  e.`data`,
  '',
  e.`create_time`,
  e.`create_time`,
  COALESCE(e.`actor_user_id`, 0),
  0,
  0,
  1,
  '',
  0,
  0,
  0,
  ''
FROM `yoshop_crm_event_log` e
LEFT JOIN `yoshop_store_user` fu
  ON fu.`store_id` = e.`store_id`
 AND fu.`store_user_id` = CAST(JSON_UNQUOTE(JSON_EXTRACT(e.`data`, '$.from_owner')) AS UNSIGNED)
LEFT JOIN `yoshop_store_user` tu
  ON tu.`store_id` = e.`store_id`
 AND tu.`store_user_id` = CAST(JSON_UNQUOTE(JSON_EXTRACT(e.`data`, '$.to_owner')) AS UNSIGNED)
LEFT JOIN `yoshop_store_user` cfu
  ON cfu.`store_id` = e.`store_id`
 AND cfu.`store_user_id` = CAST(JSON_UNQUOTE(JSON_EXTRACT(e.`data`, '$.from_user_id')) AS UNSIGNED)
LEFT JOIN `yoshop_store_user` ctu
  ON ctu.`store_id` = e.`store_id`
 AND ctu.`store_user_id` = CAST(JSON_UNQUOTE(JSON_EXTRACT(e.`data`, '$.to_user_id')) AS UNSIGNED)
WHERE e.`target_type` = 'customer' AND e.`target_id` > 0;

-- ------------------------------------------------------------
-- 回填：系统自动掉公海（这条只有 claim_log 有）
-- ------------------------------------------------------------
INSERT IGNORE INTO `yoshop_crm_touchpoint`
(`store_id`,`customer_id`,`contact_id`,`project_id`,`category`,`source_type`,`source_id`,`event_type`,
 `title`,`summary`,`detail`,`action_url`,`occurred_at`,`create_time`,`actor_user_id`,`owner_user_id`,
 `dept_id`,`visibility`,`tags`,`severity`,`csat`,`voided_at`,`dedup_key`)
SELECT
  c.`store_id`,
  c.`customer_id`,
  0, 0,
  'system',
  'claim_log',
  c.`id`,
  'system_pool',
  '系统自动掉入公海',
  COALESCE(c.`remark`, ''),
  NULL,
  '',
  c.`create_time`,
  c.`create_time`,
  0, 0, 0, 1, '', 0, 0, 0, ''
FROM `yoshop_crm_customer_claim_log` c
WHERE c.`action` = 1;
