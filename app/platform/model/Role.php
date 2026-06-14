<?php

declare (strict_types=1);

namespace app\platform\model;

use cores\BaseModel;

/**
 * 平台角色模型（store_role 表）
 */
class Role extends BaseModel
{
    protected $name = 'store_role';
    protected $pk   = 'role_id';

    public function roleMenu()
    {
        return $this->hasMany(RoleMenu::class, 'role_id');
    }

    public static function detail($where)
    {
        return static::get($where);
    }
}
