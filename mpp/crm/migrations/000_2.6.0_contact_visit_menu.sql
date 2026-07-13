-- CRM v0.4.0 — 联系人管理 + 今日拜访 菜单
INSERT IGNORE INTO yoshop_store_menu (menu_id, module, fun_type, name, path, action_mark, parent_id, sort, create_time, update_time) VALUES
(20200, 10, 'crm', '联系人管理', '/crm/contact/list', '', 20010, 205, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20201, 10, 'crm', '联系人详情', '/crm/contact/detail', '', 20010, 206, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20202, 10, 'crm', '解除绑定', '/crm/contact/unbind', '', 20010, 207, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20203, 10, 'crm', '今日拜访', '/crm/customer/today-visit', '', 20010, 200, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
