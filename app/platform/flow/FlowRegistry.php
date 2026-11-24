<?php

declare(strict_types=1);

namespace app\platform\flow;

use app\platform\flow\service\TemplateService;
use think\facade\Log;

/**
 * 业务流程注册表
 *
 * 引擎通过它拿到 Hook、名称、详情路由、可用变量；
 * 不直接依赖任何 mpp/* 或 skills/* 类。
 */
class FlowRegistry
{
    /** 系统内置变量 */
    public const SYSTEM_VARIABLES = [
        'biz_type', 'biz_id', 'initiator_id', 'initiator_dept_id',
        'total_amount', 'now', 'weekday',
    ];

    public const SYSTEM_VARIABLE_LABELS = [
        'biz_type' => '业务类型（编号）',
        'biz_id' => '业务ID',
        'initiator_id' => '发起人',
        'initiator_dept_id' => '发起人部门',
        'total_amount' => '金额',
        'now' => '当前时间',
        'weekday' => '星期',
    ];

    public static function bizTypes(): array
    {
        $types = config('flow.biz_types');
        return is_array($types) ? $types : [];
    }

    public static function has(string $bizType): bool
    {
        return isset(self::bizTypes()[$bizType]);
    }

    public static function get(string $bizType): array
    {
        $types = self::bizTypes();
        if (!isset($types[$bizType])) {
            throw new FlowException("业务类型未注册：{$bizType}");
        }
        return $types[$bizType];
    }

    public static function label(string $bizType): string
    {
        $config = self::get($bizType);
        return (string)($config['name'] ?? $bizType);
    }

    /**
     * 业务类型是否对租户可见
     *
     * 通过 Hook 类所属的 skill 判断：skills/fiscal/... → 读 fiscal 的 manifest.json。
     * mpp/*、app/* 的 Hook 不属于 skill，默认全部可见。
     */
    public static function bizVisible(string $bizType, ?int $storeId): bool
    {
        if (!self::has($bizType)) return false;

        $storeId = (int)$storeId;
        if ($storeId <= 0) return true;

        $config = self::get($bizType);
        $hook = ltrim((string)($config['hook'] ?? ''), '\\');
        if ($hook === '') return true;

        return \cores\SkillAccess::enabledForClass($hook, $storeId);
    }

    /**
     * 前端业务类型下拉
     *
     * @param int|null $storeId 传租户时过滤掉未开通 skill 的业务类型
     */
    public static function options(?int $storeId = null): array
    {
        $list = [];
        foreach (self::bizTypes() as $key => $config) {
            if ($storeId !== null && !self::bizVisible($key, $storeId)) continue;
            $list[] = [
                'value' => $key,
                'label' => (string)($config['name'] ?? $key),
                'default_template' => (string)($config['default_template'] ?? ''),
            ];
        }
        return $list;
    }

    /**
     * 实例化业务 Hook
     */
    public static function hook(string $bizType): FlowBizInterface
    {
        $config = self::get($bizType);
        $class = (string)($config['hook'] ?? '');
        if ($class === '' || !class_exists($class)) {
            throw new FlowException("业务 Hook 未配置或不存在：{$bizType}");
        }
        $hook = new $class();
        if (!$hook instanceof FlowBizInterface) {
            throw new FlowException("业务 Hook 必须实现 FlowBizInterface：{$class}");
        }
        return $hook;
    }

    /**
     * 允许在条件里使用的变量（仅值，用于发布校验）
     */
    public static function allowedVariables(string $bizType): array
    {
        return array_column(self::allowedVariableOptions($bizType), 'value');
    }

    /**
     * 变量选项：中文名 + 英文值 + 分组，供设计器下拉展示
     */
    public static function allowedVariableOptions(string $bizType): array
    {
        $config = self::get($bizType);
        $own = $config['variables'] ?? [];
        if (!is_array($own)) $own = [];

        $options = [];
        foreach (self::SYSTEM_VARIABLES as $value) {
            $options[$value] = [
                'value' => $value,
                'label' => self::SYSTEM_VARIABLE_LABELS[$value] ?? $value,
                'group' => '系统',
            ];
        }

        foreach ($own as $key => $value) {
            if (is_int($key)) {
                $name = (string)$value;
                $label = $name;
            } else {
                $name = (string)$key;
                $label = (string)$value;
            }
            if ($name === '') continue;
            $options[$name] = [
                'value' => $name,
                'label' => $label !== '' ? $label : $name,
                'group' => '业务',
            ];
        }

        return array_values($options);
    }

    /**
     * 系统预置模板（不依赖数据库记录）
     */
    public static function presets(int $storeId, string $bizType): array
    {
        $config = self::get($bizType);
        $class = (string)($config['template_presets'] ?? '');
        if ($class === '' || !class_exists($class)) return [];

        $provider = new $class();
        if (!$provider instanceof FlowTemplatePresetInterface) {
            throw new FlowException("系统预设提供方必须实现 FlowTemplatePresetInterface：{$class}");
        }

        $list = $provider->presets($storeId);
        if (!is_array($list)) return [];

        $result = [];
        foreach ($list as $preset) {
            if (!is_array($preset)) continue;
            $key = trim((string)($preset['key'] ?? ''));
            if ($key === '') continue;
            $result[] = [
                'key' => $key,
                'name' => (string)($preset['name'] ?? $key),
                'description' => (string)($preset['description'] ?? ''),
                'nodes' => is_array($preset['nodes'] ?? null) ? $preset['nodes'] : [],
                'edges' => is_array($preset['edges'] ?? null) ? $preset['edges'] : [],
            ];
        }
        return $result;
    }

    /**
     * 确保业务默认模板存在（首次使用时由业务方 seeder 生成）
     */
    public static function ensureDefaultTemplate(int $storeId, string $bizType): void
    {
        $config = self::get($bizType);
        $seeder = (string)($config['template_seeder'] ?? '');
        if ($seeder === '' || !class_exists($seeder)) return;
        (new $seeder())->ensure($storeId, $bizType);
    }

    /**
     * 解析业务标题字段
     */
    public static function title(array $snapshot, array $config): string
    {
        $field = (string)($config['title_field'] ?? 'title');
        return (string)($snapshot[$field] ?? $snapshot['title'] ?? '');
    }
}
