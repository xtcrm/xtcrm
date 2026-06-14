<?php

declare (strict_types=1);

namespace app\platform\model;

use cores\BaseModel;

/**
 * 平台用户模型（platform_user 表）
 *
 * 跨店铺唯一身份标识，按手机号去重。
 */
class PlatformUser extends BaseModel
{
    protected $name = 'platform_user';
    protected $pk   = 'id';
    protected $createTime = 'create_time';
    protected $updateTime = false;

    /**
     * 通过手机号查找
     * @return static|null
     */
    public static function findByMobile(string $mobile)
    {
        if (empty($mobile)) return null;
        return static::where('mobile', $mobile)->find();
    }

    /**
     * 通过手机号查找或创建
     */
    public static function resolveByMobile(string $mobile): int
    {
        if (empty($mobile)) return 0;

        $user = static::findByMobile($mobile);
        if ($user) return (int)$user['id'];

        $new = static::create(['mobile' => $mobile, 'create_time' => time()]);
        return (int)$new['id'];
    }
}
