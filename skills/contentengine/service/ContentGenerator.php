<?php
namespace skills\contentengine\service;

use think\facade\Db;
use skills\contentengine\model\CompanyProfile;
use skills\contentengine\model\Solution;

/**
 * AI内容生成引擎
 */
class ContentGenerator
{
    /** 调用DeepSeek API，根据格式调整token量 */
    private function callAi(string $systemPrompt, string $userPrompt, int $maxTokens = 2000): ?string
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
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
                'temperature' => 0.7,
                'max_tokens' => $maxTokens,
            ], JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $cfg['api_key']],
            CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 120, CURLOPT_CONNECTTIMEOUT => 5,
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
     * 根据选题ID生成四格式内容
     */
    public function generate(int $topicId, int $storeId, string $agentId = ''): array
    {
        $topic = Db::name('crm_content_topic')
            ->where('id', $topicId)->where('store_id', $storeId)->find();
        if (!$topic) {
            return ['success' => false, 'data' => null, 'error' => '选题不存在'];
        }

        // 加载配置
        $profile = (new CompanyProfile())->getByStoreId($storeId) ?: [];
        $keywords = explode(',', $topic['keywords'] ?? '');
        $keywords = array_map('trim', $keywords);

        // 关联解决方案
        $solution = [];
        if (!empty($topic['source_ref_id'])) {
            $solution = (new Solution())->where('id', $topic['source_ref_id'])->find();
        }

        $generated = [];
        $formats = [
            'long'  => ['公众号长文', 2500],
            'video' => ['视频脚本', 1000],
            'social'=> ['朋友圈短文', 400],
            'seo'   => ['SEO摘要', 300],
        ];

        // 构建共享变量值
        $productsList = \think\facade\Db::name('crm_product')->where('store_id',$storeId)->where('is_delete',0)->where('status',1)
            ->field('product_name,category')->limit(10)->select()->toArray();
        $baseVars = [
            'company_intro' => $profile['company_intro'] ?? $profile['core_strengths'] ?? '',
            'core_strengths' => $profile['core_strengths'] ?? '',
            'keywords' => implode('、', array_slice($keywords, 0, 10)),
            'solutions' => $solution ? ($solution['title'] . '：' . ($solution['advantages'] ?? '')) : '',
            'products' => implode('、', array_column($productsList, 'product_name')),
            'topic_title' => $topic['title'],
            'topic_angle' => $topic['angle'] ?? '',
        ];

        // 解析格式→智能体映射。agentId 可以是 JSON 如 {"long":"my-agent","video":"video-writer"}
        $formatAgentIds = [];
        $decoded = json_decode($agentId, true);
        if (is_array($decoded)) {
            $formatAgentIds = $decoded;
        }
        // 默认映射
        $fallbackAgents = ['long'=>'content-writer','video'=>'video-writer','social'=>'social-writer','seo'=>'seo-writer'];

        foreach ($formats as $format => [$label, $maxLen]) {
            // 每种格式用对应的智能体
            $agentKey = $formatAgentIds[$format] ?? $fallbackAgents[$format];
            $agent = \app\common\service\crm\PresetLoader::getAgent($agentKey);
            // 也可能是数字ID（自定义智能体）
            if (!$agent && is_numeric($agentKey)) {
                $agent = Db::name('crm_content_agent')->where('id',(int)$agentKey)->where('store_id',$storeId)->find();
            }
            // 回退到默认
            if (!$agent) {
                $agent = \app\common\service\crm\PresetLoader::getAgent($fallbackAgents[$format]);
            }
            $agentConfig = \app\common\service\crm\PresetLoader::resolveAgentConfig($agent ?: [], $storeId);

            // 格式变量
            $vars = array_merge($baseVars, ['forbidden_words' => $agentConfig['forbidden_words']]);
            $systemPrompt = \app\common\service\crm\PresetLoader::renderVars($agentConfig['system_prompt'], $vars, $storeId);
            if (!empty($agentConfig['instructions'])) {
                $systemPrompt .= "\n" . $agentConfig['instructions'];
            }

            $prompt = $this->buildPrompt($format, $topic, $profile, $keywords, $solution, $maxLen);
            $tokenMap = ['long' => 4096, 'video' => 2048, 'social' => 1024, 'seo' => 512];
            $maxTokens = $tokenMap[$format] ?? 2000;
            $content = $this->callAi($systemPrompt, $prompt, $maxTokens);
            if ($content === null) $content = '';
            // 剔除4字节emoji（MySQL utf8连接不支持）
            $content = preg_replace('/[\xF0-\xF7][\x80-\xBF][\x80-\xBF][\x80-\xBF]/', '', $content);

            // 写入产出表
            Db::name('crm_content_output')->insert([
                'store_id' => $storeId,
                'topic_id' => $topicId,
                'format' => $format,
                'title' => $topic['title'],
                'content' => $content,
                'keywords_used' => $topic['keywords'] ?? '',
                'solution_id' => $topic['source_ref_id'] ?? 0,
                'word_count' => mb_strlen(strip_tags($content)),
                'status' => 1, // 草稿
                'create_time' => time(),
                'update_time' => time(),
            ]);

            $generated[$format] = [
                'label' => $label,
                'content' => $content,
                'length' => mb_strlen($content),
            ];
        }

        // 更新选题状态
        Db::name('crm_content_topic')->where('id', $topicId)->update([
            'status' => 3, // 已生成
            'update_time' => time(),
        ]);

        return ['success' => true, 'data' => ['formats' => $generated], 'error' => ''];
    }

    /**
     * 构建生成 prompt（从模板文件加载）
     */
    private function buildPrompt(string $format, array $topic, array $profile, array $keywords, $solution, int $maxLen): string
    {
        $templateFile = root_path('skills/contentengine/templates/') . $this->getTemplateName($format);
        if (file_exists($templateFile)) {
            $template = file_get_contents($templateFile);
        } else {
            $template = $this->getFallbackTemplate($format);
        }

        $base = "选题：{$topic['title']}\n"
            . "切入角度：{$topic['angle']}\n"
            . "必须包含关键词：" . implode('、', array_slice($keywords, 0, 5)) . "\n";

        if ($solution && !empty($solution['title'])) {
            $base .= "引用方案：{$solution['title']}\n"
                . "方案优势：" . ($solution['advantages'] ?? '') . "\n"
                . "客户案例：" . ($solution['customer_cases'] ?? '') . "\n";
        }

        return $base . "\n字数控制在{$maxLen}字以内。\n" . $template;
    }

    private function getTemplateName(string $format): string
    {
        $map = ['long' => 'article-long.md', 'video' => 'video-script.md', 'social' => 'social-short.md', 'seo' => 'seo-meta.md'];
        return $map[$format] ?? 'article-long.md';
    }

    private function getFallbackTemplate(string $format): string
    {
        switch ($format) {
            case 'long': return "写一篇公众号长文：标题25字以内，正文3-5小节，每段200-400字，配图标记用[配图：描述]。直接返回正文。";
            case 'video': return "写3分钟视频脚本：[画面]+旁白格式，开场抓注意力，主体3要点，结尾行动号召。直接返回。";
            case 'social': return "写朋友圈短文：首句抓眼球，2-3句讲价值，适当emoji。400字以内，直接返回。";
            case 'seo': return "写SEO摘要：Title(DESC)+Description+Keywords格式。直接返回。";
        }
        return '';
    }

    /**
     * 获取已生成的内容列表
     */
    public function getOutputs(int $topicId, int $storeId): array
    {
        $rows = Db::name('crm_content_output')
            ->where('topic_id', $topicId)->where('store_id', $storeId)
            ->order('id', 'asc')->select()->toArray();
        return ['success' => true, 'data' => ['list' => $rows], 'error' => ''];
    }

    /**
     * 保存单条内容
     */
    public function saveOutput(int $outputId, string $content, int $storeId): array
    {
        Db::name('crm_content_output')
            ->where('id', $outputId)->where('store_id', $storeId)
            ->update([
                'content' => $content,
                'status' => 2,
                'update_time' => time(),
            ]);
        return ['success' => true, 'data' => [], 'error' => ''];
    }
}
