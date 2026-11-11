ALTER TABLE yoshop_crm_contact ADD COLUMN is_lunar tinyint(1) NOT NULL DEFAULT 0 COMMENT '0公历 1农历' AFTER birthday;
