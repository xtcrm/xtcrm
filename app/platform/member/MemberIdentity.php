<?php

declare (strict_types=1);

namespace app\platform\member;

use app\platform\model\PlatformUser;
use app\platform\model\PlatformUserStore;

/**
 * 平台会员身份服务
 */
class MemberIdentity
{
    public static function resolveByMobile(string $mobile): int
    {
        return PlatformUser::resolveByMobile($mobile);
    }

    public static function resolveByWechat(string $openid, string $platform = 'mp'): int
    {
        if (empty($openid)) return 0;
        $id = PlatformUserStore::platformUserIdByOpenid($openid, $platform);
        return $id ?? 0;
    }

    public static function getStoreBindings(int $platformUserId): array
    {
        return PlatformUserStore::getBindings($platformUserId);
    }

    public static function bindToStore(int $platformUserId, int $storeId, int $storeUserId): void
    {
        PlatformUserStore::bind($platformUserId, $storeId, $storeUserId);
    }

    public static function fromStoreUser(int $storeUserId): ?int
    {
        return PlatformUserStore::platformUserId($storeUserId);
    }

    public static function currentId(): ?int
    {
        try {
            $token = request()->header('Access-Token');
            if (empty($token)) return null;

            $data = \think\facade\Cache::get($token);
            if (empty($data) || !isset($data['uid'])) return null;

            return PlatformUserStore::platformUserId((int)$data['uid']);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
