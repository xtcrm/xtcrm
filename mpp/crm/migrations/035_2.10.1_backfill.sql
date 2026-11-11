-- =============================================
-- CRM v2.10.1 — 历史业绩回填
--
-- 把切换口径之前的历史订单补成 legacy 分账与业绩记录，否则上线后历史业绩全部归零。
--   历史订单不区分新签/续费（无历史 biz_type），统一按新签 100% 记在销售名下，
--   与升级前的「按订单负责人聚合」口径保持一致。
--   record 金额取 paid_amount，归属月取订单创建月，legacy = 1。
--
-- 同时补项目快照：客服责任人从客户带入；历史项目视为已通过审核，
-- 避免上线当天全部项目堵在待审队列。
--
-- 幂等：NOT EXISTS 判存；项目快照只补空缺。
-- =============================================

INSERT INTO `yoshop_crm_commission_share`
(`store_id`, `biz_type`, `source_type`, `source_id`, `source_no`, `customer_id`, `project_id`,
 `user_id`, `post_code`, `role`, `ratio`, `base_amount`, `share_amount`, `confirmed_amount`,
 `status`, `group_no`, `adjust_of_id`, `lock_time`, `override_id`, `legacy`, `remark`,
 `create_time`, `update_time`)
SELECT o.`store_id`, 1, 1, o.`id`, o.`order_no`, o.`customer_id`, o.`project_id`,
       o.`owner_user_id`, 'sales', 1, 100.00, o.`final_amount`, o.`final_amount`, o.`paid_amount`,
       1, 0, 0, UNIX_TIMESTAMP(), 0, 1, '历史回填',
       UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `yoshop_crm_order` o
WHERE o.`is_delete` = 0 AND o.`paid_amount` > 0 AND o.`owner_user_id` > 0
  AND NOT EXISTS (
    SELECT 1 FROM `yoshop_crm_commission_share` s
    WHERE s.`store_id` = o.`store_id` AND s.`source_type` = 1 AND s.`source_id` = o.`id`
  );

INSERT INTO `yoshop_crm_commission_record`
(`store_id`, `share_id`, `payment_id`, `customer_id`, `project_id`, `user_id`, `post_code`,
 `biz_type`, `ratio`, `base_amount`, `amount`, `confirm_month`, `confirmed_at`,
 `reversal_of_id`, `legacy`, `remark`, `create_time`)
SELECT s.`store_id`, s.`id`, 0, s.`customer_id`, s.`project_id`, s.`user_id`, s.`post_code`,
       s.`biz_type`, s.`ratio`, o.`paid_amount`, o.`paid_amount`,
       DATE_FORMAT(FROM_UNIXTIME(o.`create_time`), '%Y-%m'), o.`create_time`,
       0, 1, '历史回填', UNIX_TIMESTAMP()
FROM `yoshop_crm_commission_share` s
JOIN `yoshop_crm_order` o ON o.`store_id` = s.`store_id` AND o.`id` = s.`source_id`
WHERE s.`legacy` = 1 AND s.`source_type` = 1
  AND NOT EXISTS (
    SELECT 1 FROM `yoshop_crm_commission_record` r WHERE r.`share_id` = s.`id`
  );

UPDATE `yoshop_crm_project` p
JOIN `yoshop_crm_customer` c ON c.`store_id` = p.`store_id` AND c.`id` = p.`customer_id`
SET p.`cs_user_id` = c.`cs_user_id`, p.`update_time` = UNIX_TIMESTAMP()
WHERE p.`cs_user_id` = 0 AND c.`cs_user_id` > 0 AND p.`is_delete` = 0;

UPDATE `yoshop_crm_project`
SET `review_status` = 2, `update_time` = UNIX_TIMESTAMP()
WHERE `review_status` = 0 AND `is_delete` = 0;
