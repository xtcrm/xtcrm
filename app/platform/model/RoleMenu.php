<?php

declare (strict_types=1);

namespace app\platform\model;

use cores\BaseModel;

/**
 * 平台角色菜单关联模型（store_role_menu 表）
 */
class RoleMenu extends BaseModel
{
    protected $name = 'store_role_menu';
    protected $updateTime = false;

    public static function getMenuIds(array $roleIds): array
    {
        return (new static)->whereIn('role_id', $roleIds)->column('menu_id');
    }

    public static function increased(int $roleId, array $menuIds)
    {
        $data = [];
        foreach ($menuIds as $menuId) {
            $data[] = [
                'role_id'  => $roleId,
                'menu_id'  => $menuId,
                'store_id' => self::$storeId,
            ];
        }
        return (new static)->addAll($data);
    }

    public static function updates(int $roleId, array $newMenus)
    {
        $assignMenuIds = self::getMenuIds([$roleId]);
        $deleteMenuIds = array_diff($assignMenuIds, $newMenus);
        if (!empty($deleteMenuIds)) {
            self::deleteAll([
                ['role_id', '=', $roleId],
                ['menu_id', 'in', $deleteMenuIds],
            ]);
        }
        $newMenuIds = array_diff($newMenus, $assignMenuIds);
        $data = [];
        foreach ($newMenuIds as $menuId) {
            $data[] = [
                'role_id'  => $roleId,
                'menu_id'  => $menuId,
                'store_id' => self::$storeId,
            ];
        }
        return (new static)->addAll($data);
    }
}
