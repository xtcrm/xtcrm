<?php
namespace skills\contentengine\service;

use think\facade\Db;
use skills\contentengine\model\CompanyProfile;
use skills\contentengine\model\ContentKeyword;
use skills\contentengine\model\Solution;

/**
 * 选题扫描引擎
 * 扫描CRM数据，通过DeepSeek推荐内容选题
 */
class TopicScanner
{
    /** 调用DeepSeek API（不经过AiService的500token限制） */
    private function callAi(string $prompt): ?string
    {
        $rows = Db::name('config')
            ->where('config_type', 'crm_setting')
            ->whereIn('config_name', ['ai_api_url', 'ai_api_key', 'ai_model'])
            ->select()->toArray();
        $cfg = ['api_url' => 'https://api.deepseek.com/chat/completions', 'api_key' => '', 'model' => 'deepseek-chat'];
        foreach ($rows as $r) {
            if ($r['config_name'] === 'ai_api_url' && $r['config_value']) {
                $url = $r['config_value'];
                if (!str_contains($url, '/chat/completions')) $url = rtrim($url, '/') . '/v1/chat/completions';
                $cfg['api_url'] = $url;
            }
            if ($r['config_name'] === 'ai_api_key') $cfg['api_key'] = $r['config_value'];
            if ($r['config_name'] === 'ai_model' && $r['config_value']) $cfg['model'] = $r['config_value'];
        }
        if (empty($cfg['api_key'])) return null;

        $ch = curl_init($cfg['api_url']);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'model' => $cfg['model'],
                'messages' => [
                    ['role' => 'system', 'content' => '你是内容选题编辑。只返回JSON数组，不要任何解释。'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.4,
                'max_tokens' => 2000,
            ], JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $cfg['api_key']],
            CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 60, CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false,
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code !== 200) return null;
        $body = json_decode($resp, true);
        return trim($body['choices'][0]['message']['content'] ?? '') ?: null;
    }
    /**
     * 扫描并生成选题（供定时任务和手动触发）
     */
    public function scan(int $storeId, string $agentId = ''): array
    {
        // 1. 解析智能体配置（默认 topic-scanner）
        $agent = null;
        if (is_numeric($agentId) && !empty($agentId)) {
            $agent = Db::name('crm_content_agent')->where('id',(int)$agentId)->where('store_id',$storeId)->find();
        } elseif (!empty($agentId)) {
            $agent = PresetLoader::getAgent($agentId);
        }
        if (empty($agent)) {
            $agent = PresetLoader::getAgent('topic-scanner');
        }
        $agentConfig = PresetLoader::resolveAgentConfig($agent ?: [], $storeId);

        // 2. 加载变量数据
        $profile = (new CompanyProfile())->getByStoreId($storeId) ?: [];
        $keywords = Db::name('crm_content_keyword')
            ->where('store_id', $storeId)->where('status', 1)
            ->where('type', 'in', [1, 2])->column('keyword');
        $solutions = Db::name('crm_solution')
            ->where('store_id', $storeId)->where('status', 1)
            ->field('title')->select()->toArray();

        $vars = [
            'company_intro' => $profile['company_intro'] ?? '',
            'core_strengths' => $profile['core_strengths'] ?? '',
            'keywords' => implode('、', array_slice($keywords, 0, 20)),
            'solutions' => implode('、', array_column($solutions, 'title')),
            'products' => implode('、', array_column(
                Db::name('crm_product')->where('store_id',$storeId)->where('is_delete',0)->where('status',1)
                    ->field('product_name')->limit(10)->select()->toArray(), 'product_name')),
            'forbidden_words' => $agentConfig['forbidden_words'],
        ];

        // 3. 构建 prompt（agent 指令 + 数据）
        $prompt = PresetLoader::renderVars($agentConfig['system_prompt'], $vars, $storeId);
        $prompt .= "\n\n企业数据：\n"
            . "简介：" . $vars['company_intro'] . "\n"
            . "关键词：" . $vars['keywords'] . "\n"
            . "方案：" . $vars['solutions'] . "\n"
            . "产品：" . $vars['products'] . "\n\n";
        if (!empty($agentConfig['instructions'])) {
            $prompt .= $agentConfig['instructions'] . "\n";
        }
        $prompt .= "返回JSON数组：[{\"title\":\"选题标题\",\"content_type\":\"article\",\"source_type\":\"产品/技术/案例/行业\",\"angle\":\"切入角度\",\"keywords\":\"核心词,长尾词\",\"priority\":3}]\n"
            . "直接返回JSON，不要其他文字。";

        // 4. 调用DeepSeek（直接调，需要更大token限额）
        $content = $this->callAi($prompt);
        if ($content === null) {
            return ['success' => false, 'data' => null, 'error' => 'AI调用失败'];
        }

        // 5. 解析AI返回的选题
        // 处理 ```json ... ``` 代码块包裹
        if (preg_match('/```(?:json)?\s*(\[.*?\])\s*```/s', $content, $m)) {
            $jsonStr = $m[1];
        } elseif (preg_match('/\[.*?\]/s', $content, $m)) {
            $jsonStr = $m[0];
        } else {
            return ['success' => false, 'data' => null, 'error' => 'AI返回格式异常，未能提取选题'];
        }
        $topics = json_decode($jsonStr, true);
        if (!is_array($topics)) {
            return ['success' => false, 'data' => null, 'error' => '选题JSON解析失败：' . json_last_error_msg()];
        }

        // 6. 写入数据库（去重：相同标题不重复插入）
        // 过滤emoji
        $stripEmoji = function($s) { return preg_replace('/[\xF0-\xF7][\x80-\xBF][\x80-\xBF][\x80-\xBF]/', '', $s); };

        $added = 0;
        foreach ($topics as $t) {
            $exists = Db::name('crm_content_topic')
                ->where('store_id', $storeId)
                ->where('title', $t['title'] ?? '')
                ->find();
            if ($exists) continue;

            Db::name('crm_content_topic')->insert([
                'store_id' => $storeId,
                'title' => $stripEmoji($t['title'] ?? ''),
                'content_type' => $t['content_type'] ?? 'article',
                'source_type' => $stripEmoji($t['source_type'] ?? ''),
                'keywords' => $stripEmoji($t['keywords'] ?? ''),
                'angle' => $stripEmoji($t['angle'] ?? ''),
                'priority' => $t['priority'] ?? 3,
                'status' => 1,
                'create_time' => time(),
                'update_time' => time(),
            ]);
            $added++;
        }

        return ['success' => true, 'data' => ['added' => $added, 'total' => count($topics)], 'error' => ''];
    }

}
