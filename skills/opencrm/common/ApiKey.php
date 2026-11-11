<?php
namespace skills\opencrm\common;

use cores\BaseModel;

/**
 * 开源版占位：opencrm skill 未开源
 *
 * CRM 的「系统设置 → API 密钥」只需要一张表 + 生成 Key 的能力，
 * 不依赖 opencrm 的 v1 接口实现。表结构见
 * mpp/crm/migrations/000_0.0.1_public_closed_skill_schema.sql。
 *
 * 如需完整的开放 API（/v1/opencrm.*），请使用闭源 skill：skills/opencrm。
 */
class ApiKey extends BaseModel
{
    protected $name = 'crm_api_key';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';

    /** 生成新 Key */
    public static function generate(int $storeId, string $name, string $scopes = 'read', int $storeUserId = 0): string
    {
        $key = 'sk-' . bin2hex(random_bytes(24));
        (new self)->save([
            'store_id' => $storeId,
            'store_user_id' => $storeUserId,
            'name' => $name,
            'api_key' => $key,
            'scopes' => $scopes,
            'status' => 1,
            'create_time' => time(),
        ]);
        return $key;
    }
}
