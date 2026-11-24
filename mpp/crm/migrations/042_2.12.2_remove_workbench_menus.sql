-- =============================================
-- 移除旧岗位工作台入口
--   我的服务客户 / 我的交付 / 待审队列
-- 背景：日常待办已统一到 工作台 → 我的待办 / 我的流程；
--       这三个入口的路由已删除，菜单和角色绑定一并清理。
-- 注：页面文件和接口保留，便于回滚；ProjectService::getReviewList
--     仍被「项目 → 审核队列」使用，不要删。
-- 版本：2.12.2
-- =============================================

DELETE FROM `yoshop_store_role_menu`
WHERE `menu_id` IN (
  SELECT `menu_id` FROM `yoshop_store_menu`
  WHERE `path` IN ('/crm/workbench/cs', '/crm/workbench/delivery', '/crm/workbench/review')
);

DELETE FROM `yoshop_store_menu`
WHERE `path` IN ('/crm/workbench/cs', '/crm/workbench/delivery', '/crm/workbench/review');
