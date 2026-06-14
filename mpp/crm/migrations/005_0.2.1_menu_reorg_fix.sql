-- 菜单重组修复版：去掉 parent_id=0 守卫，无条件执行
-- 线索 → 客户管理 | 报价/订单/合同 → 销售管理 | 产品 → 系统设置

INSERT IGNORE INTO yoshop_store_menu (menu_id,module,fun_type,name,path,action_mark,parent_id,sort,create_time,update_time)
VALUES (19901,10,'crm','销售管理','/crm/quotation','',0,120,UNIX_TIMESTAMP(),UNIX_TIMESTAMP())
ON DUPLICATE KEY UPDATE name='销售管理', fun_type='crm', sort=120;

UPDATE yoshop_store_menu SET parent_id=20010, sort=150 WHERE menu_id=20020;
UPDATE yoshop_store_menu SET parent_id=19901 WHERE menu_id=20030;
UPDATE yoshop_store_menu SET parent_id=19901 WHERE menu_id=20040;
UPDATE yoshop_store_menu SET parent_id=19901 WHERE menu_id=20050;
UPDATE yoshop_store_menu SET parent_id=20080, sort=140 WHERE menu_id=20060;
UPDATE yoshop_store_menu SET name='系统设置' WHERE menu_id=20080 AND fun_type='crm';

INSERT IGNORE INTO yoshop_store_role_menu (role_id, menu_id, store_id) VALUES (1, 19901, 0);
