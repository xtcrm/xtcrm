-- 代开发票 Skill 菜单（应用 > 代开发票 > ...）
INSERT IGNORE INTO yoshop_store_menu (menu_id, module, fun_type, name, path, action_mark, parent_id, sort, create_time, update_time) VALUES
(21008, 10, 'platform', '会员列表', '/app/invoice/member', '', 21001, 130, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
