<?php

declare (strict_types=1);

namespace app\platform\model;

use cores\BaseModel;

/**
 * 店铺会员服务模型（member_service 表）
 */
class MemberService extends BaseModel
{
    protected $name = 'member_service';

    /**
     * 获取店铺的会员服务列表
     */
    public static function getByStoreId(int $storeId): array
    {
        return static::where('store_id', $storeId)
            ->where('status', 1)
            ->order('sort', 'asc')
            ->select()->toArray();
    }
}
