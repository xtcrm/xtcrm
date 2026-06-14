<?php
namespace app\common\service;

use think\facade\Db;

class AiService
{
    private static function getConfig(): array
    {
        $rows = Db::name('config')
            ->where('config_type', 'crm_setting')
            ->select()->toArray();
        $cfg = [
            'api_url'     => 'https://api.deepseek.com/chat/completions',
            'api_key'     => '',
            'model'       => 'deepseek-chat',
            'temperature' => 0.7,
            'max_tokens'  => 1024,
        ];
        foreach ($rows as $r) {
            switch ($r['config_name']) {
                case 'ai_api_url':
                    $url = $r['config_value'] ?: $cfg['api_url'];
                    // 如果只配了基础域名，自动补全路径
                    if (!str_contains($url, '/chat/completions')) {
                        $url = rtrim($url, '/') . '/chat/completions';
                    }
                    $cfg['api_url'] = $url;
                    break;
                case 'ai_api_key':     $cfg['api_key']     = $r['config_value']; break;
                case 'ai_model':       $cfg['model']       = $r['config_value'] ?: $cfg['model']; break;
                case 'ai_temperature': $cfg['temperature'] = floatval($r['config_value'] ?? 0.7); break;
                case 'ai_max_tokens':  $cfg['max_tokens']  = intval($r['config_value'] ?? 1024); break;
            }
        }
        return $cfg;
    }

    /** 分析客户，返回 {score, tags, suggestion} */
    public static function analyzeCustomer(array $customerData): array
    {
        $cfg = self::getConfig();
        if (empty($cfg['api_key'])) {
            return ['success' => false, 'data' => null, 'error' => 'AI服务未配置API Key，请先在CRM设置中配置'];
        }

        $prompt = self::buildPrompt($customerData);

        try {
            $ch = curl_init($cfg['api_url']);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode([
                    'model'       => $cfg['model'],
                    'messages'    => [
                        ['role' => 'system', 'content' => '你是一个专业的CRM客户分析助手。请根据客户信息返回JSON格式的分析结果。评分标准：行业匹配度(0-30)+跟进活跃度(0-30)+等级价值(0-20)+来源质量(0-20)，总和0-100。60以上为重点客户，40-60为普通客户，40以下为低价值。'],
                        ['role' => 'user',   'content' => $prompt],
                    ],
                    'temperature' => 0.3,
                    'max_tokens'  => $cfg['max_tokens'],
                    'response_format' => ['type' => 'json_object'],
                ], JSON_UNESCAPED_UNICODE),
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $cfg['api_key'],
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 120,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ]);
            $resp = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err  = curl_error($ch);
            curl_close($ch);

            if ($err) {
                return ['success' => false, 'data' => null, 'error' => '网络错误: ' . $err];
            }
            if ($code !== 200) {
                $errBody = json_decode($resp, true);
                $errMsg  = $errBody['error']['message'] ?? "HTTP {$code}";
                return ['success' => false, 'data' => null, 'error' => $errMsg];
            }

            $body    = json_decode($resp, true);
            $content = $body['choices'][0]['message']['content'] ?? '';
            $result  = json_decode($content, true);

            if (!is_array($result) || !isset($result['score'])) {
                return ['success' => false, 'data' => null, 'error' => 'AI返回格式异常'];
            }

            return ['success' => true, 'data' => $result, 'error' => ''];
        } catch (\Exception $e) {
            return ['success' => false, 'data' => null, 'error' => $e->getMessage()];
        }
    }

    /** 快速文本提问（用于跟进建议等） */
    public static function quickAsk(string $prompt): array
    {
        $cfg = self::getConfig();
        if (empty($cfg['api_key'])) {
            return ['success' => false, 'data' => null, 'error' => '未配置API Key'];
        }

        try {
            $ch = curl_init($cfg['api_url']);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode([
                    'model'       => $cfg['model'],
                    'messages'    => [
                        ['role' => 'system', 'content' => '你是专业的销售跟进顾问。请简洁回答，50字以内。'],
                        ['role' => 'user',   'content' => $prompt],
                    ],
                    'temperature' => 0.5,
                    'max_tokens'  => 200,
                ], JSON_UNESCAPED_UNICODE),
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $cfg['api_key'],
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 60,
                CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ]);
            $resp = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err  = curl_error($ch);
            curl_close($ch);

            if ($err) return ['success' => false, 'data' => null, 'error' => '网络错误: ' . $err];
            if ($code !== 200) return ['success' => false, 'data' => null, 'error' => "HTTP {$code}"];

            $body = json_decode($resp, true);
            $text = trim($body['choices'][0]['message']['content'] ?? '');
            return $text ? ['success' => true, 'data' => $text, 'error' => '']
                         : ['success' => false, 'data' => null, 'error' => '空响应'];
        } catch (\Exception $e) {
            return ['success' => false, 'data' => null, 'error' => $e->getMessage()];
        }
    }

    /** AI对话 */
    public static function chat(string $message, string $context): array
    {
        $cfg = self::getConfig();
        if (empty($cfg['api_key'])) {
            return ['success' => false, 'data' => null, 'error' => '未配置API Key'];
        }

        try {
            $ch = curl_init($cfg['api_url']);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode([
                    'model'    => $cfg['model'],
                    'messages' => [
                        ['role' => 'system', 'content' => "你是CRM销售助手。基于以下实时数据回答问题，简洁实用，必要时给出具体行动建议。\n\n当前数据：\n{$context}"],
                        ['role' => 'user',   'content' => $message],
                    ],
                    'temperature' => 0.5,
                    'max_tokens'  => 500,
                ], JSON_UNESCAPED_UNICODE),
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $cfg['api_key'],
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ]);
            $resp = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err  = curl_error($ch);
            curl_close($ch);

            if ($err) return ['success' => false, 'data' => null, 'error' => '网络错误: ' . $err];
            if ($code !== 200) return ['success' => false, 'data' => null, 'error' => "HTTP {$code}"];

            $body = json_decode($resp, true);
            $text = trim($body['choices'][0]['message']['content'] ?? '');
            return $text ? ['success' => true, 'data' => $text, 'error' => '']
                         : ['success' => false, 'data' => null, 'error' => '空响应'];
        } catch (\Exception $e) {
            return ['success' => false, 'data' => null, 'error' => $e->getMessage()];
        }
    }

    /** 智能搜索：自然语言转筛选条件 */
    public static function smartSearch(string $query): array
    {
        $cfg = self::getConfig();
        if (empty($cfg['api_key'])) {
            return ['success' => false, 'data' => null, 'error' => '未配置API Key'];
        }
        $prompt = "用户搜索CRM客户：\"{$query}\"\n\n请将自然语言转为筛选条件，返回JSON：{\"customer_name\": \"关键词\",\"industry\": \"行业名\",\"level\": \"A级-战略/B级-重要/C级-普通/D级-潜在\",\"province\": \"省份\",\"source\": \"来源\",\"group\": \"分组\",\"funnel_stage\": \"初步接触/需求确认/报价/谈判/成交\",\"follow_days\": 超过N天未跟进}\n只返回有把握的字段，不要猜测。";

        try {
            $ch = curl_init($cfg['api_url']);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode([
                    'model'       => $cfg['model'],
                    'messages'    => [
                        ['role' => 'system', 'content' => '你是CRM搜索助手，将自然语言转为筛选条件。只返回JSON。'],
                        ['role' => 'user',   'content' => $prompt],
                    ],
                    'temperature'       => 0.1,
                    'max_tokens'        => 300,
                ], JSON_UNESCAPED_UNICODE),
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $cfg['api_key'],
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ]);
            $resp = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err  = curl_error($ch);
            curl_close($ch);

            if ($err) return ['success' => false, 'data' => null, 'error' => '网络错误: ' . $err];
            if ($code !== 200) return ['success' => false, 'data' => null, 'error' => "HTTP {$code}"];

            $body    = json_decode($resp, true);
            $content = $body['choices'][0]['message']['content'] ?? '';
            $result  = json_decode($content, true);
            return ['success' => true, 'data' => is_array($result) ? $result : [], 'error' => ''];
        } catch (\Exception $e) {
            return ['success' => false, 'data' => null, 'error' => $e->getMessage()];
        }
    }

    /** 生成客户画像 */
    public static function generatePortrait(array $customerData): array
    {
        $cfg = self::getConfig();
        if (empty($cfg['api_key'])) {
            return ['success' => false, 'data' => null, 'error' => '未配置API Key'];
        }

        $safeName = '客户';
        if (!empty($customerData['customer_name'])) {
            $safeName = mb_substr($customerData['customer_name'], 0, 1) . '客户';
        }
        $prompt = "客户：{$safeName}\n"
            . '行业：' . ($customerData['industry'] ?? '未知') . "\n"
            . '等级：' . ($customerData['level_name'] ?? '未知') . "\n"
            . '来源：' . ($customerData['source'] ?? '未知') . "\n"
            . '分组：' . ($customerData['customer_group'] ?? '未知') . "\n";
        if (!empty($customerData['recent_followups'])) {
            $prompt .= '近期跟进：' . "\n" . $customerData['recent_followups'];
        }
        $prompt .= "\n请生成该客户画像，只返回JSON：{\"summary\":\"5字概括客户特征\",\"needs\":\"核心需求是什么\",\"pain_points\":\"痛点是什么\",\"approach\":\"下一步该怎么做\"}。不要返回其他字段。";

        try {
            $ch = curl_init($cfg['api_url']);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode([
                    'model'       => $cfg['model'],
                    'messages'    => [
                        ['role' => 'system', 'content' => '你是专业的B2B客户洞察顾问，擅长提炼客户画像和跟进策略。返回JSON。'],
                        ['role' => 'user',   'content' => $prompt],
                    ],
                    'temperature'       => 0.3,
                    'max_tokens'        => 600,
                ], JSON_UNESCAPED_UNICODE),
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $cfg['api_key'],
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 120,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ]);
            $resp = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err  = curl_error($ch);
            curl_close($ch);

            if ($err) return ['success' => false, 'data' => null, 'error' => '网络错误: ' . $err];
            if ($code !== 200) return ['success' => false, 'data' => null, 'error' => "HTTP {$code}"];

            $body    = json_decode($resp, true);
            $content = $body['choices'][0]['message']['content'] ?? '';
            $result  = json_decode($content, true);

            if (!is_array($result)) {
                return ['success' => true, 'data' => ['summary' => $content, 'needs' => '', 'pain_points' => '', 'approach' => '']];
            }
            return ['success' => true, 'data' => $result, 'error' => ''];
        } catch (\Exception $e) {
            return ['success' => false, 'data' => null, 'error' => $e->getMessage()];
        }
    }

    /** Function calling — AI 可调用工具并返回结构化结果 */
    public static function functionCall(array $messages, array $tools, int $maxRounds = 5): array
    {
        $cfg = self::getConfig();
        if (empty($cfg['api_key'])) {
            return ['success' => false, 'data' => null, 'error' => '未配置API Key'];
        }

        $round = 0;
        $toolResults = [];

        while ($round < $maxRounds) {
            $round++;
            try {
                $payload = [
                    'model' => $cfg['model'],
                    'messages' => $messages,
                    'temperature' => 0.3,
                    'max_tokens' => 800,
                ];
                if (!empty($tools)) {
                    $payload['tools'] = array_map(function ($t) {
                        return ['type' => 'function', 'function' => $t];
                    }, $tools);
                }

                $ch = curl_init($cfg['api_url']);
                curl_setopt_array($ch, [
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
                    CURLOPT_HTTPHEADER => [
                        'Content-Type: application/json',
                        'Authorization: Bearer ' . $cfg['api_key'],
                    ],
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 60,
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
                $msg = $body['choices'][0]['message'] ?? [];

                // 有 tool_call → 需要执行工具
                if (!empty($msg['tool_calls'])) {
                    $messages[] = ['role' => 'assistant', 'tool_calls' => $msg['tool_calls']];
                    foreach ($msg['tool_calls'] as $tc) {
                        $funcName = $tc['function']['name'];
                        $funcArgs = json_decode($tc['function']['arguments'], true) ?: [];
                        $toolResults[] = ['name' => $funcName, 'args' => $funcArgs];
                        // 工具结果由外部填充后回传，此处返回待执行的工具列表
                        return [
                            'success' => true,
                            'data' => [
                                'type' => 'tool_call',
                                'messages' => $messages,
                                'tool_calls' => $msg['tool_calls'],
                                'tool_results' => $toolResults,
                            ],
                            'error' => '',
                        ];
                    }
                }

                // 纯文本响应
                $text = trim($msg['content'] ?? '');
                return [
                    'success' => true,
                    'data' => [
                        'type' => 'text',
                        'content' => $text,
                        'tool_results' => $toolResults,
                    ],
                    'error' => '',
                ];

            } catch (\Exception $e) {
                return ['success' => false, 'data' => null, 'error' => $e->getMessage()];
            }
        }

        return ['success' => false, 'data' => null, 'error' => '超过最大对话轮次'];
    }

    private static function buildPrompt(array $data): string
    {
        // 脱敏：客户名称只保留姓氏+行业代称
        $safeName = '客户';
        if (!empty($data['customer_name'])) {
            $name = $data['customer_name'];
            $first = mb_substr($name, 0, 1);
            $safeName = $first . '客户';
        }
        $parts = [
            '客户：' . $safeName,
            '行业：' . ($data['industry'] ?? '未知'),
            '等级：' . ($data['level_name'] ?? '未知'),
            '来源：' . ($data['source'] ?? '未知'),
            '分组：' . ($data['customer_group'] ?? '未知'),
        ];
        if (!empty($data['recent_followups'])) {
            $parts[] = '近期跟进记录：' . "\n" . $data['recent_followups'];
        }
        $parts[] = "\n请按以下维度打分：行业匹配度(0-30)+跟进活跃度(0-30)+等级价值(0-20)+来源质量(0-20)，总和为score。返回JSON：{\"score\": 整数,\"tags\": [\"2-3个标签\"],\"suggestion\": \"一句话跟进建议\"}";
        return implode("\n", $parts);
    }
}
