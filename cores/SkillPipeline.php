<?php

namespace cores;

/**
 * 技能 AI 管道
 *
 * 桥接 DeepSeek function call 与技能包的 Service 层。
 * 负责：检测用户意图 → 注入 SKILL.md AI 指令 → 生成工具 schema → 执行工具调用
 */
class SkillPipeline
{
    /**
     * 获取所有已安装技能包的 AI 指令摘要（注入 Agent 的 system prompt）
     * 返回格式：技能名 + 简述 + 触发关键词
     */
    public static function getSkillsContext(): string
    {
        $skills = SkillLoader::getSkills();
        if (empty($skills)) return '';

        $lines = ["\n## 可用技能包"];
        foreach ($skills as $name => $skill) {
            $desc = $skill['description'] ?? $skill['name'];
            $lines[] = "- **{$skill['name']}**：{$desc}";
        }
        $lines[] = "当用户操作涉及以上技能时，应使用对应技能包的工具。";

        return implode("\n", $lines);
    }

    /**
     * 从用户消息中检测匹配的技能
     */
    public static function detectSkill(string $message): ?string
    {
        $skills = SkillLoader::getSkills();
        if (empty($skills)) return null;

        // 直接提到技能名
        foreach ($skills as $name => $skill) {
            if (mb_strpos($message, $skill['name']) !== false) {
                return $name;
            }
            $label = $skill['label'] ?? '';
            if ($label && mb_strpos($message, $label) !== false) {
                return $name;
            }
        }

        // 关键词匹配
        $keywords = [
            'quote-cn' => ['报价单', '中文报价', '报价', '打印', 'PDF'],
        ];

        foreach ($keywords as $skillName => $terms) {
            foreach ($terms as $term) {
                if (mb_strpos($message, $term) !== false) {
                    if (isset($skills[$skillName])) return $skillName;
                }
            }
        }

        return null;
    }

    /**
     * 获取技能包的 AI 系统指令（SKILL.md 的 Markdown 正文）
     */
    public static function getSkillPrompt(string $skillName): string
    {
        $skill = SkillLoader::getSkill($skillName);
        if (!$skill) return '';

        return $skill['instructions'] ?? '';
    }

    /**
     * 获取技能包的工具定义（DeepSeek function call 格式）
     * 从 SKILL.md 的「可用工具」章节解析，并补全参数 schema
     */
    public static function getSkillTools(string $skillName): array
    {
        $skill = SkillLoader::getSkill($skillName);
        if (!$skill) return [];

        $instructions = $skill['instructions'] ?? '';
        $tools = self::parseToolsFromMarkdown($instructions, $skillName);

        return $tools;
    }

    /**
     * 执行技能包的工具调用
     */
    public static function executeTool(string $skillName, string $toolName, array $args): array
    {
        $skill = SkillLoader::getSkill($skillName);
        if (!$skill) {
            return ['error' => "技能包 {$skillName} 未安装"];
        }

        // 优先查技能包自身的 service 命名空间，再 fallback 到旧路径
        $serviceClass = 'skills\\' . $skillName . '\\service\\' . self::getServiceClass($skillName);
        if (!class_exists($serviceClass)) {
            // fallback: 旧路径 app\common\service\crm\
            $serviceClass = 'app\\common\\service\\crm\\' . self::getServiceClass($skillName);
            if (!class_exists($serviceClass)) {
                return ['error' => "服务类 skills\\{$skillName}\\service\\" . self::getServiceClass($skillName) . ' 不存在'];
            }
        }

        try {
            $service = new $serviceClass();
            $method = self::mapToolToMethod($toolName);
            if (!method_exists($service, $method)) {
                return ['error' => "工具 {$toolName}（方法 {$method}）未实现"];
            }

            // 智能参数适配：根据方法签名自动匹配
            $ref = new \ReflectionMethod($service, $method);
            $params = $ref->getParameters();

            if (empty($params)) {
                $result = $service->$method();
            } elseif (count($params) === 1) {
                $firstType = $params[0]->getType();
                $typeName = $firstType ? $firstType->getName() : '';
                // 如果方法接受 array，传整个 $args；否则提取 id
                if ($typeName === 'array') {
                    $result = $service->$method($args);
                } elseif (!empty($args['id'])) {
                    $result = $service->$method((int)$args['id']);
                } else {
                    $result = $service->$method($args);
                }
            } else {
                $result = call_user_func_array([$service, $method], array_values($args));
            }

            if (is_array($result) && isset($result['success'])) {
                return $result;
            }
            return ['success' => true, 'data' => $result];
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * 从 SKILL.md 的 Markdown 正文中解析工具列表
     * 识别 `- \`toolName\` — 描述` 格式
     */
    private static function parseToolsFromMarkdown(string $instructions, string $skillName): array
    {
        $tools = [];

        // 匹配「可用工具」章节中的 - `toolName` — description 格式
        if (!preg_match_all('/-\s*`(\w+)`\s*[—\-]\s*(.+)/u', $instructions, $matches, PREG_SET_ORDER)) {
            return $tools;
        }

        foreach ($matches as $m) {
            $toolName = $m[1];
            $description = trim($m[2]);

            // 根据技能包和工具名生成参数 schema
            $params = self::guessParams($skillName, $toolName, $description);

            $tools[] = [
                'name' => "skill_{$skillName}_{$toolName}",
                'description' => "[技能:{$skillName}] {$description}",
                'parameters' => $params,
            ];
        }

        return $tools;
    }

    /**
     * 根据工具名猜测参数 schema
     */
    private static function guessParams(string $skillName, string $toolName, string $description): array
    {
        $properties = [];
        $required = [];

        // 数据查询类工具：支持多种查询方式
        if (strpos($toolName, 'getQuotation') !== false || strpos($toolName, 'QuotationData') !== false) {
            $properties['id'] = ['type' => 'integer', 'description' => '报价单数字ID（优先使用）'];
            $properties['quotation_no'] = ['type' => 'string', 'description' => '报价单号，如 Q-20260506-103'];
            $properties['customer_name'] = ['type' => 'string', 'description' => '客户名称'];
            $properties['keyword'] = ['type' => 'string', 'description' => '通用搜索关键词'];
        }
        // 渲染/导出类工具：只需要 ID
        elseif (strpos($toolName, 'export') !== false || strpos($toolName, 'render') !== false
            || strpos($toolName, 'preview') !== false) {
            $properties['id'] = ['type' => 'integer', 'description' => '报价单ID'];
            $required = ['id'];
        }
        // 保存类工具
        elseif (strpos($toolName, 'save') !== false || strpos($toolName, 'update') !== false) {
            $properties['data'] = ['type' => 'object', 'description' => '要保存的配置数据'];
            $required = ['data'];
        }
        // 搜索类工具
        elseif (strpos($toolName, 'search') !== false) {
            $properties['keyword'] = ['type' => 'string', 'description' => '搜索关键词'];
            $required = ['keyword'];
        }

        return [
            'type' => 'object',
            'properties' => $properties,
            'required' => $required,
        ];
    }

    /**
     * 工具名 → Service 方法名映射
     */
    private static function mapToolToMethod(string $toolName): string
    {
        $map = [
            'getQuotationData' => 'getQuotationData',
            'renderPreview' => 'preview',
            'exportPdf' => 'exportPdf',
            'saveTemplateConfig' => 'saveTemplate',
            'getTemplateConfig' => 'getTemplateConfig',
        ];

        return $map[$toolName] ?? $toolName;
    }

    /**
     * 技能名 → Service 类名映射
     */
    private static function getServiceClass(string $skillName): string
    {
        $map = [
            'quote-cn' => 'QuotationCnService',
        ];

        return $map[$skillName] ?? ucfirst($skillName) . 'Service';
    }

    /**
     * 获取所有已安装技能的工具（合并为一个数组）
     */
    public static function getAllSkillTools(): array
    {
        $allTools = [];
        foreach (SkillLoader::getSkills() as $name => $skill) {
            $tools = self::getSkillTools($name);
            $allTools = array_merge($allTools, $tools);
        }
        return $allTools;
    }
}
