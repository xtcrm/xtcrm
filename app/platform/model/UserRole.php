<?php

declare (strict_types=1);

namespace app\platform\model;

use think\model\Pivot;

/**
 * 平台用户角色关联模型（store_user_role 表）
 *
 * 必须继承 Pivot：作为 BelongsToMany 中间表模型使用。
 */
class UserRole extends Pivot
{
    protected $name = 'store_user_role';
    protected $pk   = 'id';
    protected $updateTime = false;

    public static function increased(int $storeUserId, array $roleIds)
    {
        $storeId = \cores\BaseModel::$storeId;
        $data = [];
        foreach ($roleIds as $roleId) {
            $data[] = [
                'store_user_id' => $storeUserId,
                'role_id'       => $roleId,
                'store_id'      => $storeId,
            ];
        }
        return (new static)->insertAll($data);
    }

    public static function updates(int $storeUserId, array $newRoles)
    {
        $assignRoleIds = self::getRoleIdsByUserId($storeUserId);
        $deleteRoleIds = array_diff($assignRoleIds, $newRoles);
        if (!empty($deleteRoleIds)) {
            static::where('store_user_id', $storeUserId)
                ->whereIn('role_id', $deleteRoleIds)
                ->delete();
        }
        $newRoleIds = array_diff($newRoles, $assignRoleIds);
        if (empty($newRoleIds)) return;

        $storeId = \cores\BaseModel::$storeId;
        $data = [];
        foreach ($newRoleIds as $roleId) {
            $data[] = [
                'store_user_id' => $storeUserId,
                'role_id'       => $roleId,
                'store_id'      => $storeId,
            ];
        }
        return (new static)->insertAll($data);
    }

    public static function getRoleIdsByUserId(int $storeUserId): array
    {
        return (new static)->where('store_user_id', $storeUserId)->column('role_id');
    }

    public static function isExistsUserByRoleId(int $roleId): bool
    {
        return !!(new static)->where('role_id', $roleId)->count();
    }

    public static function getUserIdsByRoleIds(array $roleIds): array
    {
        return (new static)->whereIn('role_id', $roleIds)->column('store_user_id');
    }
}
