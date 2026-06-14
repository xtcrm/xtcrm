<?php

declare (strict_types=1);

namespace app\platform\member;

use app\platform\model\ShopUser;
use app\platform\model\PlatformUser;

/**
 * 平台会员档案服务
 */
class MemberProfile
{
    public static function get(int $storeUserId): ?array
    {
        return ShopUser::profile($storeUserId);
    }

    public static function update(int $storeUserId, array $data): bool
    {
        $allowed = ['nick_name', 'avatar_id', 'gender'];
        $update = array_intersect_key($data, array_flip($allowed));
        if (empty($update)) return false;
        return ShopUser::updateField($storeUserId, $update);
    }

    public static function changeMobile(int $platformUserId, string $newMobile): bool
    {
        if (empty($newMobile)) return false;

        // 更新 platform_user 主手机号
        PlatformUser::where('id', $platformUserId)->update(['mobile' => $newMobile]);

        // 同步所有已绑定店铺的 yoshop_user.mobile
        $bindings = MemberIdentity::getStoreBindings($platformUserId);
        foreach ($bindings as $b) {
            ShopUser::updateField($b['store_user_id'], ['mobile' => $newMobile]);
        }

        return true;
    }
}
