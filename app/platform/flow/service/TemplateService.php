<?php

declare(strict_types=1);

namespace app\platform\flow\service;

use app\platform\flow\FlowException;
use app\platform\flow\FlowRegistry;
use app\platform\flow\model\Edge;
use app\platform\flow\model\Node;
use app\platform\flow\model\Template;
use think\facade\Db;

/**
 * 流程模板服务
 */
class TemplateService
{
    private const NODE_FIELDS = [
        'node_key', 'name', 'type', 'gateway_type', 'join_mode', 'assignee_mode',
        'assignee_rules', 'complete_mode', 'complete_ratio', 'actions', 'form_schema',
        'condition_json', 'reject_policy', 'sync_status', 'sync_fields', 'sla_hours',
        'remind_before_hours', 'escalate_to', 'position_x', 'position_y', 'config', 'sort',
    ];

    private const JSON_NODE_FIELDS = [
        'assignee_rules', 'actions', 'form_schema', 'condition_json',
        'reject_policy', 'sync_fields', 'escalate_to', 'config',
    ];

    private const EDGE_FIELDS = [
        'from_key', 'to_key', 'condition_json', 'label', 'is_default', 'sort',
    ];

    private int $storeId;

    public function __construct(int $storeId)
    {
        $this->storeId = $storeId;
    }

    public function getList(array $where = [], int $page = 1, int $limit = 20): array
    {
        $query = Db::name('flow_template')
            ->where('store_id', $this->storeId)
            ->where('is_delete', 0);

        foreach (['biz_type', 'status', 'code'] as $key) {
            $value = $where[$key] ?? '';
            if ($value !== '' && $value !== null) $query->where($key, $value);
        }
        $visibleBizTypes = $where['visible_biz_types'] ?? null;
        if (is_array($visibleBizTypes)) {
            if (empty($visibleBizTypes)) {
                return ['list' => [], 'total' => 0, 'page' => $page, 'limit' => $limit];
            }
            $query->whereIn('biz_type', $visibleBizTypes);
        }

        $keyword = trim((string)($where['keyword'] ?? ''));
        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->whereOr('code', 'like', "%{$keyword}%")
                  ->whereOr('description', 'like', "%{$keyword}%");
            });
        }

        $total = (clone $query)->count();
        $rows = $query->order(['biz_type' => 'asc', 'code' => 'asc', 'version' => 'desc'])
            ->page($page, $limit)
            ->select()
            ->toArray();

        $ids = array_column($rows, 'id');
        $nodeCounts = [];
        if (!empty($ids)) {
            $counts = Db::name('flow_node')
                ->where('store_id', $this->storeId)
                ->whereIn('template_id', $ids)
                ->group('template_id')
                ->field('template_id, COUNT(*) AS cnt')
                ->select()
                ->toArray();
            foreach ($counts as $row) $nodeCounts[(int)$row['template_id']] = (int)$row['cnt'];
        }

        $list = [];
        foreach ($rows as $row) {
            $list[] = $this->formatTemplate($row, $nodeCounts[(int)$row['id']] ?? 0);
        }

        return ['list' => $list, 'total' => $total, 'page' => $page, 'limit' => $limit];
    }

    public function detail(int $id): array
    {
        $template = $this->findTemplate($id);
        $nodes = Db::name('flow_node')
            ->where('store_id', $this->storeId)
            ->where('template_id', $id)
            ->order('sort', 'asc')->order('id', 'asc')
            ->select()->toArray();
        $edges = Db::name('flow_edge')
            ->where('store_id', $this->storeId)
            ->where('template_id', $id)
            ->order('sort', 'asc')->order('id', 'asc')
            ->select()->toArray();

        return [
            'template' => $this->formatTemplate($template, count($nodes)),
            'nodes' => array_map([$this, 'formatNode'], $nodes),
            'edges' => array_map([$this, 'formatEdge'], $edges),
            'allowed_variables' => $this->allowedVariableOptions((string)$template['biz_type']),
            'node_types' => Node::typeMap(),
        ];
    }

    public function create(array $data, int $actorId): array
    {
        $bizType = trim((string)($data['biz_type'] ?? ''));
        if (!FlowRegistry::has($bizType)) {
            throw new FlowException('请选择已注册的业务类型');
        }
        $name = trim((string)($data['name'] ?? ''));
        if ($name === '') throw new FlowException('请填写模板名称');

        $config = FlowRegistry::get($bizType);

        // 从现有模板复制，避免用户从空白画布开始设计
        $sourceId = (int)($data['source_template_id'] ?? 0);
        $useDefault = (int)($data['use_default'] ?? 0) === 1;
        $source = null;
        if ($sourceId > 0) {
            $source = Db::name('flow_template')
                ->where('store_id', $this->storeId)
                ->where('id', $sourceId)
                ->where('is_delete', 0)
                ->find();
            if (!$source) throw new FlowException('要复制的模板不存在');
            if ((string)$source['biz_type'] !== $bizType) {
                throw new FlowException('要复制的模板与所选业务类型不一致');
            }
        } elseif ($useDefault) {
            $source = Db::name('flow_template')
                ->where('store_id', $this->storeId)
                ->where('biz_type', $bizType)
                ->where('status', Template::STATUS_PUBLISHED)
                ->where('is_default', 1)
                ->where('is_delete', 0)
                ->order('version', 'desc')
                ->find();
            if (!$source) {
                FlowRegistry::ensureDefaultTemplate($this->storeId, $bizType);
                $source = Db::name('flow_template')
                    ->where('store_id', $this->storeId)
                    ->where('biz_type', $bizType)
                    ->where('status', Template::STATUS_PUBLISHED)
                    ->where('is_default', 1)
                    ->where('is_delete', 0)
                    ->order('version', 'desc')
                    ->find();
            }
            if (!$source) {
                throw new FlowException('该业务还没有默认模板，请选择空白模板或先创建一套流程');
            }
        }

        // 系统预置模板：不依赖库里已有记录
        $presetKey = trim((string)($data['preset_key'] ?? ''));
        $preset = null;
        if (!$source && $presetKey !== '') {
            foreach (FlowRegistry::presets($this->storeId, $bizType) as $item) {
                if ($item['key'] === $presetKey) {
                    $preset = $item;
                    break;
                }
            }
            if (!$preset) throw new FlowException('系统预设模板不存在或已下线');
            if (empty($preset['nodes'])) throw new FlowException('系统预设模板没有节点数据');
        }

        $code = trim((string)($data['code'] ?? ''));
        if ($code === '') {
            $code = $source
                ? (string)$source['code']
                : (string)($config['default_template'] ?? ($bizType . '_default'));
        }
        if (!preg_match('/^[A-Za-z][A-Za-z0-9_\-]{0,49}$/', $code)) {
            throw new FlowException('模板编码只能使用字母、数字、下划线、短横线，且以字母开头');
        }

        $maxVersion = (int)Db::name('flow_template')
            ->where('store_id', $this->storeId)
            ->where('code', $code)
            ->max('version');
        $version = $maxVersion + 1;

        Db::startTrans();
        try {
            $templateId = (int)Db::name('flow_template')->insertGetId([
                'store_id' => $this->storeId,
                'code' => $code,
                'name' => $name,
                'biz_type' => $bizType,
                'version' => $version,
                'status' => Template::STATUS_DRAFT,
                'is_default' => 0,
                'description' => (string)($data['description'] ?? ''),
                'created_by' => $actorId,
                'create_time' => time(),
                'update_time' => time(),
                'is_delete' => 0,
            ]);

            if ($source || $preset) {
                if ($preset) {
                    $nodes = $preset['nodes'];
                    $edges = $preset['edges'];
                } else {
                $nodes = Db::name('flow_node')
                    ->where('store_id', $this->storeId)
                    ->where('template_id', (int)$source['id'])
                    ->order('sort', 'asc')->order('id', 'asc')
                    ->select()->toArray();
                $edges = Db::name('flow_edge')
                    ->where('store_id', $this->storeId)
                    ->where('template_id', (int)$source['id'])
                    ->order('sort', 'asc')->order('id', 'asc')
                    ->select()->toArray();
                }

                foreach ($nodes as &$node) {
                    unset($node['id']);
                    $this->insertNode($templateId, $node);
                }
                unset($node);

                foreach ($edges as &$edge) {
                    unset($edge['id']);
                    $this->insertEdge($templateId, $edge);
                }
                unset($edge);

                $this->saveGraph($templateId, $nodes, $edges);
            } else {
                $nodes = [
                    [
                        'node_key' => 'start', 'name' => '开始', 'type' => Node::TYPE_START,
                        'position_x' => 120, 'position_y' => 220, 'sort' => 10,
                    ],
                    [
                        'node_key' => 'end', 'name' => '结束', 'type' => Node::TYPE_END,
                        'position_x' => 720, 'position_y' => 220, 'sort' => 999,
                    ],
                ];
                foreach ($nodes as $node) {
                    $this->insertNode($templateId, $node);
                }
                $this->insertEdge($templateId, [
                    'from_key' => 'start', 'to_key' => 'end', 'label' => '', 'is_default' => 0, 'sort' => 10,
                ]);

                $this->saveGraph($templateId, $nodes, [
                    ['from_key' => 'start', 'to_key' => 'end', 'label' => '', 'is_default' => 0, 'sort' => 10],
                ]);
            }

            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            throw $e;
        }

        return $this->detail($templateId);
    }

    public function saveDraft(int $id, array $payload, int $actorId): array
    {
        $template = $this->findTemplate($id);
        if ((string)$template['status'] !== Template::STATUS_DRAFT) {
            throw new FlowException('只有草稿可以编辑；已发布模板请先复制新版本');
        }

        $nodes = $payload['nodes'] ?? [];
        $edges = $payload['edges'] ?? [];
        if (empty($nodes) && !empty($payload['graph'])) {
            $graph = is_string($payload['graph']) ? json_decode($payload['graph'], true) : $payload['graph'];
            if (is_array($graph)) {
                $nodes = $graph['nodes'] ?? [];
                $edges = $graph['edges'] ?? [];
            }
        }
        if (!is_array($nodes) || !is_array($edges)) {
            throw new FlowException('节点/连线数据不合法');
        }

        $cleanNodes = [];
        foreach ($nodes as $node) {
            if (!is_array($node)) continue;
            $nodeKey = trim((string)($node['node_key'] ?? $node['key'] ?? $node['id'] ?? ''));
            if ($nodeKey === '') continue;
            $node['node_key'] = $nodeKey;
            $cleanNodes[] = $this->cleanNode($node);
        }

        $cleanEdges = [];
        foreach ($edges as $edge) {
            if (!is_array($edge)) continue;
            $from = trim((string)($edge['from_key'] ?? $edge['source'] ?? ''));
            $to = trim((string)($edge['to_key'] ?? $edge['target'] ?? ''));
            if ($from === '' || $to === '') continue;
            $edge['from_key'] = $from;
            $edge['to_key'] = $to;
            $cleanEdges[] = $this->cleanEdge($edge);
        }

        Db::startTrans();
        try {
            Db::name('flow_node')->where('store_id', $this->storeId)->where('template_id', $id)->delete();
            Db::name('flow_edge')->where('store_id', $this->storeId)->where('template_id', $id)->delete();

            foreach ($cleanNodes as $node) {
                $this->insertNode($id, $node);
            }
            foreach ($cleanEdges as $edge) {
                $this->insertEdge($id, $edge);
            }

            $this->saveGraph($id, $cleanNodes, $cleanEdges, [
                'name' => trim((string)($payload['name'] ?? '')) ?: null,
                'description' => $payload['description'] ?? null,
            ]);

            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            throw $e;
        }

        return $this->detail($id);
    }

    public function validateTemplate(int $id): array
    {
        $template = $this->findTemplate($id);
        $nodes = Db::name('flow_node')->where('store_id', $this->storeId)->where('template_id', $id)->select()->toArray();
        $edges = Db::name('flow_edge')->where('store_id', $this->storeId)->where('template_id', $id)->select()->toArray();
        $errors = TemplateValidator::validate(
            array_map([$this, 'formatNode'], $nodes),
            array_map([$this, 'formatEdge'], $edges),
            $this->allowedVariables((string)$template['biz_type'])
        );
        return ['passed' => empty($errors), 'errors' => $errors];
    }

    /**
     * 发布前指派检查
     *
     * 明确配置的角色/角色名/指定人/部门必须在当前租户能解析到人；
     * 业务字段/上级属于运行时解析，无法预检，标记为需确认；
     * 主规则无人、兜底有人，标记为需确认。
     */
    public function assignmentCheck(int $id): array
    {
        $template = $this->findTemplate($id);
        $nodes = Db::name('flow_node')
            ->where('store_id', $this->storeId)
            ->where('template_id', $id)
            ->order('sort', 'asc')->order('id', 'asc')
            ->select()->toArray();

        $resolver = new OrgResolver($this->storeId);
        $context = ['variables' => [], 'snapshot' => [], 'initiator_id' => 0];

        $errors = [];
        $needsConfirm = false;
        $rows = [];

        foreach ($nodes as $node) {
            $type = (string)($node['type'] ?? '');
            if (!isset(Node::TASK_TYPES[$type])) continue;

            $rules = json_decode((string)($node['assignee_rules'] ?? '[]'), true);
            if (!is_array($rules)) $rules = [];
            $config = json_decode((string)($node['config'] ?? ''), true);
            if (!is_array($config)) $config = [];
            $fallback = is_array($config['fallback_rules'] ?? null) ? $config['fallback_rules'] : [];

            $types = array_column($rules, 'type');
            $hasCreator = in_array('creator', $types, true);
            $isDynamic = (bool)array_intersect($types, ['field', 'manager']);
            $concrete = array_values(array_filter($rules, function ($rule) {
                return in_array((string)($rule['type'] ?? ''), ['role', 'role_name', 'user', 'dept'], true);
            }));

            $users = !empty($concrete) ? $resolver->resolve($concrete, $context) : [];
            $names = array_column($users, 'name');
            $status = 'ok';
            $message = '';

            if (!empty($names)) {
                if ($isDynamic) {
                    $status = 'dynamic';
                    $needsConfirm = true;
                    $message = '还包含业务字段，运行时按业务单解析';
                }
            } elseif ($hasCreator) {
                $names = ['发起人'];
            } else {
                $fallbackUsers = !empty($fallback) ? $resolver->resolve($fallback, $context) : [];
                $fallbackNames = array_column($fallbackUsers, 'name');
                $fallbackHasCreator = in_array('creator', array_column($fallback, 'type'), true);

                if (!empty($fallbackNames)) {
                    $status = 'fallback';
                    $needsConfirm = true;
                    $names = $fallbackNames;
                    $message = '主指派未匹配，将使用兜底规则';
                } elseif ($fallbackHasCreator) {
                    $names = ['发起人'];
                    $message = '主指派未匹配，将回落到发起人';
                } elseif ($isDynamic) {
                    $status = 'dynamic';
                    $needsConfirm = true;
                    $message = '运行时按业务字段解析，发布前请确认业务单上已填写对应人员';
                } else {
                    $status = 'error';
                    $errors[] = "节点【{$node['name']}】没有可用指派（" . $this->ruleText($rules) . "）";
                    $message = '当前租户匹配不到任何人';
                }
            }

            $rows[] = [
                'node_key' => (string)$node['node_key'],
                'name' => (string)$node['name'],
                'rule_text' => $this->ruleText($rules),
                'status' => $status,
                'assignees' => $names,
                'message' => $message,
            ];
        }

        return [
            'passed' => empty($errors),
            'needs_confirm' => $needsConfirm,
            'errors' => $errors,
            'nodes' => $rows,
        ];
    }

    private function ruleText(array $rules): string
    {
        $labels = [];
        foreach ($rules as $rule) {
            $type = (string)($rule['type'] ?? '');
            switch ($type) {
                case 'role':
                    $roleId = (int)($rule['id'] ?? 0);
                    $name = Db::name('store_role')->where('store_id', $this->storeId)
                        ->where('role_id', $roleId)->value('role_name');
                    $count = $roleId > 0 ? Db::name('store_user_role')
                        ->where('store_id', $this->storeId)->where('role_id', $roleId)->count() : 0;
                    $labels[] = '角色：' . ($name ?: ('#' . $roleId)) . '（' . $count . '人）';
                    break;
                case 'role_name':
                    $roleName = (string)($rule['name'] ?? '');
                    $roleId = $roleName !== '' ? Db::name('store_role')->where('store_id', $this->storeId)
                        ->where('role_name', $roleName)->value('role_id') : 0;
                    if (!$roleId) {
                        $labels[] = '角色名：' . $roleName . '（角色不存在）';
                    } else {
                        $count = Db::name('store_user_role')->where('store_id', $this->storeId)
                            ->where('role_id', $roleId)->count();
                        $labels[] = '角色名：' . $roleName . '（' . $count . '人）';
                    }
                    break;
                case 'user':
                    $name = Db::name('store_user')->where('store_id', $this->storeId)
                        ->where('store_user_id', (int)($rule['id'] ?? 0))->value('real_name');
                    $labels[] = '指定人：' . ($name ?: ('#' . (int)($rule['id'] ?? 0)));
                    break;
                case 'dept':
                    $name = Db::name('store_department')->where('store_id', $this->storeId)
                        ->where('id', (int)($rule['id'] ?? 0))->value('department_name');
                    $labels[] = '部门：' . ($name ?: ('#' . (int)($rule['id'] ?? 0)));
                    break;
                case 'field':
                    $labels[] = '业务字段：' . (string)($rule['field'] ?? '');
                    break;
                case 'creator':
                    $labels[] = '发起人';
                    break;
                case 'manager':
                    $labels[] = '上级：' . (string)($rule['of_field'] ?? '');
                    break;
            }
        }
        return $labels ? implode(' + ', $labels) : '未配置';
    }

    public function publish(int $id, bool $asDefault, int $actorId, bool $confirmAssignment = false, bool $system = false): array
    {
        $template = $this->findTemplate($id);
        if ((string)$template['status'] === Template::STATUS_PUBLISHED) {
            throw new FlowException('该版本已发布');
        }

        $check = $this->validateTemplate($id);
        if (!$check['passed']) {
            throw new FlowException('模板校验未通过：' . implode('；', $check['errors']));
        }

        // 发布前指派检查：客户必须确认节点指派，避免运行时静默回落到发起人
        if (!$system) {
            $assign = $this->assignmentCheck($id);
            if (!empty($assign['errors'])) {
                throw new FlowException('指派未确认：' . implode('；', array_slice($assign['errors'], 0, 5)));
            }
            if ($assign['needs_confirm'] && !$confirmAssignment) {
                throw new FlowException('发布前请确认各节点指派后再发布');
            }
        }

        $bizType = (string)$template['biz_type'];
        $code = (string)$template['code'];

        Db::startTrans();
        try {
            Db::name('flow_template')
                ->where('store_id', $this->storeId)
                ->where('biz_type', $bizType)
                ->where('code', $code)
                ->where('status', Template::STATUS_PUBLISHED)
                ->where('id', '<>', $id)
                ->update(['status' => Template::STATUS_ARCHIVED, 'is_default' => 0, 'update_time' => time()]);

            $hasDefault = (int)Db::name('flow_template')
                ->where('store_id', $this->storeId)
                ->where('biz_type', $bizType)
                ->where('status', Template::STATUS_PUBLISHED)
                ->where('is_default', 1)
                ->where('id', '<>', $id)
                ->count() > 0;

            $setDefault = $asDefault || !$hasDefault;
            if ($setDefault) {
                Db::name('flow_template')
                    ->where('store_id', $this->storeId)
                    ->where('biz_type', $bizType)
                    ->where('id', '<>', $id)
                    ->update(['is_default' => 0, 'update_time' => time()]);
            }

            Db::name('flow_template')->where('id', $id)->where('store_id', $this->storeId)->update([
                'status' => Template::STATUS_PUBLISHED,
                'is_default' => $setDefault ? 1 : 0,
                'published_by' => $actorId,
                'published_at' => time(),
                'update_time' => time(),
            ]);

            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            throw $e;
        }

        return $this->detail($id);
    }

    public function archive(int $id): array
    {
        $template = $this->findTemplate($id);
        if ((string)$template['status'] !== Template::STATUS_PUBLISHED) {
            throw new FlowException('只有已发布模板可以归档');
        }
        Db::name('flow_template')->where('id', $id)->where('store_id', $this->storeId)->update([
            'status' => Template::STATUS_ARCHIVED,
            'is_default' => 0,
            'update_time' => time(),
        ]);
        return ['id' => $id];
    }

    public function setDefault(int $id): array
    {
        $template = $this->findTemplate($id);
        if ((string)$template['status'] !== Template::STATUS_PUBLISHED) {
            throw new FlowException('只有已发布模板可以设为默认');
        }
        Db::startTrans();
        try {
            Db::name('flow_template')
                ->where('store_id', $this->storeId)
                ->where('biz_type', (string)$template['biz_type'])
                ->update(['is_default' => 0, 'update_time' => time()]);
            Db::name('flow_template')->where('id', $id)->where('store_id', $this->storeId)
                ->update(['is_default' => 1, 'update_time' => time()]);
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            throw $e;
        }
        return ['id' => $id];
    }

    public function copyVersion(int $id, int $actorId): array
    {
        $template = $this->findTemplate($id);
        $code = (string)$template['code'];

        $maxVersion = (int)Db::name('flow_template')
            ->where('store_id', $this->storeId)
            ->where('code', $code)
            ->max('version');

        $nodes = Db::name('flow_node')->where('store_id', $this->storeId)->where('template_id', $id)->select()->toArray();
        $edges = Db::name('flow_edge')->where('store_id', $this->storeId)->where('template_id', $id)->select()->toArray();

        Db::startTrans();
        try {
            $newId = (int)Db::name('flow_template')->insertGetId([
                'store_id' => $this->storeId,
                'code' => $code,
                'name' => (string)$template['name'],
                'biz_type' => (string)$template['biz_type'],
                'version' => $maxVersion + 1,
                'status' => Template::STATUS_DRAFT,
                'is_default' => 0,
                'graph' => $template['graph'] ?? null,
                'description' => (string)($template['description'] ?? ''),
                'created_by' => $actorId,
                'create_time' => time(),
                'update_time' => time(),
                'is_delete' => 0,
            ]);

            foreach ($nodes as $node) {
                unset($node['id']);
                $node['template_id'] = $newId;
                Db::name('flow_node')->insert($node);
            }
            foreach ($edges as $edge) {
                unset($edge['id']);
                $edge['template_id'] = $newId;
                Db::name('flow_edge')->insert($edge);
            }

            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            throw $e;
        }

        return $this->detail($newId);
    }

    public function remove(int $id): array
    {
        $template = $this->findTemplate($id);
        if ((string)$template['status'] === Template::STATUS_PUBLISHED) {
            throw new FlowException('已发布模板不能删除，请先归档');
        }
        Db::name('flow_template')->where('id', $id)->where('store_id', $this->storeId)
            ->update(['is_delete' => 1, 'update_time' => time()]);
        return ['id' => $id];
    }

    /**
     * 启动流程时解析模板：优先指定 code，其次默认模板
     */
    public function resolveForStart(string $bizType, string $code = ''): array
    {
        $query = Db::name('flow_template')
            ->where('store_id', $this->storeId)
            ->where('biz_type', $bizType)
            ->where('status', Template::STATUS_PUBLISHED)
            ->where('is_delete', 0);

        if ($code !== '') {
            $query->where('code', $code);
        } else {
            $query->where('is_default', 1);
        }

        $template = $query->order('version', 'desc')->find();
        if (!$template) {
            // 首次使用：让业务方生成默认模板
            FlowRegistry::ensureDefaultTemplate($this->storeId, $bizType);
            $template = Db::name('flow_template')
                ->where('store_id', $this->storeId)
                ->where('biz_type', $bizType)
                ->where('status', Template::STATUS_PUBLISHED)
                ->where('is_delete', 0)
                ->when($code !== '', function ($q) use ($code) { $q->where('code', $code); })
                ->when($code === '', function ($q) { $q->where('is_default', 1); })
                ->order('version', 'desc')
                ->find();
        }

        if (!$template) {
            throw new FlowException('该业务还没有已发布的流程模板，请先在「流程模板」中配置并发布');
        }
        return $template;
    }

    /**
     * 加载模板定义（节点 map + 出边）
     */
    public function loadDefinition(int $templateId): array
    {
        $nodes = Db::name('flow_node')
            ->where('store_id', $this->storeId)
            ->where('template_id', $templateId)
            ->order('sort', 'asc')->order('id', 'asc')
            ->select()->toArray();
        $edges = Db::name('flow_edge')
            ->where('store_id', $this->storeId)
            ->where('template_id', $templateId)
            ->order('sort', 'asc')->order('id', 'asc')
            ->select()->toArray();

        $nodeMap = [];
        foreach ($nodes as $node) {
            $node = $this->formatNode($node);
            $nodeMap[(string)$node['node_key']] = $node;
        }
        $outgoing = [];
        foreach ($edges as $edge) {
            $edge = $this->formatEdge($edge);
            $outgoing[(string)$edge['from_key']][] = $edge;
        }

        return ['nodes' => $nodeMap, 'outgoing' => $outgoing, 'edges' => $edges];
    }

    // ── 内部 ──

    private function findTemplate(int $id): array
    {
        $template = Db::name('flow_template')
            ->where('store_id', $this->storeId)
            ->where('id', $id)
            ->where('is_delete', 0)
            ->find();
        if (!$template) throw new FlowException('流程模板不存在');
        return $template;
    }

    private function cleanNode(array $node): array
    {
        $data = [];
        foreach (self::NODE_FIELDS as $field) {
            if (!array_key_exists($field, $node)) continue;
            $value = $node[$field];
            if (in_array($field, self::JSON_NODE_FIELDS, true)) {
                $data[$field] = $this->encodeJson($value);
            } elseif (in_array($field, ['position_x', 'position_y', 'complete_ratio', 'sla_hours', 'remind_before_hours', 'sort'], true)) {
                $data[$field] = (int)$value;
            } else {
                $data[$field] = is_string($value) ? $value : (string)$value;
            }
        }
        $data['node_key'] = (string)($node['node_key'] ?? '');
        $data['name'] = (string)($node['name'] ?? $data['node_key']);
        $data['type'] = (string)($node['type'] ?? Node::TYPE_TASK);
        return $data;
    }

    private function cleanEdge(array $edge): array
    {
        $data = [];
        foreach (self::EDGE_FIELDS as $field) {
            if (!array_key_exists($field, $edge)) continue;
            $value = $edge[$field];
            if ($field === 'condition_json') {
                $data[$field] = $this->encodeJson($value);
            } elseif (in_array($field, ['is_default', 'sort'], true)) {
                $data[$field] = (int)$value;
            } else {
                $data[$field] = (string)$value;
            }
        }
        $data['is_default'] = (int)($edge['is_default'] ?? 0);
        $data['sort'] = (int)($edge['sort'] ?? 100);
        return $data;
    }

    private function insertNode(int $templateId, array $node): void
    {
        $data = $node;
        foreach (self::JSON_NODE_FIELDS as $field) {
            if (isset($data[$field]) && is_array($data[$field])) {
                $data[$field] = json_encode($data[$field], JSON_UNESCAPED_UNICODE);
            }
        }
        $data['store_id'] = $this->storeId;
        $data['template_id'] = $templateId;
        if (!isset($data['sort'])) $data['sort'] = 100;
        Db::name('flow_node')->insert($data);
    }

    private function insertEdge(int $templateId, array $edge): void
    {
        $data = $edge;
        if (isset($data['condition_json']) && is_array($data['condition_json'])) {
            $data['condition_json'] = json_encode($data['condition_json'], JSON_UNESCAPED_UNICODE);
        }
        $data['store_id'] = $this->storeId;
        $data['template_id'] = $templateId;
        Db::name('flow_edge')->insert($data);
    }

    private function saveGraph(int $templateId, array $nodes, array $edges, array $extra = []): void
    {
        $update = [
            'graph' => json_encode(['nodes' => $nodes, 'edges' => $edges], JSON_UNESCAPED_UNICODE),
            'update_time' => time(),
        ];
        if (!empty($extra['name'])) $update['name'] = (string)$extra['name'];
        if (array_key_exists('description', $extra) && $extra['description'] !== null) {
            $update['description'] = (string)$extra['description'];
        }
        Db::name('flow_template')->where('id', $templateId)->where('store_id', $this->storeId)->update($update);
    }

    private function encodeJson($value): string
    {
        if ($value === null || $value === '') return '';
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? json_encode($decoded, JSON_UNESCAPED_UNICODE) : '';
        }
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    private function formatTemplate(array $row, int $nodeCount): array
    {
        $row['status_text'] = Template::statusMap()[(string)$row['status']] ?? (string)$row['status'];
        $row['biz_label'] = FlowRegistry::has((string)$row['biz_type'])
            ? FlowRegistry::label((string)$row['biz_type'])
            : (string)$row['biz_type'];
        $row['node_count'] = $nodeCount;
        $row['is_default'] = (int)$row['is_default'];
        return $row;
    }

    private function formatNode(array $node): array
    {
        foreach (self::JSON_NODE_FIELDS as $field) {
            $raw = $node[$field] ?? '';
            $decoded = is_string($raw) && $raw !== '' ? json_decode($raw, true) : $raw;
            $node[$field] = is_array($decoded) ? $decoded : ($field === 'assignee_rules' || $field === 'actions' ? [] : null);
        }
        $node['type_text'] = Node::typeMap()[(string)$node['type']] ?? (string)$node['type'];
        return $node;
    }

    private function formatEdge(array $edge): array
    {
        $raw = $edge['condition_json'] ?? '';
        $decoded = is_string($raw) && $raw !== '' ? json_decode($raw, true) : $raw;
        $edge['condition_json'] = is_array($decoded) ? $decoded : null;
        $edge['is_default'] = (int)($edge['is_default'] ?? 0);
        return $edge;
    }

    private function allowedVariables(string $bizType): array
    {
        return FlowRegistry::has($bizType) ? FlowRegistry::allowedVariables($bizType) : FlowRegistry::SYSTEM_VARIABLES;
    }

    private function allowedVariableOptions(string $bizType): array
    {
        if (FlowRegistry::has($bizType)) {
            return FlowRegistry::allowedVariableOptions($bizType);
        }
        return array_map(fn($value) => [
            'value' => $value,
            'label' => FlowRegistry::SYSTEM_VARIABLE_LABELS[$value] ?? $value,
            'group' => '系统',
        ], FlowRegistry::SYSTEM_VARIABLES);
    }
}
