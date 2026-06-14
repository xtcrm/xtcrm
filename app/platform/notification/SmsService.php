<?php

declare (strict_types=1);

namespace app\platform\notification;

use app\common\service\Message;

/**
 * 平台短信服务
 *
 * 短信场景通过 Message 的 scene 机制分发（基础设斝，不做重写）。
 */
class SmsService
{
    /**
     * 发送短信验证码
     * @return bool
     */
    public static function sendCaptcha(string $phone, string $code, int $storeId): bool
    {
        return (bool)Message::send('passport.captcha', [
            'mobile' => $phone,
            'code'   => $code,
        ], $storeId);
    }

    /**
     * 发送指定场景的短信
     * @return bool
     */
    public static function send(string $sceneName, array $param, int $storeId): bool
    {
        return (bool)Message::send($sceneName, $param, $storeId);
    }
}
