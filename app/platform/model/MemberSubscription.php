<?php

declare (strict_types=1);

namespace app\platform\model;

use cores\BaseModel;

/**
 * 会员订阅模型（member_user 表）
 */
class MemberSubscription extends BaseModel
{
    protected $name = 'member_user';

    /**
     * 获取用户有效订阅列表
     */
    public static function getActiveByUserId(int $storeUserId): array
    {
        return \think\facade\Db::name('member_user')->alias('mu')
            ->join('member_service ms', 'mu.service_id = ms.id')
            ->where('mu.user_id', $storeUserId)
            ->where('mu.status', 1)
            ->field('mu.*, ms.name as service_name, ms.type as service_type')
            ->order('mu.expire_time', 'desc')
            ->select()->toArray();
    }

    /**
     * 是否有有效订阅
     */
    public static function hasActive(int $storeUserId): bool
    {
        return static::where('user_id', $storeUserId)
            ->where('status', 1)
            ->where('expire_time', '>', time())
            ->count() > 0;
    }
}
