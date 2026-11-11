-- 平台用户表（跨店铺唯一身份）
CREATE TABLE IF NOT EXISTS `yoshop_platform_user` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `mobile` varchar(20) NOT NULL DEFAULT '' COMMENT '主手机号',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_mobile` (`mobile`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='平台用户表（跨店铺唯一身份）';

-- 平台用户-店铺映射表
CREATE TABLE IF NOT EXISTS `yoshop_platform_user_store` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `platform_user_id` int unsigned NOT NULL COMMENT '平台用户ID',
  `store_id` int unsigned NOT NULL COMMENT '店铺ID',
  `store_user_id` int unsigned NOT NULL COMMENT '对应 yoshop_user.user_id',
  `is_primary` tinyint NOT NULL DEFAULT '0' COMMENT '是否主店铺',
  `create_time` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_platform_store` (`platform_user_id`, `store_id`),
  KEY `idx_store_user` (`store_id`, `store_user_id`),
  KEY `idx_platform_user` (`platform_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='平台用户-店铺映射表';
