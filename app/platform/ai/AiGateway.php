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
     * @param int|null $storeId 显式租户（默认从请求上下文解析；队列等无请求场景可显式传）
     */
    public static function request(string $systemPrompt, string $userPrompt, array $options = [], ?int $storeId = null): AiResponse
    {
        return self::chat([
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user',   'content' => $userPrompt],
        ], $options, $storeId);
    }

    /**
     * 多轮对话
     */
    public static function chat(array $messages, array $options = [], ?int $storeId = null): AiResponse
    {
        return self::chatWithConfig(AiConfig::get($storeId), $messages, $options);
    }

    /**
     * 用显式配置发起对话
     *
     * 设置页「测试连接」需要验证尚未保存的配置，所以把配置作为参数传入。
     *
     * @param array $cfg ['api_url','api_key','model','temperature','max_tokens']
     */
    public static function chatWithConfig(array $cfg, array $messages, array $options = []): AiResponse
    {
        if (empty($cfg['api_key'])) {
            return AiResponse::fail('未配置 API Key');
        }
        if (empty($cfg['api_url'])) {
            return AiResponse::fail('未配置 AI 接口地址');
        }
        if (empty($cfg['model'])) {
            return AiResponse::fail('还没有选择模型，请到「设置 → AI 配置」选择');
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

            // 结构化 JSON 输出：仅当调用方显式传入时透传，旧调用不受影响
            if (!empty($options['response_format'])) {
                $payload['response_format'] = $options['response_format'];
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
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
            ]);

            $resp = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err  = curl_error($ch);
            curl_close($ch);

            if ($err) return AiResponse::fail('网络错误: ' . $err);
            if ($code !== 200) {
                $errBody = json_decode($resp, true);
                $message = $errBody['error']['message'] ?? $errBody['message'] ?? "HTTP {$code}";
                if (($errBody['code'] ?? '') === 'INVALID_API_KEY') {
                    $message = 'API Key 无效，请到「设置 → AI 配置」检查';
                }
                return AiResponse::fail($message);
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
                'temperature' => 0.1,
                'max_tokens'  => $cfg['max_tokens'],
                'tool_choice' => 'auto',
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
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
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

    /**
     * Function Calling 流式版 —— 每收到 LLM chunk 即调 $onChunk
     *
     * @param array    $messages
     * @param array    $tools
     * @param callable $onChunk function(array $event): void
     *   $event 类型：
     *     ['type' => 'text_chunk',    'content' => '深圳']
     *     ['type' => 'tool_call_start','id' => 'call_xxx', 'name' => 'search_customers']
     *     ['type' => 'tool_call_args', 'id' => 'call_xxx', 'fragment' => '{"key']
     *     ['type' => 'tool_calls_finished']  // 本轮所有 tool_calls 收集完毕
     *     ['type' => 'finished']             // LLM 流结束
     *
     * @throws \Throwable
     */
    public static function functionCallStream(array $messages, array $tools, callable $onChunk): void
    {
        $cfg = AiConfig::get();

        if (empty($cfg['api_key'])) {
            throw new \RuntimeException('未配置 API Key');
        }

        $payload = [
            'model'       => $cfg['model'],
            'messages'    => $messages,
            'temperature' => 0.3,
            'max_tokens'  => $cfg['max_tokens'],
            'stream'      => true,
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
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_TIMEOUT        => 90,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_WRITEFUNCTION  => function ($ch, $raw) use ($onChunk) {
                // DeepSeek 流式响应是 SSE 格式，每行 "data: {...}"
                foreach (explode("\n", $raw) as $line) {
                    $line = trim($line);
                    if ($line === '') continue;
                    if (!str_starts_with($line, 'data: ')) continue;

                    $json = substr($line, 6);
                    if ($json === '[DONE]') {
                        $onChunk(['type' => 'finished']);
                        continue;
                    }

                    $chunk = json_decode($json, true);
                    if (!$chunk) continue;

                    $choices = $chunk['choices'] ?? [];
                    if (empty($choices)) continue;

                    $delta  = $choices[0]['delta'] ?? [];
                    $finish = $choices[0]['finish_reason'] ?? null;

                    // 文本增量 → 逐字推送
                    if (!empty($delta['content'])) {
                        $onChunk(['type' => 'text_chunk', 'content' => $delta['content']]);
                    }

                    // tool_call 增量（DeepSeek 流式模式下是分片返回的）
                    if (!empty($delta['tool_calls'])) {
                        foreach ($delta['tool_calls'] as $tc) {
                            $id  = $tc['id'] ?? '';
                            $fn  = $tc['function'] ?? [];
                            $idx = $tc['index'] ?? 0;

                            // 新的 tool_call 开始（有 function.name）
                            if (!empty($fn['name'])) {
                                $onChunk([
                                    'type'  => 'tool_call_start',
                                    'id'    => $id,
                                    'name'  => $fn['name'],
                                    'index' => $idx,
                                ]);
                            }
                            // arguments 片段
                            if (!empty($fn['arguments'])) {
                                $onChunk([
                                    'type'     => 'tool_call_args',
                                    'id'       => $id,
                                    'fragment' => $fn['arguments'],
                                    'index'    => $idx,
                                ]);
                            }
                        }
                    }

                    // finish_reason = tool_calls → 本轮所有工具调用已完整
                    if ($finish === 'tool_calls') {
                        $onChunk(['type' => 'tool_calls_finished']);
                    }
                }
                return strlen($raw);
            },
        ]);

        curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            throw new \RuntimeException('流式调用失败: ' . $err);
        }
    }

    /**
     * 拉取可用模型列表
     *
     * @param string $url    模型列表地址（/v1/models）
     * @param string $apiKey
     * @return array ['success' => bool, 'data' => string[], 'error' => string]
     */
    public static function listModels(string $url, string $apiKey): array
    {
        if ($url === '') {
            return ['success' => false, 'data' => [], 'error' => '未配置 AI 接口地址'];
        }
        if ($apiKey === '') {
            return ['success' => false, 'data' => [], 'error' => '请先填写 API Key'];
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $apiKey],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        $resp = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($err !== '') {
            return ['success' => false, 'data' => [], 'error' => '网络错误：' . $err];
        }

        $body = json_decode((string)$resp, true);
        if ($code !== 200) {
            $message = $body['error']['message'] ?? $body['message'] ?? ('HTTP ' . $code);
            if (($body['code'] ?? '') === 'INVALID_API_KEY') {
                $message = 'API Key 无效，请检查后再试';
            }
            return ['success' => false, 'data' => [], 'error' => $message];
        }

        $ids = [];
        foreach ((array)($body['data'] ?? []) as $m) {
            $id = trim((string)(is_array($m) ? ($m['id'] ?? '') : $m));
            if ($id !== '') $ids[] = $id;
        }
        $ids = array_values(array_unique($ids));
        sort($ids);

        if (empty($ids)) {
            return ['success' => false, 'data' => [], 'error' => '没有读取到可用模型，请手动填写模型名'];
        }

        return ['success' => true, 'data' => $ids, 'error' => ''];
    }
}
