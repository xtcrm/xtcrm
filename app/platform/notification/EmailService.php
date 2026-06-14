<?php

declare (strict_types=1);

namespace app\platform\notification;

use think\facade\Log;

/**
 * 平台邮件服务
 *
 * 基于 PHP mail() 函数发送。如需 SMTP/第三方邮件服务，替换 send 方法的实现即可，
 * 接口不变。
 */
class EmailService
{
    /**
     * 发送邮件
     *
     * @param string $to      收件人邮箱
     * @param string $subject 主题
     * @param string $body    正文（支持 HTML）
     * @return bool
     */
    public static function send(string $to, string $subject, string $body): bool
    {
        // 暂时使用 PHP 原生 mail，后续可切换为 SMTP 或第三方服务
        try {
            $headers = implode("\r\n", [
                'MIME-Version: 1.0',
                'Content-type: text/html; charset=utf-8',
                'From: ' . (self::fromAddress() ?: 'noreply@example.com'),
            ]);

            $result = mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, $headers);

            if (!$result) {
                Log::warning("邮件发送失败: to={$to}, subject={$subject}");
            }

            return $result;
        } catch (\Throwable $e) {
            Log::error("邮件发送异常: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * 获取发件人地址（可从配置读取）
     */
    private static function fromAddress(): string
    {
        // 后续从平台配置读取
        return '';
    }
}
