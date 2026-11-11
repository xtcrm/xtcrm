-- =============================================
-- CRM v2.8.2 — 项目审核记录
--
-- 审核是全检：每个交付办结前必须过审，因此它是项目状态机的一环，不是独立任务。
--   review_status 已在 030 加到 crm_project：0未送审 1待审核 2已通过 3已退回
--   本表按「轮次」记录每次送审与审核结果，round_no 递增。
--
-- 指派制（D11）：送审后由组长指派审核人，队列对审核组可见但只有被指派人可操作。
--   assign_user_id / assign_time 用于区分「排队时长」和「响应时长」。
-- 独立性：submit_user_id != reviewer_user_id，由 ProjectService 强制。
--
-- 幂等：CREATE TABLE IF NOT EXISTS。
-- =============================================

CREATE TABLE IF NOT EXISTS `yoshop_crm_project_review` (
  `id`               int(11)      NOT NULL AUTO_INCREMENT COMMENT '审核ID',
  `store_id`         int(11)      NOT NULL DEFAULT 0 COMMENT '租户ID',
  `project_id`       int(11)      NOT NULL DEFAULT 0 COMMENT '项目ID',
  `workorder_id`     int(11)      NOT NULL DEFAULT 0 COMMENT '关联工单 0=成果型',
  `customer_id`      int(11)      NOT NULL DEFAULT 0 COMMENT '客户ID',
  `submit_user_id`   int(11)      NOT NULL DEFAULT 0 COMMENT '送审人',
  `submit_time`      int(11)      NOT NULL DEFAULT 0 COMMENT '送审时间',
  `reviewer_user_id` int(11)      NOT NULL DEFAULT 0 COMMENT '审核人',
  `assign_user_id`   int(11)      NOT NULL DEFAULT 0 COMMENT '指派操作人（组长）',
  `assign_time`      int(11)      NOT NULL DEFAULT 0 COMMENT '指派时间',
  `review_time`      int(11)      NOT NULL DEFAULT 0 COMMENT '审核时间',
  `result`           tinyint(3)   NOT NULL DEFAULT 0 COMMENT '0待审 1通过 2退回',
  `reason`           varchar(500) NOT NULL DEFAULT '' COMMENT '退回原因',
  `round_no`         int(11)      NOT NULL DEFAULT 1 COMMENT '第几轮送审',
  `create_time`      int(11)      DEFAULT NULL,
  `update_time`      int(11)      DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pending`  (`store_id`,`result`,`submit_time`),
  KEY `idx_project`  (`store_id`,`project_id`),
  KEY `idx_reviewer` (`store_id`,`reviewer_user_id`,`result`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='CRM项目审核记录';
