<?php

declare (strict_types=1);

namespace app\platform\auth;

use cores\BaseModel;
use think\facade\Db;

/**
 * 开放平台 API Token
 *
 * 设计原则：
 *   - Token 直接绑定商城管理员，不再要求先建开发者/应用
 *   - 权限两档：read 只读 / write 可编辑
 *   - 只存 SHA256，明文只在生成/轮换时返回一次
 */
class ApiToken extends BaseModel
{
    // 表名
    protected $name = 'api_token';

    // 主键
    protected $pk = 'id';

    // 权限：只读
    const PERMISSION_READ = 'read';

    // 权限：可编辑
    const PERMISSION_WRITE = 'write';

    /**
     * 权限选项
     * @return array
     */
    public static function permissionMap(): array
    {
        return [
            self::PERMISSION_READ => '只读',
            self::PERMISSION_WRITE => '可编辑',
        ];
    }

    /**
     * 生成 Token
     * @param int $storeId
     * @param array $data store_user_id / permission / expire_days / remark
     * @param int $operatorId 后台操作人
     * @return array ['id','token','expire_time']
     */
    public static function generate(int $storeId, array $data, int $operatorId = 0): array
    {
        if ($storeId <= 0) {
            throwError('无法确定当前租户');
        }
        $userId = (int)($data['store_user_id'] ?? 0);
        if ($userId <= 0) {
            throwError('请选择绑定人');
        }
        self::assertStoreUser($storeId, $userId);

        $permission = self::normalizePermission((string)($data['permission'] ?? self::PERMISSION_READ));
        $expireTime = self::parseExpireTime((int)($data['expire_days'] ?? 0));
        $remark = mb_substr(trim((string)($data['remark'] ?? '')), 0, 255);

        $token = self::makeToken();
        $now = time();
        $model = new self;
        $model->save([
            'store_id' => $storeId,
            'store_user_id' => $userId,
            'token_prefix' => substr($token, 0, 16),
            'token_hash' => hash('sha256', $token),
            'permission' => $permission,
            'status' => 1,
            'expire_time' => $expireTime,
            'last_used_at' => 0,
            'last_ip' => '',
            'remark' => $remark,
            'operator_id' => $operatorId,
            'create_time' => $now,
            'update_time' => $now,
        ]);

        return [
            'id' => (int)$model->id,
            'token' => $token,
            'expire_time' => $expireTime,
        ];
    }

    /**
     * 校验 Token
     * @param string $token
     * @return array|null ['token_id','store_id','store_user_id','permission','user_name']
     */
    public static function verify(string $token): ?array
    {
        $token = trim($token);
        if ($token === '') {
            return null;
        }
        $row = Db::name('api_token')
            ->where('token_hash', hash('sha256', $token))
            ->find();
        if (!$row || (int)$row['status'] !== 1) {
            return null;
        }
        if ((int)$row['expire_time'] > 0 && (int)$row['expire_time'] <= time()) {
            return null;
        }
        $storeId = (int)$row['store_id'];
        $userId = (int)$row['store_user_id'];
        $user = Db::name('store_user')
            ->where('store_user_id', $userId)
            ->where('store_id', $storeId)
            ->where('is_delete', 0)
            ->find();
        if (!$user) {
            return null;
        }

        // 记录最近使用
        Db::name('api_token')
            ->where('id', (int)$row['id'])
            ->where('store_id', $storeId)
            ->update([
                'last_used_at' => time(),
                'last_ip' => (string)request()->ip(),
            ]);

        return [
            'token_id' => (int)$row['id'],
            'store_id' => $storeId,
            'store_user_id' => $userId,
            'permission' => self::normalizePermission((string)$row['permission']),
            'user_name' => (string)($user['real_name'] ?: $user['user_name']),
        ];
    }

    /**
     * 编辑 Token 元信息（不改 Token 本身）
     * @param int $id
     * @param int $storeId
     * @param array $data
     * @return bool
     */
    public static function updateMeta(int $id, int $storeId, array $data): bool
    {
        $row = Db::name('api_token')->where('id', $id)->where('store_id', $storeId)->find();
        if (!$row) {
            throwError('Token 不存在');
        }
        $update = ['update_time' => time()];
        if (isset($data['store_user_id'])) {
            $userId = (int)$data['store_user_id'];
            if ($userId <= 0) {
                throwError('请选择绑定人');
            }
            self::assertStoreUser($storeId, $userId);
            $update['store_user_id'] = $userId;
        }
        if (isset($data['permission'])) {
            $update['permission'] = self::normalizePermission((string)$data['permission']);
        }
        if (isset($data['expire_days'])) {
            $update['expire_time'] = self::parseExpireTime((int)$data['expire_days']);
        }
        if (isset($data['status'])) {
            $update['status'] = (int)$data['status'] === 1 ? 1 : 0;
        }
        if (isset($data['remark'])) {
            $update['remark'] = mb_substr(trim((string)$data['remark']), 0, 255);
        }
        return Db::name('api_token')->where('id', $id)->where('store_id', $storeId)->update($update) !== false;
    }

    /**
     * 轮换 Token（重新生成明文，旧 Token 立即失效）
     * @param int $id
     * @param int $storeId
     * @return string 新 Token 明文
     */
    public static function rotate(int $id, int $storeId): string
    {
        $row = Db::name('api_token')->where('id', $id)->where('store_id', $storeId)->find();
        if (!$row) {
            throwError('Token 不存在');
        }
        $token = self::makeToken();
        Db::name('api_token')->where('id', $id)->where('store_id', $storeId)->update([
            'token_prefix' => substr($token, 0, 16),
            'token_hash' => hash('sha256', $token),
            'last_used_at' => 0,
            'update_time' => time(),
        ]);
        return $token;
    }

    /**
     * 删除 Token（只删令牌，不动任何业务数据）
     * @param int $id
     * @param int $storeId
     * @return bool
     */
    public static function remove(int $id, int $storeId): bool
    {
        return Db::name('api_token')->where('id', $id)->where('store_id', $storeId)->delete() > 0;
    }

    /**
     * 统一权限值
     * @param string $permission
     * @return string
     */
    private static function normalizePermission(string $permission): string
    {
        return $permission === self::PERMISSION_WRITE ? self::PERMISSION_WRITE : self::PERMISSION_READ;
    }

    /**
     * 有效期天数 → 到期时间戳（0=永久）
     * @param int $days
     * @return int
     */
    private static function parseExpireTime(int $days): int
    {
        if ($days <= 0) return 0;
        if ($days > 3650) $days = 3650;
        return strtotime('+' . $days . ' days');
    }

    /**
     * 校验绑定人属于当前租户
     * @param int $storeId
     * @param int $userId
     * @return void
     */
    private static function assertStoreUser(int $storeId, int $userId): void
    {
        $exists = Db::name('store_user')
            ->where('store_user_id', $userId)
            ->where('store_id', $storeId)
            ->where('is_delete', 0)
            ->count();
        if (empty($exists)) {
            throwError('绑定的管理员不存在或已停用');
        }
    }

    /**
     * 生成随机 Token
     * @return string
     */
    private static function makeToken(): string
    {
        return 'sk_live_' . bin2hex(random_bytes(24));
    }
}
