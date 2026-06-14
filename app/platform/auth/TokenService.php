<?php

declare (strict_types=1);

namespace app\platform\auth;

use think\facade\Cache;

/**
 * 平台 Token 服务
 *
 * 自包含实现：直接操作 Cache，不委托 app\common\service\store\User。
 * Token 格式：Cache key = md5 hash；value = {uid, sid, exp}。
 */
class TokenService
{
    /** Token 有效期（秒），默认 604800 = 7天 */
    const EXPIRE_SECONDS = 604800;

    /** Token 盐值 */
    const TOKEN_SALT = '_store_user_salt_';

    /**
     * 登录：生成 token，缓存用户信息，返回 token 字符串
     *
     * @param array $user  用户数据，至少包含 store_user_id 和 store_id
     * @return string      token
     */
    public static function login(array $user): string
    {
        $token = self::makeToken((int)$user['store_user_id']);

        Cache::set($token, [
            'uid' => (int)$user['store_user_id'],
            'sid' => (int)$user['store_id'],
            'exp' => time() + self::EXPIRE_SECONDS,
        ], self::EXPIRE_SECONDS);

        return $token;
    }

    /**
     * 校验：从 Request header 中提取 Access-Token，验证有效性
     *
     * @return array|null  成功返回 ['uid' => int, 'sid' => int, 'exp' => int]，失败返回 null
     */
    public static function verify(): ?array
    {
        $token = self::tokenFromRequest();
        if (empty($token)) return null;

        $data = Cache::get($token);
        if (empty($data)) return null;

        // 检查过期
        if (isset($data['exp']) && time() > $data['exp']) {
            Cache::delete($token);
            return null;
        }

        return $data;
    }

    /**
     * 刷新：延长当前 token 的过期时间
     */
    public static function refresh(): bool
    {
        $token = self::tokenFromRequest();
        if (empty($token)) return false;

        $data = Cache::get($token);
        if (empty($data)) return false;

        $data['exp'] = time() + self::EXPIRE_SECONDS;
        Cache::set($token, $data, self::EXPIRE_SECONDS);
        return true;
    }

    /**
     * 登出：清除当前 token
     */
    public static function logout(): bool
    {
        $token = self::tokenFromRequest();
        if (!empty($token)) {
            Cache::delete($token);
        }
        return true;
    }

    /**
     * 更新登录用户信息（缓存刷新用，如编辑用户资料后）
     */
    public static function update(array $user): bool
    {
        $token = self::tokenFromRequest();
        if (empty($token)) return false;

        // 保留原有 exp
        $existing = Cache::get($token);
        $exp = $existing['exp'] ?? (time() + self::EXPIRE_SECONDS);

        return Cache::set($token, [
            'uid' => (int)$user['store_user_id'],
            'sid' => (int)$user['store_id'],
            'exp' => $exp,
        ], self::EXPIRE_SECONDS);
    }

    /**
     * 获取当前登录用户 ID
     */
    public static function userId(): ?int
    {
        $data = self::verify();
        return $data ? (int)$data['uid'] : null;
    }

    /**
     * 获取当前租户 ID
     */
    public static function storeId(): ?int
    {
        $data = self::verify();
        return $data ? (int)$data['sid'] : null;
    }

    /**
     * 获取当前登录用户的完整信息（从 DB 查 store_user 表）
     */
    public static function getUser(): ?array
    {
        $uid = self::userId();
        if (empty($uid)) return null;

        $storeId = self::storeId();
        $user = \think\facade\Db::name('store_user')
            ->where('store_user_id', $uid)
            ->where('store_id', $storeId)
            ->find();

        return $user ?: null;
    }

    /**
     * 获取 Token 有效期（秒）
     */
    public static function getExpireSeconds(): int
    {
        return self::EXPIRE_SECONDS;
    }

    /**
     * 检查当前用户是否已过期
     */
    public static function isExpired(): bool
    {
        $data = self::verify();
        return empty($data);
    }

    /**
     * 生成 token 字符串
     */
    private static function makeToken(int $userId): string
    {
        $stamp  = microtime(true);
        $random = bin2hex(random_bytes(16));
        $salt   = self::TOKEN_SALT;
        return hash_hmac('sha256', "{$stamp}_{$userId}_{$random}", $salt);
    }

    /**
     * 从请求中提取 Access-Token
     */
    private static function tokenFromRequest(): ?string
    {
        $token = request()->header('Access-Token');
        return $token ?: null;
    }
}
