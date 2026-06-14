<?php

declare (strict_types=1);

namespace app\platform\enum;

use app\common\enum\EnumBasics;

/**
 * 枚举类：文件类型
 */
class FileType extends EnumBasics
{
    const IMAGE = 10;
    const ANNEX = 20;
    const VIDEO = 30;

    public static function data(): array
    {
        return [
            self::IMAGE => ['name' => '图片', 'value' => self::IMAGE],
            self::ANNEX => ['name' => '附件', 'value' => self::ANNEX],
            self::VIDEO => ['name' => '视频', 'value' => self::VIDEO],
        ];
    }
}
