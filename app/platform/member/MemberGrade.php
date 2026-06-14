<?php

declare (strict_types=1);

namespace app\platform\member;

use app\platform\model\UserGrade;
use app\platform\model\MemberSubscription;
use app\platform\model\MemberService;

/**
 * 平台会员等级服务
 */
class MemberGrade
{
    public static function get(int $storeUserId): ?array
    {
        return UserGrade::getByUserId($storeUserId);
    }

    public static function getSubscriptions(int $storeUserId): array
    {
        return MemberSubscription::getActiveByUserId($storeUserId);
    }

    public static function hasActiveSubscription(int $storeUserId): bool
    {
        return MemberSubscription::hasActive($storeUserId);
    }

    public static function getStoreServices(int $storeId): array
    {
        return MemberService::getByStoreId($storeId);
    }
}
