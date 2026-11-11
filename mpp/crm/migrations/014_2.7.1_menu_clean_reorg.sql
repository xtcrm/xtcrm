-- ============================================================
-- CRM 菜单全面重构
-- 版本: 2.7.1
-- 页面路径 = asyncRoutes.js（前端路由）
-- 操作路径 = 各 Controller checkAction() 调用（后端权限校验）
-- ID 从 20000 起，按模块 1000 间隔
-- ============================================================

-- Step 1: 清理
DELETE FROM yoshop_store_role_menu WHERE menu_id IN (SELECT menu_id FROM yoshop_store_menu WHERE fun_type = 'crm');
DELETE FROM yoshop_store_menu WHERE fun_type = 'crm';

-- ============================================================
-- Step 2: 页面菜单 module=10（路径 = asyncRoutes.js）
-- ============================================================

-- ─── 20000 工作台 ───
INSERT INTO yoshop_store_menu (menu_id, module, fun_type, name, path, action_mark, parent_id, sort, create_time, update_time) VALUES
(20000, 10, 'crm', '工作台', '/index', '', 0, 10, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20001, 10, 'crm', '仪表盘', '/index', '', 20000, 10, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- ─── 20100 客户管理 ───
INSERT INTO yoshop_store_menu (menu_id, module, fun_type, name, path, action_mark, parent_id, sort, create_time, update_time) VALUES
(20100, 10, 'crm', '客户管理', '/crm/customer', '', 0, 20, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20101, 10, 'crm', '客户列表', '/crm/customer/index', '', 20100, 10, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20102, 10, 'crm', '公海池', '/crm/customer/pool', '', 20100, 20, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20103, 10, 'crm', '协作客户', '/crm/customer/collab', '', 20100, 30, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20104, 10, 'crm', '新增客户', '/crm/customer/create', '', 20100, 40, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20105, 10, 'crm', '今日拜访', '/crm/customer/today-visit', '', 20100, 50, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20109, 10, 'crm', '客户详情', '/crm/customer/detail', '', 20100, 60, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20110, 10, 'crm', '线索管理', '/crm/lead/index', '', 20100, 70, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20119, 10, 'crm', '线索详情', '/crm/lead/detail', '', 20100, 80, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20120, 10, 'crm', '联系人管理', '/crm/contact/index', '', 20100, 90, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20129, 10, 'crm', '联系人详情', '/crm/contact/detail', '', 20100, 100, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- ─── 20200 销售管理 ───
INSERT INTO yoshop_store_menu (menu_id, module, fun_type, name, path, action_mark, parent_id, sort, create_time, update_time) VALUES
(20200, 10, 'crm', '销售管理', '/crm/quotation', '', 0, 30, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20201, 10, 'crm', '报价管理', '/crm/quotation/index', '', 20200, 10, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20209, 10, 'crm', '报价详情', '/crm/quotation/detail', '', 20200, 15, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20202, 10, 'crm', '订单管理', '/crm/order/index', '', 20200, 20, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20210, 10, 'crm', '订单详情', '/crm/order/detail', '', 20200, 25, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20203, 10, 'crm', '合同管理', '/crm/contract/index', '', 20200, 30, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20211, 10, 'crm', '合同详情', '/crm/contract/detail', '', 20200, 35, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20220, 10, 'crm', '中文报价预览', '/crm/quotation/cn-preview', '', 20200, 40, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- ─── 20300 知识库 ───
INSERT INTO yoshop_store_menu (menu_id, module, fun_type, name, path, action_mark, parent_id, sort, create_time, update_time) VALUES
(20300, 10, 'crm', '知识库', '/crm/knowledge', '', 0, 40, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20301, 10, 'crm', '知识列表', '/crm/knowledge/index', '', 20300, 10, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20310, 10, 'crm', '内容创作', '/crm/content', '', 20300, 20, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20311, 10, 'crm', '创作工坊', '/crm/content/create', '', 20310, 10, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20312, 10, 'crm', '创作配置', '/crm/content/config', '', 20310, 20, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20313, 10, 'crm', '公司档案', '/crm/content/profile', '', 20310, 30, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20314, 10, 'crm', '关键词管理', '/crm/content/keywords', '', 20310, 40, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20315, 10, 'crm', '解决方案库', '/crm/content/solutions', '', 20310, 50, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20316, 10, 'crm', '写作风格', '/crm/content/styles', '', 20310, 60, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20317, 10, 'crm', '创作Agent', '/crm/content/agents', '', 20310, 70, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20318, 10, 'crm', '内容日历', '/crm/content/calendar', '', 20310, 80, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20319, 10, 'crm', '内容效果', '/crm/content/analytics', '', 20310, 90, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- ─── 20400 AI 助手 ───
INSERT INTO yoshop_store_menu (menu_id, module, fun_type, name, path, action_mark, parent_id, sort, create_time, update_time) VALUES
(20400, 10, 'crm', 'AI 助手', '/ai', '', 0, 50, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20401, 10, 'crm', 'OpenClaw', '/ai/openclaw', '', 20400, 10, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20402, 10, 'crm', '控制台', '/ai/openclaw/index', '', 20401, 10, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20403, 10, 'crm', '技能管理', '/ai/openclaw/skills', '', 20401, 20, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20404, 10, 'crm', '节点管理', '/ai/openclaw/nodes', '', 20401, 30, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20405, 10, 'crm', '设置', '/ai/openclaw/setting', '', 20401, 40, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- ─── 20500 应用（Skills） ───
INSERT INTO yoshop_store_menu (menu_id, module, fun_type, name, path, action_mark, parent_id, sort, create_time, update_time) VALUES
(20500, 10, 'crm', '应用', '/app', '', 0, 60, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 获客账本
INSERT INTO yoshop_store_menu (menu_id, module, fun_type, name, path, action_mark, parent_id, sort, create_time, update_time) VALUES
(20510, 10, 'crm', '获客账本', '/app/leadledger', '', 20500, 10, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20511, 10, 'crm', '获客ROI', '/app/leadledger/dashboard', '', 20510, 10, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20512, 10, 'crm', '分享链接', '/app/leadledger/share-link', '', 20510, 20, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20513, 10, 'crm', '页面模板', '/app/leadledger/page-template', '', 20510, 30, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20514, 10, 'crm', '表单设计', '/app/leadledger/form-design', '', 20510, 40, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20515, 10, 'crm', '线索管理', '/app/leadledger/lead', '', 20510, 50, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20519, 10, 'crm', '线索详情', '/app/leadledger/lead/detail', '', 20510, 60, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 票无忧
INSERT INTO yoshop_store_menu (menu_id, module, fun_type, name, path, action_mark, parent_id, sort, create_time, update_time) VALUES
(20520, 10, 'crm', '票无忧', '/app/invoice', '', 20500, 20, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20521, 10, 'crm', '税负仪表盘', '/app/invoice/dashboard', '', 20520, 10, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20522, 10, 'crm', '发票管理', '/app/invoice/list', '', 20520, 20, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20523, 10, 'crm', '开票公司', '/app/invoice/company', '', 20520, 30, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20524, 10, 'crm', '短信配置', '/app/invoice/sms', '', 20520, 40, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20525, 10, 'crm', '预警配置', '/app/invoice/alert', '', 20520, 50, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20526, 10, 'crm', 'OCR 配置', '/app/invoice/ocr', '', 20520, 60, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20527, 10, 'crm', '发票详情', '/app/invoice/detail', '', 20520, 70, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20528, 10, 'crm', '公司详情', '/app/invoice/company/detail', '', 20520, 80, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 官网管理
INSERT INTO yoshop_store_menu (menu_id, module, fun_type, name, path, action_mark, parent_id, sort, create_time, update_time) VALUES
(20530, 10, 'crm', '官网管理', '/app/website', '', 20500, 30, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20531, 10, 'crm', '产品管理', '/app/website/products', '', 20530, 10, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20532, 10, 'crm', '资讯管理', '/app/website/posts', '', 20530, 20, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20533, 10, 'crm', '案例管理', '/app/website/cases', '', 20530, 30, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20534, 10, 'crm', '页面管理', '/app/website/pages', '', 20530, 40, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20535, 10, 'crm', '导航管理', '/app/website/nav', '', 20530, 50, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20536, 10, 'crm', 'SEO 关键字', '/app/website/keywords', '', 20530, 60, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20537, 10, 'crm', '留言管理', '/app/website/messages', '', 20530, 70, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20538, 10, 'crm', '网站设置', '/app/website/settings', '', 20530, 80, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- ─── 20600 系统设置 ───
INSERT INTO yoshop_store_menu (menu_id, module, fun_type, name, path, action_mark, parent_id, sort, create_time, update_time) VALUES
(20600, 10, 'crm', '系统设置', '/crm/product', '', 0, 70, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20601, 10, 'crm', '产品管理', '/crm/product/index', '', 20600, 10, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20610, 10, 'crm', '管理员列表', '/manage/user/index', '', 20600, 20, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20611, 10, 'crm', '角色管理', '/manage/role/index', '', 20600, 30, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20612, 10, 'crm', '部门管理', '/manage/department/index', '', 20600, 40, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20613, 10, 'crm', '菜单管理', '/manage/menu/index', '', 20600, 50, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20620, 10, 'crm', 'CRM 设置', '/crm/setting', '', 20600, 60, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20621, 10, 'crm', '公海规则', '/crm/setting?tab=pool', '', 20620, 10, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20622, 10, 'crm', '审批规则', '/crm/setting?tab=approval', '', 20620, 20, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20623, 10, 'crm', 'AI配置', '/crm/setting?tab=ai', '', 20620, 30, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20624, 10, 'crm', 'AI洞察', '/crm/setting?tab=insight', '', 20620, 40, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20625, 10, 'crm', '字典配置', '/crm/setting?tab=dict', '', 20620, 50, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20626, 10, 'crm', '自定义字段', '/crm/setting/customField', '', 20620, 60, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20627, 10, 'crm', '需求规则', '/crm/setting/demandRule', '', 20620, 70, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20628, 10, 'crm', 'API 密钥', '/crm/setting/apiKey', '', 20620, 80, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- ============================================================
-- Step 3: 操作权限 module=20（路径 = Controller checkAction 调用）
-- ============================================================

-- ─── Customer 操作 ───
INSERT INTO yoshop_store_menu (menu_id, module, fun_type, name, path, action_mark, parent_id, sort, create_time, update_time) VALUES
(20151, 20, 'crm', '客户列表', '/crm/customer/lists', 'lists', 20101, 100, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20152, 20, 'crm', '客户查询', '/crm/customer/select', 'select', 20101, 110, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20153, 20, 'crm', '新增客户', '/crm/customer/add', 'add', 20104, 100, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20154, 20, 'crm', '编辑客户', '/crm/customer/edit', 'edit', 20109, 100, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20155, 20, 'crm', '删除客户', '/crm/customer/delete', 'delete', 20101, 120, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20156, 20, 'crm', '公海列表', '/crm/customer/poolLists', 'poolLists', 20102, 100, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20157, 20, 'crm', '认领客户', '/crm/customer/claim', 'claim', 20102, 110, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20158, 20, 'crm', '释放客户', '/crm/customer/release', 'release', 20102, 120, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20159, 20, 'crm', '变更状态', '/crm/customer/changeStatus', 'changeStatus', 20109, 110, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20160, 20, 'crm', 'AI分析', '/crm/customer/analyze', 'analyze', 20109, 120, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20161, 20, 'crm', 'AI画像', '/crm/customer/portrait', 'portrait', 20109, 130, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- ─── Contact 操作 ───
-- ⚠️ 已知：此段 SQL 在实际执行时未生效（followup 占用了 20171-20174），
--   联系人权限由 015_2.7.2_contact_permissions.sql 补充到 20175-20180。
INSERT INTO yoshop_store_menu (menu_id, module, fun_type, name, path, action_mark, parent_id, sort, create_time, update_time) VALUES
(20171, 20, 'crm', '联系人列表', '/crm/contact/list', 'list', 20120, 100, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20172, 20, 'crm', '联系人详情', '/crm/contact/detail', 'detail', 20129, 100, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20173, 20, 'crm', '新增联系人', '/crm/contact/add', 'add', 20120, 110, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20174, 20, 'crm', '编辑联系人', '/crm/contact/edit', 'edit', 20129, 110, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20175, 20, 'crm', '解除绑定', '/crm/contact/unbind', 'unbind', 20129, 120, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20176, 20, 'crm', '删除联系人', '/crm/contact/delete', 'delete', 20120, 120, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- ─── Lead 操作 ───
INSERT INTO yoshop_store_menu (menu_id, module, fun_type, name, path, action_mark, parent_id, sort, create_time, update_time) VALUES
(20181, 20, 'crm', '线索列表', '/crm/lead/lists', 'lists', 20110, 100, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20182, 20, 'crm', '线索详情', '/crm/lead/detail', 'detail', 20119, 100, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20183, 20, 'crm', '新增线索', '/crm/lead/add', 'add', 20110, 110, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20184, 20, 'crm', '编辑线索', '/crm/lead/edit', 'edit', 20110, 120, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20185, 20, 'crm', '删除线索', '/crm/lead/delete', 'delete', 20110, 130, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20186, 20, 'crm', '转为客户', '/crm/lead/convert', 'convert', 20110, 140, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- ─── Quotation 操作 ───
INSERT INTO yoshop_store_menu (menu_id, module, fun_type, name, path, action_mark, parent_id, sort, create_time, update_time) VALUES
(20251, 20, 'crm', '报价列表', '/crm/quotation/lists', 'lists', 20201, 100, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20252, 20, 'crm', '报价详情', '/crm/quotation/detail', 'detail', 20209, 100, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20253, 20, 'crm', '新增报价', '/crm/quotation/add', 'add', 20201, 110, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20254, 20, 'crm', '编辑报价', '/crm/quotation/edit', 'edit', 20201, 120, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20255, 20, 'crm', '删除报价', '/crm/quotation/delete', 'delete', 20201, 130, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20256, 20, 'crm', '变更状态', '/crm/quotation/changeStatus', 'changeStatus', 20201, 140, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- ─── Order 操作 ───
INSERT INTO yoshop_store_menu (menu_id, module, fun_type, name, path, action_mark, parent_id, sort, create_time, update_time) VALUES
(20261, 20, 'crm', '订单列表', '/crm/order/lists', 'lists', 20202, 100, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20262, 20, 'crm', '订单详情', '/crm/order/detail', 'detail', 20210, 100, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20263, 20, 'crm', '新增订单', '/crm/order/add', 'add', 20202, 110, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20264, 20, 'crm', '编辑订单', '/crm/order/edit', 'edit', 20202, 120, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20265, 20, 'crm', '删除订单', '/crm/order/delete', 'delete', 20202, 130, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20266, 20, 'crm', '变更状态', '/crm/order/changeStatus', 'changeStatus', 20202, 140, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- ─── Contract 操作 ───
INSERT INTO yoshop_store_menu (menu_id, module, fun_type, name, path, action_mark, parent_id, sort, create_time, update_time) VALUES
(20271, 20, 'crm', '合同列表', '/crm/contract/lists', 'lists', 20203, 100, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20272, 20, 'crm', '合同详情', '/crm/contract/detail', 'detail', 20211, 100, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20273, 20, 'crm', '新增合同', '/crm/contract/add', 'add', 20203, 110, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20274, 20, 'crm', '编辑合同', '/crm/contract/edit', 'edit', 20203, 120, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20275, 20, 'crm', '删除合同', '/crm/contract/delete', 'delete', 20203, 130, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20276, 20, 'crm', '变更状态', '/crm/contract/changeStatus', 'changeStatus', 20203, 140, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- ─── Product 操作 ───
INSERT INTO yoshop_store_menu (menu_id, module, fun_type, name, path, action_mark, parent_id, sort, create_time, update_time) VALUES
(20651, 20, 'crm', '产品列表', '/crm/product/lists', 'lists', 20601, 100, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20652, 20, 'crm', '产品查询', '/crm/product/select', 'select', 20601, 110, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20653, 20, 'crm', '产品详情', '/crm/product/detail', 'detail', 20601, 120, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20654, 20, 'crm', '新增产品', '/crm/product/add', 'add', 20601, 130, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20655, 20, 'crm', '编辑产品', '/crm/product/edit', 'edit', 20601, 140, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20656, 20, 'crm', '删除产品', '/crm/product/delete', 'delete', 20601, 150, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- ─── Knowledge 操作 ───
INSERT INTO yoshop_store_menu (menu_id, module, fun_type, name, path, action_mark, parent_id, sort, create_time, update_time) VALUES
(20351, 20, 'crm', '知识列表', '/crm/knowledge/lists', 'lists', 20301, 100, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20352, 20, 'crm', '新增知识', '/crm/knowledge/add', 'add', 20301, 110, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20353, 20, 'crm', '编辑知识', '/crm/knowledge/edit', 'edit', 20301, 120, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20354, 20, 'crm', '删除知识', '/crm/knowledge/delete', 'delete', 20301, 130, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- ─── FollowUp 操作 ───
INSERT INTO yoshop_store_menu (menu_id, module, fun_type, name, path, action_mark, parent_id, sort, create_time, update_time) VALUES
(20191, 20, 'crm', '跟进列表', '/crm/followup/lists', 'lists', 20101, 200, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20192, 20, 'crm', '新增跟进', '/crm/followup/add', 'add', 20101, 210, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20193, 20, 'crm', '编辑跟进', '/crm/followup/edit', 'edit', 20101, 220, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20194, 20, 'crm', '删除跟进', '/crm/followup/delete', 'delete', 20101, 230, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- ─── CustomField 操作 ───
INSERT INTO yoshop_store_menu (menu_id, module, fun_type, name, path, action_mark, parent_id, sort, create_time, update_time) VALUES
(20701, 20, 'crm', '查询字段', '/crm/customField/lists', 'lists', 20626, 100, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20702, 20, 'crm', '新增字段', '/crm/customField/add', 'add', 20626, 110, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20703, 20, 'crm', '编辑字段', '/crm/customField/edit', 'edit', 20626, 120, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20704, 20, 'crm', '删除字段', '/crm/customField/delete', 'delete', 20626, 130, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20705, 20, 'crm', '启停字段', '/crm/customField/setStatus', 'setStatus', 20626, 140, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- ─── DemandRule 操作 ───
INSERT INTO yoshop_store_menu (menu_id, module, fun_type, name, path, action_mark, parent_id, sort, create_time, update_time) VALUES
(20711, 20, 'crm', '查询规则', '/crm/demandRule/lists', 'lists', 20627, 100, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20712, 20, 'crm', '新增规则', '/crm/demandRule/add', 'add', 20627, 110, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20713, 20, 'crm', '编辑规则', '/crm/demandRule/edit', 'edit', 20627, 120, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20714, 20, 'crm', '删除规则', '/crm/demandRule/delete', 'delete', 20627, 130, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20715, 20, 'crm', '启停规则', '/crm/demandRule/setStatus', 'setStatus', 20627, 140, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- ─── Demand 操作 ───
INSERT INTO yoshop_store_menu (menu_id, module, fun_type, name, path, action_mark, parent_id, sort, create_time, update_time) VALUES
(20716, 20, 'crm', '忽略需求', '/crm/demand/dismiss', 'dismiss', 20627, 150, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20717, 20, 'crm', '更新需求', '/crm/demand/setStatus', 'setStatus', 20627, 160, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- ─── ApiKey 操作 ───
INSERT INTO yoshop_store_menu (menu_id, module, fun_type, name, path, action_mark, parent_id, sort, create_time, update_time) VALUES
(20721, 20, 'crm', '生成密钥', '/crm/apiKey/generate', 'generate', 20628, 100, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20722, 20, 'crm', '删除密钥', '/crm/apiKey/delete', 'delete', 20628, 110, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- ─── AI 操作 ───
INSERT INTO yoshop_store_menu (menu_id, module, fun_type, name, path, action_mark, parent_id, sort, create_time, update_time) VALUES
(20451, 20, 'crm', 'AI对话', '/crm.assistant/chat', 'chat', 20400, 100, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20452, 20, 'crm', '查看档案', '/crm.profile/detail', 'detail', 20313, 100, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- ============================================================
-- Step 4: 角色绑定
-- ============================================================

-- 超管 role_id=1 绑定全部
INSERT INTO yoshop_store_role_menu (role_id, menu_id, store_id, create_time)
SELECT 1, menu_id, 0, UNIX_TIMESTAMP()
FROM yoshop_store_menu WHERE fun_type = 'crm' AND menu_id BETWEEN 20000 AND 20799;

-- 销售经理 role_id=10004 绑定页面菜单
INSERT INTO yoshop_store_role_menu (role_id, menu_id, store_id, create_time)
SELECT 10004, menu_id, 10001, UNIX_TIMESTAMP()
FROM yoshop_store_menu WHERE fun_type = 'crm' AND module = 10 AND menu_id BETWEEN 20000 AND 20799;
