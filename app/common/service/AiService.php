<?php
namespace app\common\service;

use app\platform\ai\AiConfig;

class AiService
{
    /**
     * 读取全局 AI 配置（设置 → AI 配置）
     *
     * 优先 llm_setting，未配置时回退历史 crm_setting；按当前请求租户隔离。
     */
    private static function getConfig(): array
    {
        return AiConfig::get();
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
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
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
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
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
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
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

    /** 从企查查/天眼查文本中提取企业信息 */
    public static function extractCompanyInfo(string $text): array
    {
        $cfg = self::getConfig();
        if (empty($cfg['api_key'])) return ['success' => false, 'data' => null, 'error' => '未配置API Key'];

        $prompt = "从以下企业信息文本中提取关键字段。只返回JSON，字段值为空字符串表示未找到。\n"
            . "字段：customer_name、legal_representative、registered_capital、paid_in_capital、"
            . "established_date(YYYY-MM-DD)、tax_number(18位)、registration_status、"
            . "business_registration_no、organization_code、taxpayer_qualification、"
            . "insured_count(纯数字)、telephone、email、website、registered_address、"
            . "business_scope、introduction、national_industry、registration_authority、"
            . "approval_date、business_term\n\n文本：\n" . mb_substr($text, 0, 4000);

        try {
            $ch = curl_init($cfg['api_url']);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode([
                    'model' => $cfg['model'],
                    'messages' => [
                        ['role' => 'system', 'content' => '你是企业信息提取助手，只返回JSON，不返回其他内容。'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => 0.1,
                    'max_tokens' => 2048,
                    'response_format' => ['type' => 'json_object'],
                ], JSON_UNESCAPED_UNICODE),
                CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $cfg['api_key']],
                CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 60, CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false,
            ]);
            $resp = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);
            curl_close($ch);
            if ($err) return ['success' => false, 'data' => null, 'error' => '网络错误: ' . $err];
            if ($code !== 200) return ['success' => false, 'data' => null, 'error' => "HTTP {$code}"];

            $body = json_decode($resp, true);
            $content = trim($body['choices'][0]['message']['content'] ?? '');
            $data = json_decode($content, true);
            // 容错：处理 markdown 代码块包裹或前缀文字
            if (!$data) {
                if (preg_match('/```(?:json)?\s*\n?(.*?)\n?```/s', $content, $m)) {
                    $data = json_decode(trim($m[1]), true);
                } elseif (preg_match('/\{.*\}/s', $content, $m)) {
                    $data = json_decode($m[0], true);
                }
            }
            // 最后的兜底：尝试修复尾部截断的 JSON
            if (!$data && substr($content, -1) !== '}') {
                $fixed = $content;
                // 补全缺失的引号和括号
                $fixed = preg_replace('/,\s*$/', '', $fixed); // 去掉尾部逗号
                if (substr_count($fixed, '{') > substr_count($fixed, '}')) {
                    $fixed .= '"}'; // 简单补全
                }
                $data = json_decode($fixed, true);
            }
            if (!$data) return ['success' => false, 'data' => null, 'error' => 'AI返回格式异常: ' . mb_substr($content, 0, 200)];
            return ['success' => true, 'data' => $data, 'error' => ''];
        } catch (\Exception $e) {
            return ['success' => false, 'data' => null, 'error' => $e->getMessage()];
        }
    }

    /** 生成需求规则：自然语言描述 → 结构化规则 JSON */
    public static function generateDemandRule(string $description, string $fieldsHint): array
    {
        $cfg = self::getConfig();
        if (empty($cfg['api_key'])) {
            return ['success' => false, 'data' => null, 'error' => 'AI服务未配置API Key'];
        }

        $systemPrompt = "你是 CRM 系统的需求规则配置助手。根据用户的自然语言描述，生成需求识别规则。\n\n"
            . "可用原生字段：\n{$fieldsHint}\n\n"
            . "⚠️ 重要规则：\n"
            . "1. 优先使用已有的原生字段，不要为已有字段创建扩展字段。\n"
            . "   例如：用户说「参保人数」，原生字段已有 insured_count，直接用 insured_count，不要新建 extra.insured_count。\n"
            . "2. 只有当用户描述的需求涉及的数据在原生字段中确实不存在时，才在 new_fields 中建议创建。\n"
            . "3. 自定义字段必须用 extra.英文key 引用，不要用中文。例如 extra.supervisor 而不是 extra.监事。\n"
            . "操作符：not_empty(非空), empty(为空), eq(等于), contains(包含), gt(大于), split_count_gt(分隔后条目数大于N), days_since_gt(距今超过N天)\n"
            . "逻辑：all=所有条件必须满足, any=任一条件满足即可\n\n"
            . "输出 JSON：{\"rule_key\":\"英文标识\",\"rule_name\":\"规则中文名\",\"conditions\":[{\"field\":\"字段\",\"op\":\"操作符\",\"value\":\"比较值(可选)\"}],\"logic\":\"all|any\",\"priority\":1-3,\"new_fields\":[{\"field_key\":\"英文key\",\"field_label\":\"中文名\",\"field_type\":\"text|date|select\"}]}\n"
            . "new_fields 只放真正不存在的字段，已有原生字段或已有扩展字段都不要放进去。";

        try {
            $ch = curl_init($cfg['api_url']);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode([
                    'model'       => $cfg['model'],
                    'messages'    => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user',   'content' => $description],
                    ],
                    'temperature'      => 0.3,
                    'max_tokens'       => 1024,
                    'response_format'  => ['type' => 'json_object'],
                ], JSON_UNESCAPED_UNICODE),
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
            $err  = curl_error($ch);
            curl_close($ch);

            if ($err) return ['success' => false, 'data' => null, 'error' => '网络错误: ' . $err];
            if ($code !== 200) return ['success' => false, 'data' => null, 'error' => "HTTP {$code}"];

            $body = json_decode($resp, true);
            $text = trim($body['choices'][0]['message']['content'] ?? '');
            if (empty($text)) return ['success' => false, 'data' => null, 'error' => '空响应'];

            $rule = json_decode($text, true);
            if (!$rule || empty($rule['conditions'])) {
                return ['success' => false, 'data' => null, 'error' => 'AI返回格式异常，请重试'];
            }
            return ['success' => true, 'data' => $rule, 'error' => ''];
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
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
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
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
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
