<?php

declare (strict_types=1);

namespace app\platform\notification;

use think\facade\Db;

/**
 * 阿里云短信网关
 *
 * 直接调阿里云 SendSms API（OpenAPI V1 签名 + form-encoded POST）。
 * 文档: https://help.aliyun.com/document_detail/419273.html
 */
class SmsGateway
{
    const API_URL = 'https://dysmsapi.aliyuncs.com/';
    const SIGN_METHOD = 'HMAC-SHA1';

    /**
     * 发送短信
     *
     * @param string $phone    手机号
     * @param string $signName 短信签名
     * @param string $templateCode 模板CODE
     * @param array  $templateParam 模板变量 ['code' => '1234']
     * @param int|null $storeId
     * @return array ['success' => bool, 'error' => string]
     */
    public static function send(string $phone, string $signName, string $templateCode, array $templateParam = [], ?int $storeId = null): array
    {
        $cfg = self::config($storeId);
        if (empty($cfg['access_key_id'])) {
            return ['success' => false, 'error' => '未配置短信服务'];
        }

        try {
            $params = [
                'AccessKeyId'      => $cfg['access_key_id'],
                'Action'           => 'SendSms',
                'Format'           => 'JSON',
                'OutId'            => '',
                'PhoneNumbers'     => $phone,
                'SignName'         => $signName,
                'SignatureMethod'  => self::SIGN_METHOD,
                'SignatureNonce'   => bin2hex(random_bytes(16)),
                'SignatureVersion' => '1.0',
                'TemplateCode'     => $templateCode,
                'TemplateParam'    => json_encode($templateParam, JSON_UNESCAPED_UNICODE),
                'Timestamp'        => gmdate('Y-m-d\TH:i:s\Z'),
                'Version'          => '2017-05-25',
            ];

            ksort($params);
            $canonical = '';
            foreach ($params as $k => $v) {
                $canonical .= '&' . self::pct($k) . '=' . self::pct($v);
            }
            $signStr = 'POST&%2F&' . self::pct(substr($canonical, 1));
            $params['Signature'] = base64_encode(hash_hmac('sha1', $signStr, $cfg['access_key_secret'] . '&', true));

            $postBody = http_build_query($params);

            $ch = curl_init(self::API_URL);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $postBody,
                CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);

            $resp = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($code !== 200) {
                return ['success' => false, 'error' => "HTTP {$code}: " . substr($resp, 0, 200)];
            }

            $result = json_decode($resp, true);
            if (($result['Code'] ?? '') === 'OK') {
                return ['success' => true, 'error' => ''];
            }
            return ['success' => false, 'error' => $result['Message'] ?? $result['Code'] ?? '发送失败'];

        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private static function config(?int $storeId): array
    {
        if ($storeId === null) {
            try { $storeId = app()->request->storeId() ?? 0; }
            catch (\Throwable $e) { $storeId = 0; }
        }

        $rows = Db::name('config')
            ->where('store_id', $storeId)
            ->where('config_type', 'sms_setting')
            ->select()->toArray();

        $cfg = ['access_key_id' => '', 'access_key_secret' => '', 'sign_name' => '', 'template_code' => ''];
        foreach ($rows as $r) {
            switch ($r['config_name']) {
                case 'sms_ak': $cfg['access_key_id'] = $r['config_value']; break;
                case 'sms_sk': $cfg['access_key_secret'] = $r['config_value']; break;
                case 'sms_sign': $cfg['sign_name'] = $r['config_value']; break;
                case 'sms_template': $cfg['template_code'] = $r['config_value']; break;
            }
        }
        return $cfg;
    }

    private static function pct(string $s): string
    {
        return str_replace(['%2A','%7E','+'], ['*','~','%20'], rawurlencode($s));
    }
}
