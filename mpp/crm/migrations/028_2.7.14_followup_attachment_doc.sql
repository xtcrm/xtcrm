-- =============================================
-- CRM v2.7.14 — 跟进附件归档到资料库
--
-- 之前「记一笔」上传的附件只把路径写进 crm_followup.attachment，
-- 文件虽然进了 upload_file，但不会出现在项目/客户详情的「文件」Tab。
--
-- 本次改为引用式统一：
--   crm_document 是文件唯一档案（category = 沟通附件）
--   crm_followup.attachment_doc_id 指回档案，attachment 路径字段保留用于列表快速渲染
-- 删除沟通记录不删文件；删除被引用的文件由 DocumentService 拦截。
-- =============================================

ALTER TABLE `yoshop_crm_followup`
  ADD COLUMN `attachment_doc_id` int(11) NOT NULL DEFAULT 0 COMMENT '归档资料ID（crm_document.id，0=未归档）' AFTER `attachment`;

-- 历史数据回填：给已有附件补建 crm_document，再回写 attachment_doc_id
INSERT INTO `yoshop_crm_document`
(`store_id`, `customer_id`, `project_id`, `quotation_id`, `order_id`, `contract_id`,
 `file_id`, `category`, `title`, `remark`, `owner_user_id`, `is_delete`, `create_time`, `update_time`)
SELECT f.`store_id`, f.`customer_id`, IFNULL(f.`project_id`, 0), 0, 0, 0,
       uf.`file_id`, '沟通附件', uf.`file_name`, CONCAT('来自沟通记录 #', f.`id`),
       f.`owner_user_id`, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM `yoshop_crm_followup` f
JOIN `yoshop_upload_file` uf
  ON uf.`store_id` = f.`store_id` AND uf.`file_path` = f.`attachment` AND uf.`is_delete` = 0
WHERE f.`attachment` <> '' AND f.`attachment` <> '0'
  AND f.`attachment_doc_id` = 0 AND f.`is_delete` = 0;

UPDATE `yoshop_crm_followup` f
JOIN `yoshop_crm_document` d
  ON d.`store_id` = f.`store_id`
 AND d.`category` = '沟通附件'
 AND d.`remark` = CONCAT('来自沟通记录 #', f.`id`)
SET f.`attachment_doc_id` = d.`id`
WHERE f.`attachment_doc_id` = 0 AND f.`attachment` <> '' AND f.`attachment` <> '0';
