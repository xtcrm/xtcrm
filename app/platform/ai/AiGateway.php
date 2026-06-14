<?php

declare (strict_types=1);

namespace app\platform\ai;

/**
 * AI 统一网关
 *
 * 自包含实现：直接 curl 调 DeepSeek API，不委托 app\common\service\AiService。
 * 提供 request（单次）、chat（多轮）、functionCall（工具调用）。
 */
class AiGateway
{
    const DEFAULT_TIMEOUT = 120;

    /**
     * 单次请求
     */
    public static function request(string $systemPrompt, string $userPrompt, array $options = []): AiResponse
    {
        return self::chat([
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user',   'content' => $userPrompt],
        ], $options);
    }

    /**
     * 多轮对话
     */
    public static function chat(array $messages, array $options = []): AiResponse
    {
        $cfg = AiConfig::get();

        if (empty($cfg['api_key'])) {
            return AiResponse::fail('未配置 API Key');
        }

        try {
            $payload = [
                'model'       => $options['model'] ?? $cfg['model'],
                'messages'    => $messages,
                'temperature' => $options['temperature'] ?? $cfg['temperature'],
                'max_tokens'  => $options['max_tokens'] ?? $cfg['max_tokens'],
            ];

            // functionCall 模式：传入 tools
            if (!empty($options['tools'])) {
                $payload['tools'] = array_map(function ($t) {
                    return ['type' => 'function', 'function' => $t];
                }, $options['tools']);
            }

            $ch = curl_init($cfg['api_url']);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $cfg['api_key'],
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => $options['timeout'] ?? self::DEFAULT_TIMEOUT,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ]);

            $resp = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err  = curl_error($ch);
            curl_close($ch);

            if ($err) return AiResponse::fail('网络错误: ' . $err);
            if ($code !== 200) {
                $errBody = json_decode($resp, true);
                return AiResponse::fail($errBody['error']['message'] ?? "HTTP {$code}");
            }

            $body    = json_decode($resp, true);
            $content = $body['choices'][0]['message']['content'] ?? '';

            return AiResponse::ok(trim($content));

        } catch (\Throwable $e) {
            return AiResponse::fail($e->getMessage());
        }
    }

    /**
     * Function Calling
     *
     * 发送含 tools 的消息，返回 AI 响应（可能含 tool_calls）。
     * 不在此处循环执行工具——由调用方（如 AgentService）控制循环。
     *
     * @param array $messages
     * @param array $tools
     * @return array  ['success' => bool, 'data' => ['type' => 'text'|'tool_call', ...], 'error' => string]
     */
    public static function functionCall(array $messages, array $tools): array
    {
        $cfg = AiConfig::get();

        if (empty($cfg['api_key'])) {
            return ['success' => false, 'data' => null, 'error' => '未配置 API Key'];
        }

        try {
            $payload = [
                'model'       => $cfg['model'],
                'messages'    => $messages,
                'temperature' => 0.3,
                'max_tokens'  => 800,
            ];

            if (!empty($tools)) {
                $payload['tools'] = array_map(function ($t) {
                    return ['type' => 'function', 'function' => $t];
                }, $tools);
            }

            $ch = curl_init($cfg['api_url']);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $cfg['api_key'],
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 60,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ]);

            $resp = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($code !== 200) {
                $errBody = json_decode($resp, true);
                return ['success' => false, 'data' => null, 'error' => $errBody['error']['message'] ?? "HTTP {$code}"];
            }

            $body = json_decode($resp, true);
            $msg  = $body['choices'][0]['message'] ?? [];

            // tool_calls 响应
            if (!empty($msg['tool_calls'])) {
                return [
                    'success' => true,
                    'data' => [
                        'type'       => 'tool_call',
                        'messages'   => $messages,
                        'tool_calls' => $msg['tool_calls'],
                    ],
                    'error' => '',
                ];
            }

            // 文本响应
            return [
                'success' => true,
                'data'    => ['type' => 'text', 'content' => trim($msg['content'] ?? '')],
                'error'   => '',
            ];

        } catch (\Throwable $e) {
            return ['success' => false, 'data' => null, 'error' => $e->getMessage()];
        }
    }
}
