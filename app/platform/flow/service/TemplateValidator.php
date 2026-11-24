<?php

declare(strict_types=1);

namespace app\platform\flow\service;

use app\platform\flow\model\Node;

/**
 * 模板发布前校验
 */
class TemplateValidator
{
    /**
     * @param array $nodes 节点数组（每项含 node_key/type/...）
     * @param array $edges 连线数组（from_key/to_key/condition_json/is_default）
     * @param array $allowedVariables 允许的条件变量
     * @return string[] 错误列表，空数组表示通过
     */
    public static function validate(array $nodes, array $edges, array $allowedVariables): array
    {
        $errors = [];

        if (empty($nodes)) return ['模板没有任何节点'];

        $keys = [];
        $rejectTargets = [];
        $startKeys = [];
        $endKeys = [];
        $approvalCount = 0;
        $nodeMap = [];

        foreach ($nodes as $node) {
            $key = trim((string)($node['node_key'] ?? ''));
            $type = (string)($node['type'] ?? '');
            $name = (string)($node['name'] ?? $key);

            if ($key === '') {
                $errors[] = '存在没有 node_key 的节点';
                continue;
            }
            if (!preg_match('/^[A-Za-z][A-Za-z0-9_\-]{0,49}$/', $key)) {
                $errors[] = "节点 key 不合法：{$key}（字母开头，仅字母数字下划线短横线）";
            }
            if (isset($keys[$key])) {
                $errors[] = "节点 key 重复：{$key}";
                continue;
            }
            $keys[$key] = true;
            $nodeMap[$key] = $node;

            if (!isset(Node::typeMap()[$type])) {
                $errors[] = "节点【{$name}】类型不合法：{$type}";
            }
            if ($type === Node::TYPE_START) $startKeys[] = $key;
            if ($type === Node::TYPE_END) $endKeys[] = $key;
            if ($type === Node::TYPE_APPROVAL) $approvalCount++;

            if (isset(Node::TASK_TYPES[$type])) {
                $rules = self::decode($node['assignee_rules'] ?? []);
                if (empty($rules)) {
                    $errors[] = "节点【{$name}】没有配置指派规则";
                }
                $mode = (string)($node['complete_mode'] ?? 'any');
                if (!in_array($mode, ['all', 'any', 'ratio', 'sequential'], true)) {
                    $errors[] = "节点【{$name}】完成规则不合法：{$mode}";
                }
                if ($mode === 'ratio') {
                    $ratio = (int)($node['complete_ratio'] ?? 0);
                    if ($ratio <= 0 || $ratio > 100) {
                        $errors[] = "节点【{$name}】会签比例必须在 1-100";
                    }
                }
            }

            if ($type === Node::TYPE_GATEWAY) {
                $gatewayType = (string)($node['gateway_type'] ?? '');
                if (!in_array($gatewayType, ['exclusive', 'parallel', 'inclusive'], true)) {
                    $errors[] = "网关【{$name}】类型不合法：{$gatewayType}";
                }
                if (!in_array((string)($node['join_mode'] ?? 'all'), ['all', 'any'], true)) {
                    $errors[] = "网关【{$name}】合并方式不合法";
                }
            }

            if (!empty($node['condition_json'])) {
                foreach (ConditionEvaluator::validateAll($node['condition_json'], $allowedVariables) as $err) {
                    $errors[] = "节点【{$name}】{$err}";
                }
            }

            $reject = self::decode($node['reject_policy'] ?? []);
            if (!empty($reject['type']) && (string)$reject['type'] === 'back_to') {
                $rejectTargets[$key] = ['name' => $name, 'target' => (string)($reject['target'] ?? '')];
            }
        }

        foreach ($rejectTargets as $key => $item) {
            $target = $item['target'];
            if ($target === '' || !isset($keys[$target])) {
                $errors[] = "节点【{$item['name']}】驳回目标不存在：{$target}";
                continue;
            }
            $targetType = (string)($nodeMap[$target]['type'] ?? '');
            if (!isset(Node::TASK_TYPES[$targetType])) {
                $errors[] = "节点【{$item['name']}】驳回目标必须是任务/审批/签字/收款节点";
            }
        }

        if (count($startKeys) !== 1) {
            $errors[] = '必须且只能有一个开始节点（当前 ' . count($startKeys) . ' 个）';
        }
        if (empty($endKeys)) {
            $errors[] = '至少需要一个结束节点';
        }
        if ($approvalCount === 0) {
            $errors[] = '至少需要一个审核节点';
        }

        // 连线校验
        $outgoing = [];
        $incoming = [];
        $adjacency = [];
        $defaultCount = [];
        foreach ($edges as $edge) {
            $from = (string)($edge['from_key'] ?? '');
            $to = (string)($edge['to_key'] ?? '');
            if (!isset($keys[$from])) {
                $errors[] = "连线起点不存在：{$from}";
                continue;
            }
            if (!isset($keys[$to])) {
                $errors[] = "连线终点不存在：{$to}";
                continue;
            }
            if ($from === $to) {
                $errors[] = "不允许自环连线：{$from}";
                continue;
            }
            $outgoing[$from][] = $edge;
            $incoming[$to][] = $edge;
            $adjacency[$from][] = $to;

            if (!empty($edge['is_default'])) {
                $defaultCount[$from] = ($defaultCount[$from] ?? 0) + 1;
            }
            if (!empty($edge['condition_json'])) {
                foreach (ConditionEvaluator::validateAll($edge['condition_json'], $allowedVariables) as $err) {
                    $errors[] = "连线 {$from} → {$to} {$err}";
                }
            }
        }

        foreach ($nodeMap as $key => $node) {
            $type = (string)($node['type'] ?? '');
            $out = $outgoing[$key] ?? [];
            if ($type === Node::TYPE_END) {
                if (!empty($out)) $errors[] = "结束节点【{$node['name']}】不应有出边";
                continue;
            }
            if (empty($out)) {
                $errors[] = "节点【{$node['name']}】没有出边，流程会卡住";
                continue;
            }
            if ($type === Node::TYPE_GATEWAY) {
                $gatewayType = (string)($node['gateway_type'] ?? '');
                if ($gatewayType === 'exclusive' && count($out) > 1) {
                    if (($defaultCount[$key] ?? 0) === 0) {
                        $errors[] = "排他网关【{$node['name']}】缺少默认分支";
                    }
                    if (($defaultCount[$key] ?? 0) > 1) {
                        $errors[] = "排他网关【{$node['name']}】只能有一个默认分支";
                    }
                }
                $incomingCount = count($incoming[$key] ?? []);
                if ($gatewayType === 'parallel' && $incomingCount <= 1 && count($out) < 2) {
                    $errors[] = "并行网关【{$node['name']}】至少需要两个分支";
                }
            }
        }

        // 可达性：start → 所有节点
        if (count($startKeys) === 1) {
            $reachable = self::reachable($startKeys[0], $adjacency);
            foreach ($nodeMap as $key => $node) {
                if (!isset($reachable[$key])) {
                    $errors[] = "节点【{$node['name']}】从开始节点不可达";
                }
            }
        }

        // 终止性：所有节点都能到 end
        if (!empty($endKeys)) {
            $reverse = [];
            foreach ($adjacency as $from => $tos) {
                foreach ($tos as $to) $reverse[$to][] = $from;
            }
            $canEnd = [];
            foreach ($endKeys as $endKey) {
                foreach (self::reachable($endKey, $reverse) as $k => $v) {
                    $canEnd[$k] = true;
                }
            }
            foreach ($nodeMap as $key => $node) {
                if (!isset($canEnd[$key])) {
                    $errors[] = "节点【{$node['name']}】无法到达结束节点";
                }
            }
        }

        // 环检测
        if (self::hasCycle($nodeMap, $adjacency)) {
            $errors[] = '流程存在环，v1 暂不支持循环';
        }

        return array_values(array_unique($errors));
    }

    private static function decode($value): array
    {
        if (is_array($value)) return $value;
        if (is_string($value) && trim($value) !== '') {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }
        return [];
    }

    private static function reachable(string $start, array $adjacency): array
    {
        $seen = [$start => true];
        $queue = [$start];
        while ($queue) {
            $current = array_shift($queue);
            foreach ($adjacency[$current] ?? [] as $next) {
                if (!isset($seen[$next])) {
                    $seen[$next] = true;
                    $queue[] = $next;
                }
            }
        }
        return $seen;
    }

    private static function hasCycle(array $nodeMap, array $adjacency): bool
    {
        $state = []; // 0 unvisited, 1 visiting, 2 done
        $dfs = function (string $key) use (&$dfs, &$state, $adjacency): bool {
            $state[$key] = 1;
            foreach ($adjacency[$key] ?? [] as $next) {
                $s = $state[$next] ?? 0;
                if ($s === 1) return true;
                if ($s === 0 && $dfs($next)) return true;
            }
            $state[$key] = 2;
            return false;
        };
        foreach ($nodeMap as $key => $node) {
            if (($state[$key] ?? 0) === 0 && $dfs((string)$key)) return true;
        }
        return false;
    }
}
