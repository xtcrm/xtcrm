<?php

declare (strict_types=1);

namespace app\platform\enum;

use app\common\enum\EnumBasics;

/**
 * 平台设置枚举类
 */
class Setting extends EnumBasics
{
    const STORAGE = 'storage';
    const SMS     = 'sms';
    const REGISTER = 'register';

    public static function data(): array
    {
        return [
            self::STORAGE  => ['value' => self::STORAGE,  'describe' => '上传设置'],
            self::SMS      => ['value' => self::SMS,      'describe' => '短信通知'],
            self::REGISTER => ['value' => self::REGISTER, 'describe' => '注册设置'],
        ];
    }
}
