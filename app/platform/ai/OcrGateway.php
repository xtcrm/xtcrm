<?php

declare (strict_types=1);

namespace app\platform\ai;

/**
 * 阿里云 OCR 网关
 *
 * 调用 RecognizeAllText（Type=Advanced），参数走 query string（HMAC-SHA1 签名），
 * 图片二进制走 POST body（Content-Type: application/octet-stream）。
 *
 * 识别文字由 AiGateway (DeepSeek) 进一步结构化。
 */
class OcrGateway
{
    const DEFAULT_TIMEOUT = 30;
    const API_VERSION = '2021-07-07';
    const SIGN_METHOD = 'HMAC-SHA1';

    /**
     * 测试连通性：用 GD 生成小 PNG 调 RecognizeAllText
     */
    public static function test(?int $storeId = null): bool
    {
        $cfg = OcrConfig::get($storeId);
        if (empty($cfg['access_key_id'])) return false;

        // GD 生成有效 PNG
        $im = imagecreatetruecolor(20, 20);
        $bg = imagecolorallocate($im, 255, 255, 255);
        $fg = imagecolorallocate($im, 0, 0, 0);
        imagefill($im, 0, 0, $bg);
        imagestring($im, 2, 2, 2, 'OK', $fg);
        ob_start(); imagepng($im); $pngData = ob_get_clean(); imagedestroy($im);

        $result = self::recognize(base64_encode($pngData));
        return $result->success;
    }

    /**
     * 识别图片中的全部文字
     */
    public static function recognize(string $imageBase64, array $options = []): AiResponse
    {
        $cfg = OcrConfig::get();

        if (!OcrConfig::isConfigured()) {
            return AiResponse::fail('未配置 OCR 服务');
        }

        try {
            $imageData = base64_decode($imageBase64);
            if (empty($imageData)) {
                return AiResponse::fail('图片数据为空');
            }

            $params = self::buildCommonParams($cfg, 'RecognizeAllText');
            $params['Type'] = 'Advanced';
            $params['Version'] = self::API_VERSION;

            self::sign($params, 'POST', $cfg['access_key_secret']);

            $url = 'https://' . $cfg['endpoint'] . '/?' . self::buildQuery($params);

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $imageData,
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/octet-stream',
                    'Content-Length: ' . strlen($imageData),
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => $options['timeout'] ?? self::DEFAULT_TIMEOUT,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);

            $resp = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err  = curl_error($ch);
            curl_close($ch);

            if ($err) return AiResponse::fail('OCR 网络错误: ' . $err);
            if ($code !== 200) {
                return AiResponse::fail("OCR HTTP {$code}: " . substr($resp, 0, 300));
            }

            $result = json_decode($resp, true);
            if (empty($result)) {
                return AiResponse::fail('OCR 响应解析失败');
            }

            $content = $result['Data']['Content'] ?? '';
            if (empty($content)) {
                return AiResponse::fail('OCR 未识别到文字');
            }

            return AiResponse::ok(trim($content));

        } catch (\Throwable $e) {
            return AiResponse::fail('OCR 异常: ' . $e->getMessage());
        }
    }

    /**
     * 构建公共参数
     */
    private static function buildCommonParams(array $cfg, string $action): array
    {
        return [
            'AccessKeyId'      => $cfg['access_key_id'],
            'Action'           => $action,
            'Format'           => 'JSON',
            'SignatureMethod'  => self::SIGN_METHOD,
            'SignatureNonce'   => bin2hex(random_bytes(16)),
            'SignatureVersion' => '1.0',
            'Timestamp'        => gmdate('Y-m-d\TH:i:s\Z'),
        ];
    }

    /**
     * 签名并追加 Signature 参数
     */
    private static function sign(array &$params, string $method, string $secret): void
    {
        ksort($params);
        $canonical = '';
        foreach ($params as $k => $v) {
            $canonical .= '&' . self::percentEncode($k) . '=' . self::percentEncode($v);
        }
        $stringToSign = $method . '&%2F&' . self::percentEncode(substr($canonical, 1));
        $params['Signature'] = base64_encode(hash_hmac('sha1', $stringToSign, $secret . '&', true));
    }

    /**
     * 构建 query string（已排序+编码）
     */
    private static function buildQuery(array $params): string
    {
        ksort($params);
        $parts = [];
        foreach ($params as $k => $v) {
            $parts[] = self::percentEncode($k) . '=' . self::percentEncode($v);
        }
        return implode('&', $parts);
    }

    /**
     * 阿里云 OpenAPI 编码
     */
    private static function percentEncode(string $str): string
    {
        $str = rawurlencode($str);
        return str_replace(['%2A', '%7E', '+'], ['*', '~', '%20'], $str);
    }
}
