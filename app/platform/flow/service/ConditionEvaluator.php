<?php

declare(strict_types=1);

namespace app\platform\flow\service;

use app\platform\flow\FlowException;

/**
 * 结构化条件求值器
 *
 * 条件 JSON 示例：
 *   {"all":[{"field":"risk_level","op":"in","value":["A"]},
 *           {"field":"total_amount","op":"gte","value":10000}]}
 * 支持 all / any / not 嵌套，不执行任意代码。
 */
class ConditionEvaluator
{
    public const OPERATORS = [
        'eq', 'neq', 'in', 'not_in', 'gt', 'gte', 'lt', 'lte',
        'contains', 'not_contains', 'empty', 'not_empty',
    ];

    /**
     * @param mixed $condition JSON 字符串、数组或 null
     * @param array $variables 流程变量
     */
    public static function evaluate($condition, array $variables): bool
    {
        if ($condition === null || $condition === '' || $condition === [] || $condition === '[]') {
            return true;
        }
        if (is_string($condition)) {
            $decoded = json_decode($condition, true);
            if (!is_array($decoded)) return false;
            $condition = $decoded;
        }
        if (!is_array($condition)) return false;

        if (array_key_exists('all', $condition)) {
            $items = is_array($condition['all']) ? $condition['all'] : [];
            foreach ($items as $item) {
                if (!self::evaluate($item, $variables)) return false;
            }
            return true;
        }
        if (array_key_exists('any', $condition)) {
            $items = is_array($condition['any']) ? $condition['any'] : [];
            foreach ($items as $item) {
                if (self::evaluate($item, $variables)) return true;
            }
            return false;
        }
        if (array_key_exists('not', $condition)) {
            return !self::evaluate($condition['not'], $variables);
        }

        // 列表视为 all
        if (self::isList($condition)) {
            foreach ($condition as $item) {
                if (!self::evaluate($item, $variables)) return false;
            }
            return true;
        }

        return self::evaluateLeaf($condition, $variables);
    }

    private static function evaluateLeaf(array $leaf, array $variables): bool
    {
        $field = (string)($leaf['field'] ?? '');
        $op = (string)($leaf['op'] ?? '');
        $expected = $leaf['value'] ?? null;

        if ($field === '' || !in_array($op, self::OPERATORS, true)) return false;

        $actual = self::valueOf($field, $variables);
        $exists = $actual !== null;

        switch ($op) {
            case 'empty':
                return !$exists || $actual === '' || $actual === [] || $actual === 0 || $actual === '0';
            case 'not_empty':
                return $exists && !($actual === '' || $actual === [] || $actual === 0 || $actual === '0');
            case 'eq':
                return $exists && self::compare($actual, $expected) === 0;
            case 'neq':
                return !$exists || self::compare($actual, $expected) !== 0;
            case 'gt':
                return $exists && self::compare($actual, $expected) > 0;
            case 'gte':
                return $exists && self::compare($actual, $expected) >= 0;
            case 'lt':
                return $exists && self::compare($actual, $expected) < 0;
            case 'lte':
                return $exists && self::compare($actual, $expected) <= 0;
            case 'in':
                $set = is_array($expected) ? $expected : [$expected];
                foreach ($set as $item) {
                    if (self::compare($actual, $item) === 0) return true;
                }
                return false;
            case 'not_in':
                $set = is_array($expected) ? $expected : [$expected];
                foreach ($set as $item) {
                    if (self::compare($actual, $item) === 0) return false;
                }
                return true;
            case 'contains':
                if (is_array($actual)) return in_array($expected, $actual, false);
                return $exists && strpos((string)$actual, (string)$expected) !== false;
            case 'not_contains':
                if (is_array($actual)) return !in_array($expected, $actual, false);
                return !$exists || strpos((string)$actual, (string)$expected) === false;
        }

        return false;
    }

    /**
     * 支持 a.b.c 取值
     */
    public static function valueOf(string $path, array $variables)
    {
        if (array_key_exists($path, $variables)) return $variables[$path];

        $current = $variables;
        foreach (explode('.', $path) as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return null;
            }
            $current = $current[$segment];
        }
        return $current;
    }

    private static function compare($actual, $expected): int
    {
        if (is_numeric($actual) && is_numeric($expected)) {
            $a = (float)$actual;
            $b = (float)$expected;
            return $a <=> $b;
        }
        if (is_bool($actual) || is_bool($expected)) {
            return (int)(bool)$actual <=> (int)(bool)$expected;
        }
        return strcmp((string)$actual, (string)$expected) <=> 0;
    }

    private static function isList(array $arr): bool
    {
        if (function_exists('array_is_list')) return array_is_list($arr);
        $i = 0;
        foreach ($arr as $k => $v) {
            if ($k !== $i++) return false;
        }
        return true;
    }

    /**
     * 校验条件结构，返回错误列表（空数组表示通过）
     */
    public static function validateAll($condition, array $allowedFields): array
    {
        $errors = [];
        self::walk($condition, $allowedFields, $errors, '条件');
        return $errors;
    }

    private static function walk($condition, array $allowedFields, array &$errors, string $path): void
    {
        if ($condition === null || $condition === '' || $condition === []) return;
        if (is_string($condition)) {
            $condition = json_decode($condition, true);
            if (!is_array($condition)) {
                $errors[] = "{$path}：不是合法的 JSON";
                return;
            }
        }
        if (!is_array($condition)) {
            $errors[] = "{$path}：结构非法";
            return;
        }

        foreach (['all', 'any'] as $key) {
            if (array_key_exists($key, $condition)) {
                if (!is_array($condition[$key])) {
                    $errors[] = "{$path}.{$key}：必须是数组";
                    return;
                }
                foreach ($condition[$key] as $i => $item) {
                    self::walk($item, $allowedFields, $errors, "{$path}.{$key}[{$i}]");
                }
                return;
            }
        }
        if (array_key_exists('not', $condition)) {
            self::walk($condition['not'], $allowedFields, $errors, "{$path}.not");
            return;
        }

        if (self::isList($condition)) {
            foreach ($condition as $i => $item) {
                self::walk($item, $allowedFields, $errors, "{$path}[{$i}]");
            }
            return;
        }

        $field = (string)($condition['field'] ?? '');
        $op = (string)($condition['op'] ?? '');
        if ($field === '') {
            $errors[] = "{$path}：缺少 field";
        } elseif (!in_array($field, $allowedFields, true)) {
            $errors[] = "{$path}：变量 {$field} 不在允许列表";
        }
        if (!in_array($op, self::OPERATORS, true)) {
            $errors[] = "{$path}：操作符 {$op} 不支持";
        }
    }
}
