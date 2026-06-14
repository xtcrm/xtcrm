<?php
namespace skills\contentengine\service;

/**
 * 预设模板加载器
 * 从 skills/content-engine/templates/ 读取系统预设，不依赖数据库
 */
class PresetLoader
{
    private static function baseDir(): string
    {
        return root_path('skills/contentengine/templates');
    }

    /** 获取所有预设写作风格 */
    public static function getStyles(): array
    {
        $dir = self::baseDir() . '/styles';
        if (!is_dir($dir)) return [];
        $files = glob($dir . '/*.json');
        $styles = [];
        foreach ($files as $f) {
            $data = json_decode(file_get_contents($f), true);
            if ($data) {
                $data['preset_key'] = basename($f, '.json');
                $styles[] = $data;
            }
        }
        return $styles;
    }

    /** 获取单个预设风格 */
    public static function getStyle(string $presetKey): ?array
    {
        $file = self::baseDir() . '/styles/' . $presetKey . '.json';
        if (!file_exists($file)) return null;
        $data = json_decode(file_get_contents($file), true);
        if ($data) $data['preset_key'] = $presetKey;
        return $data;
    }

    /** 获取所有预设智能体 */
    public static function getAgents(): array
    {
        $dir = self::baseDir() . '/agents';
        if (!is_dir($dir)) return [];
        $files = glob($dir . '/*.json');
        $agents = [];
        foreach ($files as $f) {
            $data = json_decode(file_get_contents($f), true);
            if ($data) {
                $data['preset_key'] = basename($f, '.json');
                $agents[] = $data;
            }
        }
        return $agents;
    }

    /** 获取单个预设智能体 */
    public static function getAgent(string $presetKey): ?array
    {
        $file = self::baseDir() . '/agents/' . $presetKey . '.json';
        if (!file_exists($file)) return null;
        $data = json_decode(file_get_contents($file), true);
        if ($data) $data['preset_key'] = $presetKey;
        return $data;
    }

    /** 解析智能体的配置：风格system_prompt + 变量替换 */
    public static function resolveAgentConfig(?array $agent, int $storeId): array
    {
        if (!$agent) return ['system_prompt' => '', 'allowed_vars' => []];

        // 确定风格：先查 preset_style，再查 style_id
        $stylePrompt = '';
        $forbiddenWords = '';
        if (!empty($agent['preset_style'])) {
            $preset = self::getStyle($agent['preset_style']);
            if ($preset) {
                $stylePrompt = $preset['system_prompt'] ?? '';
                $forbiddenWords = $preset['forbidden_words'] ?? '';
            }
        }
        if (empty($stylePrompt) && !empty($agent['style_id'])) {
            $style = \think\facade\Db::name('crm_content_style')
                ->where('id', $agent['style_id'])->where('store_id', $storeId)->find();
            if ($style) {
                $stylePrompt = $style['system_prompt'] ?? '';
                $forbiddenWords = $style['forbidden_words'] ?? '';
            }
        }
        if (empty($stylePrompt)) {
            $stylePrompt = '你是企业内容编辑。专业、准确、有数据支撑。直接返回正文。';
            $forbiddenWords = '在当今,随着...的发展,众所周知,赋能,抓手,底层逻辑';
        }

        $allowedVars = is_array($agent['allowed_vars'] ?? null)
            ? $agent['allowed_vars']
            : (json_decode($agent['allowed_vars'] ?? '[]', true) ?: []);

        $instructions = $agent['instructions'] ?? '';

        return [
            'system_prompt' => $stylePrompt,
            'forbidden_words' => $forbiddenWords,
            'allowed_vars' => $allowedVars,
            'instructions' => $instructions,
        ];
    }

    /** 渲染变量值：{变量key} → 实际数据 */
    public static function renderVars(string $text, array $vars, int $storeId): string
    {
        foreach ($vars as $key => $value) {
            $text = str_replace('{' . $key . '}', (string)$value, $text);
        }
        // 处理 {forbidden_words}
        if (isset($vars['forbidden_words'])) {
            $text = str_replace('{forbidden_words}', $vars['forbidden_words'], $text);
        }
        $text = str_replace('{company_name}', '', $text);
        return $text;
    }
}
