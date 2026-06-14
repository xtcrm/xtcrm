<?php

declare (strict_types=1);

namespace app\platform\model;

use cores\BaseModel;

/**
 * 平台用户-店铺映射模型（platform_user_store 表）
 */
class PlatformUserStore extends BaseModel
{
    protected $name = 'platform_user_store';
    protected $pk   = 'id';
    protected $createTime = 'create_time';
    protected $updateTime = false;

    /**
     * 获取平台用户的所有店铺绑定
     */
    public static function getBindings(int $platformUserId): array
    {
        return static::where('platform_user_id', $platformUserId)->select()->toArray();
    }

    /**
     * 绑定平台用户到店铺
     * @return bool 是否新建了绑定（false = 已存在）
     */
    public static function bind(int $platformUserId, int $storeId, int $storeUserId): bool
    {
        $exist = static::where('platform_user_id', $platformUserId)
            ->where('store_id', $storeId)
            ->find();

        if ($exist) return false;

        $isPrimary = static::where('platform_user_id', $platformUserId)->count() === 0;

        static::create([
            'platform_user_id' => $platformUserId,
            'store_id'         => $storeId,
            'store_user_id'    => $storeUserId,
            'is_primary'       => $isPrimary ? 1 : 0,
            'create_time'      => time(),
        ]);
        return true;
    }

    /**
     * 根据店铺用户 ID 反查平台用户 ID
     */
    public static function platformUserId(int $storeUserId): ?int
    {
        $row = static::where('store_user_id', $storeUserId)->find();
        return $row ? (int)$row['platform_user_id'] : null;
    }

    /**
     * 根据 openid 反查平台用户 ID
     */
    public static function platformUserIdByOpenid(string $openid, string $platform = 'mp'): ?int
    {
        $row = \think\facade\Db::name('user_oauth')
            ->alias('o')
            ->join(static::getTable() . ' m', 'o.user_id = m.store_user_id')
            ->where('o.openid', $openid)
            ->where('o.platform', $platform)
            ->field('m.platform_user_id')
            ->find();

        return $row ? (int)$row['platform_user_id'] : null;
    }
}
