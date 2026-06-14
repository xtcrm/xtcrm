<?php

declare (strict_types=1);

namespace app\platform\model;

use cores\BaseModel;

/**
 * 用户等级模型（user_grade 表）
 */
class UserGrade extends BaseModel
{
    protected $name = 'user_grade';
    protected $pk   = 'grade_id';

    public static function getByUserId(int $storeUserId): ?array
    {
        $user = \think\facade\Db::name('user')
            ->where('user_id', $storeUserId)
            ->field('grade_id')
            ->find();

        if (!$user || empty($user['grade_id'])) return null;

        return static::where('grade_id', $user['grade_id'])
            ->where('status', 1)
            ->find() ?: null;
    }
}
