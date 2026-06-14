<?php

declare (strict_types=1);

namespace app\platform\member;

use app\platform\model\ShopUser;

/**
 * 平台会员钱包服务
 */
class MemberWallet
{
    public static function get(int $storeUserId): array
    {
        return ShopUser::wallet($storeUserId);
    }

    public static function getTotal(int $platformUserId): array
    {
        $bindings = MemberIdentity::getStoreBindings($platformUserId);
        if (empty($bindings)) {
            return ['balance' => 0, 'points' => 0, 'subsidy_balance' => 0, 'pay_money' => 0, 'expend_money' => 0];
        }
        $storeUserIds = array_column($bindings, 'store_user_id');
        return ShopUser::walletTotal($storeUserIds);
    }

    public static function changeBalance(int $storeUserId, float $amount, string $reason = ''): bool
    {
        if ($amount == 0) return true;
        return ShopUser::incBalance($storeUserId, $amount);
    }

    public static function changePoints(int $storeUserId, int $points, string $reason = ''): bool
    {
        if ($points == 0) return true;
        return ShopUser::incPoints($storeUserId, $points);
    }
}
