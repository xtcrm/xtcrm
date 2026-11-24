-- =============================================
-- CRM 菜单整理
-- 1) 删除重复/孤儿/已下线菜单
-- 2) 修正我的待办/我的流程父级（原来错误挂到商城首页 10001）
-- 3) 重排顶级菜单与工作台/应用子菜单顺序
-- 版本：2.12.3
-- =============================================

-- 删除菜单前先清角色绑定
DELETE FROM `yoshop_store_role_menu` WHERE `menu_id` IN (
  -- 重复「应用」及其重复子菜单
  21000, 21001, 21011,
  -- 孤儿「中文报价单」树（销售下已有 20220 + 20257~20259）
  10565, 10566, 10567, 10568,
  -- 孤儿顶层「论坛」（工作台下已有 21201~21205）
  21200,
  -- 孤儿重复「CRM设置」（系统设置下已有 20620）
  21017,
  -- 孤儿重复「查询操作日志」（操作日志页 20748 下已有 20749）
  20747,
  -- AI 助手 / OpenClaw：前端已无路由
  20400, 20401, 20402, 20403, 20404, 20405, 20451, 20452, 20453, 20454, 20455
);

DELETE FROM `yoshop_store_menu` WHERE `menu_id` IN (
  21000, 21001, 21011,
  10565, 10566, 10567, 10568,
  21200,
  21017,
  20747,
  20400, 20401, 20402, 20403, 20404, 20405, 20451, 20452, 20453, 20454, 20455
);

-- 我的待办 / 我的流程 挂到 CRM 工作台（20000），不是商城首页
UPDATE `yoshop_store_menu`
SET `parent_id` = 20000, `sort` = 20, `update_time` = UNIX_TIMESTAMP()
WHERE `menu_id` = 21272;

UPDATE `yoshop_store_menu`
SET `parent_id` = 20000, `sort` = 30, `update_time` = UNIX_TIMESTAMP()
WHERE `menu_id` = 21275;

-- 工作台子菜单顺序：仪表盘 → 我的待办 → 我的流程 → 论坛
UPDATE `yoshop_store_menu` SET `sort` = 10, `update_time` = UNIX_TIMESTAMP() WHERE `menu_id` = 20001;
UPDATE `yoshop_store_menu` SET `sort` = 40, `update_time` = UNIX_TIMESTAMP() WHERE `menu_id` = 21201;
UPDATE `yoshop_store_menu` SET `sort` = 45, `update_time` = UNIX_TIMESTAMP() WHERE `menu_id` = 21202;
UPDATE `yoshop_store_menu` SET `sort` = 50, `update_time` = UNIX_TIMESTAMP() WHERE `menu_id` = 21203;
UPDATE `yoshop_store_menu` SET `sort` = 60, `update_time` = UNIX_TIMESTAMP() WHERE `menu_id` = 21204;
UPDATE `yoshop_store_menu` SET `sort` = 61, `update_time` = UNIX_TIMESTAMP() WHERE `menu_id` = 21205;

-- 顶级菜单顺序
UPDATE `yoshop_store_menu` SET `sort` = 10,  `update_time` = UNIX_TIMESTAMP() WHERE `menu_id` = 20000; -- 工作台
UPDATE `yoshop_store_menu` SET `sort` = 20,  `update_time` = UNIX_TIMESTAMP() WHERE `menu_id` = 20100; -- 客户
UPDATE `yoshop_store_menu` SET `sort` = 30,  `update_time` = UNIX_TIMESTAMP() WHERE `menu_id` = 20900; -- 项目
UPDATE `yoshop_store_menu` SET `sort` = 40,  `update_time` = UNIX_TIMESTAMP() WHERE `menu_id` = 20200; -- 销售
UPDATE `yoshop_store_menu` SET `sort` = 50,  `update_time` = UNIX_TIMESTAMP() WHERE `menu_id` = 21129; -- 业绩
UPDATE `yoshop_store_menu` SET `sort` = 60,  `update_time` = UNIX_TIMESTAMP() WHERE `menu_id` = 21138; -- 报表
UPDATE `yoshop_store_menu` SET `sort` = 70,  `update_time` = UNIX_TIMESTAMP() WHERE `menu_id` = 20300; -- 知识库
UPDATE `yoshop_store_menu` SET `sort` = 80,  `update_time` = UNIX_TIMESTAMP() WHERE `menu_id` = 20500; -- 应用
UPDATE `yoshop_store_menu` SET `sort` = 90,  `update_time` = UNIX_TIMESTAMP() WHERE `menu_id` = 20600; -- 系统设置

-- 应用子菜单排序（去重后）
UPDATE `yoshop_store_menu` SET `sort` = 10, `update_time` = UNIX_TIMESTAMP() WHERE `menu_id` = 20510; -- 获客账本
UPDATE `yoshop_store_menu` SET `sort` = 20, `update_time` = UNIX_TIMESTAMP() WHERE `menu_id` = 21206; -- 财税管理
UPDATE `yoshop_store_menu` SET `sort` = 30, `update_time` = UNIX_TIMESTAMP() WHERE `menu_id` = 20520; -- 票无忧
UPDATE `yoshop_store_menu` SET `sort` = 40, `update_time` = UNIX_TIMESTAMP() WHERE `menu_id` = 20530; -- 官网管理
UPDATE `yoshop_store_menu` SET `sort` = 50, `update_time` = UNIX_TIMESTAMP() WHERE `menu_id` = 20540; -- 工单
UPDATE `yoshop_store_menu` SET `sort` = 60, `update_time` = UNIX_TIMESTAMP() WHERE `menu_id` = 20590; -- 企业查询
UPDATE `yoshop_store_menu` SET `sort` = 70, `update_time` = UNIX_TIMESTAMP() WHERE `menu_id` = 20800; -- AI 影音
UPDATE `yoshop_store_menu` SET `sort` = 80, `update_time` = UNIX_TIMESTAMP() WHERE `menu_id` = 21271; -- 流程中心
