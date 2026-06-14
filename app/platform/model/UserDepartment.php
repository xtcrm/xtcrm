<?php

declare (strict_types=1);

namespace app\platform\model;

use cores\BaseModel;

/**
 * 平台用户-部门关联模型（store_user_department 表）
 */
class UserDepartment extends BaseModel
{
    protected $name = 'store_user_department';
    protected $autoWriteTimestamp = true;

    /**
     * @return static|null
     */
    public static function getByUserId(int $userId)
    {
        return static::where('user_id', $userId)
            ->where('store_id', self::$storeId)
            ->find();
    }

    public static function getByDepartmentId(int $departmentId): array
    {
        return static::where('department_id', $departmentId)
            ->where('store_id', self::$storeId)
            ->select()
            ->toArray();
    }

    public static function addUserToDept(int $userId, int $departmentId, int $isManager = 0): bool
    {
        $exist = static::where('user_id', $userId)
            ->where('department_id', $departmentId)
            ->where('store_id', self::$storeId)
            ->find();

        if ($exist) {
            return $exist->save(['is_manager' => $isManager]);
        }

        return (bool)(new static)->save([
            'user_id'       => $userId,
            'department_id' => $departmentId,
            'is_manager'    => $isManager,
            'store_id'      => self::$storeId,
        ]);
    }

    public static function removeUserFromDepartment(int $userId): bool
    {
        return (bool)static::where('user_id', $userId)
            ->where('store_id', self::$storeId)
            ->delete();
    }
}
