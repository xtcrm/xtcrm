<?php

declare (strict_types = 1);

namespace app\common\enum\order;

use app\common\enum\EnumBasics;

/**
 * 枚举类：配送方式
 * Class DeliveryType
 * @package app\common\enum\order
 */
class DeliveryType extends EnumBasics
{
    // 快递配送
    const EXPRESS = 10;

    // 上门自提
    const EXTRACT = 20;

    // 无需配送
    const NOTHING = 30;

    /**
     * 获取枚举数据
     * @return array
     */
    public static function data(): array
    {
        return [
            self::EXPRESS => [
                'name' => '快递配送',
                'value' => self::EXPRESS,
            ],
            self::EXTRACT => [
                'name' => '上门自提',
                'value' => self::EXTRACT,
            ],
            self::NOTHING => [
                'name' => '无需配送',
                'value' => self::NOTHING,
            ]
        ];
    }
}