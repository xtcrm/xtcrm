<?php

declare (strict_types=1);

namespace app\platform\model;

use cores\BaseModel;

/**
 * 平台菜单模型（store_menu 表）
 */
class Menu extends BaseModel
{
    protected $name = 'store_menu';
    protected $pk   = 'menu_id';

    public static function detail($where)
    {
        $query = static::withoutGlobalScope();
        is_array($where) ? $query->where($where) : $query->where('menu_id', $where);
        return $query->find();
    }
}
