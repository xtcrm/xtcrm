-- =============================================
-- 流程模板校验动作改名：validate → check
-- 原因：Template::validate() 与 cores\BaseController::validate() 签名冲突
-- 版本：1.0.1
-- =============================================

UPDATE `yoshop_store_menu`
SET `path` = '/flow.template/check', `action_mark` = 'check', `update_time` = UNIX_TIMESTAMP()
WHERE `path` = '/flow.template/validate';
