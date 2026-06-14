-- CRM 菜单体系 (menu_id 从 20000 开始)
-- module=10 菜单项, module=20 操作项
-- 路径使用斜杠格式，与 controller checkAction 一致
-- 菜单结构 v2：工作台/客户管理(含线索)/销售管理/知识库/应用/系统设置

-- ===== 一级菜单 =====
INSERT IGNORE INTO `yoshop_store_menu` (`menu_id`,`module`,`fun_type`,`name`,`path`,`action_mark`,`parent_id`,`sort`) VALUES
(20001,10,'crm','工作台','/crm','',0,10),
(20010,10,'crm','客户管理','/crm/customer','',0,20),
(20020,10,'crm','线索管理','/crm/lead','',0,30),
(20030,10,'crm','报价管理','/crm/quotation','',0,40),
(20040,10,'crm','订单管理','/crm/order','',0,50),
(20050,10,'crm','合同管理','/crm/contract','',0,60),
(20060,10,'crm','产品管理','/crm/product','',0,70),
(20070,10,'crm','知识库','/crm/knowledge','',0,80),
(20080,10,'crm','系统管理','/manage','',0,200);

-- ===== 工作台 =====
INSERT IGNORE INTO `yoshop_store_menu` (`menu_id`,`module`,`fun_type`,`name`,`path`,`action_mark`,`parent_id`,`sort`) VALUES
(20002,10,'crm','仪表盘','/crm/dashboard/index','',20001,100);

-- ===== 客户管理 =====
INSERT IGNORE INTO `yoshop_store_menu` (`menu_id`,`module`,`fun_type`,`name`,`path`,`action_mark`,`parent_id`,`sort`) VALUES
(20011,10,'crm','客户列表','/crm/customer/lists','',20010,100),
(20012,10,'crm','公海池','/crm/customer/poolLists','',20010,110),
(20013,10,'crm','协作客户','/crm/customer/collabLists','',20010,120),
(20014,10,'crm','客户详情','/crm/customer/detail','',20010,130),
(20015,10,'crm','新增客户','/crm/customer/create','',20010,140);

INSERT IGNORE INTO `yoshop_store_menu` (`menu_id`,`module`,`fun_type`,`name`,`path`,`action_mark`,`parent_id`,`sort`) VALUES
(20016,20,'crm','客户查询','/crm/customer/select','select',20011,50),
(20017,20,'crm','编辑客户','/crm/customer/edit','edit',20011,100),
(20018,20,'crm','删除客户','/crm/customer/delete','delete',20011,110),
(20019,20,'crm','AI分析','/crm/customer/analyze','analyze',20012,100),
(20021,20,'crm','认领客户','/crm/customer/claim','claim',20012,110),
(20022,20,'crm','释放客户','/crm/customer/release','release',20012,120);

-- ===== 线索管理 =====
INSERT IGNORE INTO `yoshop_store_menu` (`menu_id`,`module`,`fun_type`,`name`,`path`,`action_mark`,`parent_id`,`sort`) VALUES
(20023,10,'crm','线索列表','/crm/lead/lists','',20020,100),
(20024,10,'crm','线索详情','/crm/lead/detail','',20020,110);

INSERT IGNORE INTO `yoshop_store_menu` (`menu_id`,`module`,`fun_type`,`name`,`path`,`action_mark`,`parent_id`,`sort`) VALUES
(20025,20,'crm','新增线索','/crm/lead/add','add',20023,100),
(20026,20,'crm','编辑线索','/crm/lead/edit','edit',20023,110),
(20027,20,'crm','删除线索','/crm/lead/delete','delete',20023,120),
(20028,20,'crm','转为客户','/crm/lead/convert','convert',20023,130);

-- ===== 报价管理 =====
INSERT IGNORE INTO `yoshop_store_menu` (`menu_id`,`module`,`fun_type`,`name`,`path`,`action_mark`,`parent_id`,`sort`) VALUES
(20031,10,'crm','报价列表','/crm/quotation/lists','',20030,100),
(20032,10,'crm','报价详情','/crm/quotation/detail','',20030,110);

INSERT IGNORE INTO `yoshop_store_menu` (`menu_id`,`module`,`fun_type`,`name`,`path`,`action_mark`,`parent_id`,`sort`) VALUES
(20033,20,'crm','新增报价','/crm/quotation/add','add',20031,100),
(20034,20,'crm','编辑报价','/crm/quotation/edit','edit',20031,110),
(20035,20,'crm','删除报价','/crm/quotation/delete','delete',20031,120),
(20036,20,'crm','发送报价','/crm/quotation/send','send',20032,110),
(20037,20,'crm','确认报价','/crm/quotation/confirm','confirm',20032,120),
(20038,20,'crm','转为订单','/crm/quotation/convert','convert',20032,130);

-- ===== 订单管理 =====
INSERT IGNORE INTO `yoshop_store_menu` (`menu_id`,`module`,`fun_type`,`name`,`path`,`action_mark`,`parent_id`,`sort`) VALUES
(20041,10,'crm','订单列表','/crm/order/lists','',20040,100),
(20042,10,'crm','订单详情','/crm/order/detail','',20040,110);

INSERT IGNORE INTO `yoshop_store_menu` (`menu_id`,`module`,`fun_type`,`name`,`path`,`action_mark`,`parent_id`,`sort`) VALUES
(20043,20,'crm','新增订单','/crm/order/add','add',20041,100),
(20044,20,'crm','编辑订单','/crm/order/edit','edit',20041,110),
(20045,20,'crm','删除订单','/crm/order/delete','delete',20041,120),
(20046,20,'crm','状态变更','/crm/order/changeStatus','changeStatus',20042,100);

-- ===== 合同管理 =====
INSERT IGNORE INTO `yoshop_store_menu` (`menu_id`,`module`,`fun_type`,`name`,`path`,`action_mark`,`parent_id`,`sort`) VALUES
(20051,10,'crm','合同列表','/crm/contract/lists','',20050,100),
(20052,10,'crm','合同详情','/crm/contract/detail','',20050,110);

INSERT IGNORE INTO `yoshop_store_menu` (`menu_id`,`module`,`fun_type`,`name`,`path`,`action_mark`,`parent_id`,`sort`) VALUES
(20053,20,'crm','新增合同','/crm/contract/add','add',20051,100),
(20054,20,'crm','编辑合同','/crm/contract/edit','edit',20051,110),
(20055,20,'crm','删除合同','/crm/contract/delete','delete',20051,120);

-- ===== 产品管理 =====
INSERT IGNORE INTO `yoshop_store_menu` (`menu_id`,`module`,`fun_type`,`name`,`path`,`action_mark`,`parent_id`,`sort`) VALUES
(20061,10,'crm','产品列表','/crm/product/lists','',20060,100);

INSERT IGNORE INTO `yoshop_store_menu` (`menu_id`,`module`,`fun_type`,`name`,`path`,`action_mark`,`parent_id`,`sort`) VALUES
(20062,20,'crm','新增产品','/crm/product/add','add',20061,100),
(20063,20,'crm','编辑产品','/crm/product/edit','edit',20061,110),
(20064,20,'crm','删除产品','/crm/product/delete','delete',20061,120),
(20065,20,'crm','产品查询','/crm/product/select','select',20061,50);

-- ===== 知识库 =====
INSERT IGNORE INTO `yoshop_store_menu` (`menu_id`,`module`,`fun_type`,`name`,`path`,`action_mark`,`parent_id`,`sort`) VALUES
(20071,10,'crm','知识列表','/crm/knowledge/lists','',20070,100);

INSERT IGNORE INTO `yoshop_store_menu` (`menu_id`,`module`,`fun_type`,`name`,`path`,`action_mark`,`parent_id`,`sort`) VALUES
(20072,20,'crm','新增知识','/crm/knowledge/add','add',20071,100),
(20073,20,'crm','编辑知识','/crm/knowledge/edit','edit',20071,110),
(20074,20,'crm','删除知识','/crm/knowledge/delete','delete',20071,120);

-- ===== 系统管理 =====
INSERT IGNORE INTO `yoshop_store_menu` (`menu_id`,`module`,`fun_type`,`name`,`path`,`action_mark`,`parent_id`,`sort`) VALUES
(20081,10,'crm','管理员列表','/crm/admin/user/list','',20080,100),
(20082,10,'crm','角色管理','/crm/admin/role/list','',20080,110),
(20083,10,'crm','部门管理','/crm/manage/department/tree','',20080,120),
(20084,10,'crm','菜单管理','/crm/admin/menu/list','',20080,130);

INSERT IGNORE INTO `yoshop_store_menu` (`menu_id`,`module`,`fun_type`,`name`,`path`,`action_mark`,`parent_id`,`sort`) VALUES
(20085,20,'crm','新增管理员','/crm/admin/user/add','add',20081,100),
(20086,20,'crm','编辑管理员','/crm/admin/user/edit','edit',20081,110),
(20087,20,'crm','删除管理员','/crm/admin/user/delete','delete',20081,120),
(20088,20,'crm','新增角色','/crm/admin/role/add','add',20082,100),
(20089,20,'crm','编辑角色','/crm/admin/role/edit','edit',20082,110),
(20090,20,'crm','删除角色','/crm/admin/role/delete','delete',20082,120),
(20091,20,'crm','新增部门','/crm/manage/department/add','add',20083,100),
(20092,20,'crm','编辑部门','/crm/manage/department/edit','edit',20083,110),
(20093,20,'crm','删除部门','/crm/manage/department/delete','delete',20083,120),
(20094,20,'crm','分配员工','/crm/manage/department/assignUser','assignUser',20083,130),
(20095,20,'crm','移除员工','/crm/manage/department/removeUser','removeUser',20083,140);

-- ===== 报价扩展（中文报价 skill） =====
INSERT IGNORE INTO `yoshop_store_menu` (`menu_id`,`module`,`fun_type`,`name`,`path`,`action_mark`,`parent_id`,`sort`) VALUES
(20039,20,'crm','中文报价预览','/crm/quotation_cn/preview','preview',20031,50),
(20047,20,'crm','导出中文报价','/crm/quotation_cn/exportPdf','exportPdf',20031,60),
(20048,20,'crm','保存报价模板','/crm/quotation_cn/saveTemplate','saveTemplate',20031,70);

-- ===== 绑定到管理员角色 =====
INSERT IGNORE INTO `yoshop_store_role_menu` (`role_id`, `menu_id`, `store_id`)
SELECT 1, menu_id, 0 FROM `yoshop_store_menu` WHERE fun_type = 'crm' AND menu_id >= 20000;

-- 修复：20020 的 AI画像 action 引用（20020 是线索管理的 menu_id，AI画像应该是 20026）
-- 注意：20020 原本是 module=20 的操作项（AI画像），同时也是 module=10 的线索管理菜单ID
-- 这里无需修复——线索管理 menu_id=20020 是在线纠正后的正确值
