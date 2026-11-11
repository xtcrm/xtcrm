<?php

declare (strict_types=1);

namespace app\common\enum\payment;

use app\common\enum\EnumBasics;

/**
 * 枚举类：支付方式类型
 * Class Method
 * @package app\common\enum\payment
 */
class Method extends EnumBasics
{
    // 微信支付
    const WECHAT = 'wechat';

    // 支付宝支付
    const ALIPAY = 'alipay';

    // 余额支付
    const BALANCE = 'balance';

    /**
     * 获取类型值
     * @return array
     */
    public static function data(): array
    {
        return [
            self::WECHAT => [
                'name' => '微信支付',
                'value' => self::WECHAT,
            ],
            self::ALIPAY => [
                'name' => '支付宝',
                'value' => self::ALIPAY,
            ],
            self::BALANCE => [
                'name' => '余额支付',
                'value' => self::BALANCE,
            ]
        ];
    }
}
