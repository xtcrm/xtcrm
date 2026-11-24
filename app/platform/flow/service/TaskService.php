<?php

declare(strict_types=1);

namespace app\platform\flow\service;

use app\platform\flow\FlowException;
use app\platform\flow\FlowRegistry;
use app\platform\flow\model\Node;
use app\platform\flow\model\Task;
use app\platform\flow\model\Template;
use app\platform\flow\model\Token;
use think\facade\Db;

/**
 * 待办与流程步骤查询
 */
class TaskService
{
    private int $storeId;
    private int $userId;
    private bool $isSuper;
    private array $userNames = [];

    public function __construct(int $storeId, int $userId = 0, bool $isSuper = false)
    {
        $this->storeId = $storeId;
        $this->userId = $userId;
        $this->isSuper = $isSuper;
    }

    /**
     * 待办列表
     * @param string $scope pending|done|created|all
     */
    public function getList(string $scope = 'pending', array $where = [], int $page = 1, int $limit = 20): array
    {
        $query = Db::name('flow_task')->where('store_id', $this->storeId);

        $status = (string)($where['status'] ?? '');
        switch ($scope) {
            case 'pending':
                $query->where('assignee_id', $this->userId)->whereIn('status', Task::OPEN_STATUSES);
                break;
            case 'done':
                $query->where('completed_by', $this->userId)
                    ->whereIn('status', [Task::STATUS_DONE, Task::STATUS_REJECTED]);
                break;
            case 'created':
                $instanceIds = Db::name('flow_instance')
                    ->where('store_id', $this->storeId)
                    ->where('started_by', $this->userId)
                    ->column('id');
                if (empty($instanceIds)) return ['list' => [], 'total' => 0, 'page' => $page, 'limit' => $limit];
                $query->whereIn('instance_id', $instanceIds);
                break;
            case 'all':
                if (!$this->isSuper) {
                    $query->where('assignee_id', $this->userId)->whereIn('status', Task::OPEN_STATUSES);
                }
                break;
            default:
                break;
        }

        if ($status !== '') $query->where('status', $status);
        if ($scope !== 'pending' && $scope !== 'done' && $status === '') {
            // 全部/我发起：默认展示所有状态
        }

        $keyword = trim((string)($where['keyword'] ?? ''));
        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('node_name', 'like', "%{$keyword}%")
                  ->whereOr('assignee_name', 'like', "%{$keyword}%")
                  ->whereOr('comment', 'like', "%{$keyword}%");
            });
        }

        $bizType = trim((string)($where['biz_type'] ?? ''));
        if ($bizType !== '') {
            $bizInstanceIds = Db::name('flow_instance')
                ->where('store_id', $this->storeId)
                ->where('biz_type', $bizType)
                ->column('id');
            if (empty($bizInstanceIds)) {
                return ['list' => [], 'total' => 0, 'page' => $page, 'limit' => $limit];
            }
            $query->whereIn('instance_id', $bizInstanceIds);
        }

        $total = (clone $query)->count();
        $rows = $query->order('id', 'desc')->page($page, $limit)->select()->toArray();

        return [
            'list' => $this->enrich($rows),
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ];
    }

    public function count(): array
    {
        $pending = Db::name('flow_task')
            ->where('store_id', $this->storeId)
            ->where('assignee_id', $this->userId)
            ->whereIn('status', Task::OPEN_STATUSES)
            ->count();

        $overdue = Db::name('flow_task')
            ->where('store_id', $this->storeId)
            ->where('assignee_id', $this->userId)
            ->whereIn('status', Task::OPEN_STATUSES)
            ->where('due_at', '>', 0)
            ->where('due_at', '<', time())
            ->count();

        $rows = Db::name('flow_task')->alias('t')
            ->join('flow_instance i', 'i.id = t.instance_id AND i.store_id = t.store_id')
            ->where('t.store_id', $this->storeId)
            ->where('t.assignee_id', $this->userId)
            ->whereIn('t.status', Task::OPEN_STATUSES)
            ->group('i.biz_type')
            ->field('i.biz_type, COUNT(*) AS cnt')
            ->select()->toArray();
        $countMap = [];
        foreach ($rows as $row) {
            $countMap[(string)$row['biz_type']] = (int)$row['cnt'];
        }
        $published = Db::name('flow_template')
            ->where('store_id', $this->storeId)
            ->where('status', 'published')
            ->where('is_delete', 0)
            ->column('biz_type');
        $published = array_flip(array_map('strval', $published));

        $bizTypes = [];
        foreach (FlowRegistry::options((int)$this->storeId) as $opt) {
            $value = (string)$opt['value'];
            // 只展示本租户配了模板的业务，或者已有待办的业务
            if (!isset($published[$value]) && empty($countMap[$value])) continue;
            $bizTypes[] = [
                'value' => $value,
                'label' => (string)$opt['label'],
                'count' => $countMap[$value] ?? 0,
            ];
        }

        return ['pending' => $pending, 'overdue' => $overdue, 'biz_types' => $bizTypes];
    }

    public function detail(int $taskId): array
    {
        $task = Db::name('flow_task')
            ->where('store_id', $this->storeId)
            ->where('id', $taskId)
            ->find();
        if (!$task) {
            throw new \app\platform\flow\FlowException('待办不存在');
        }

        $instance = Db::name('flow_instance')
            ->where('store_id', $this->storeId)
            ->where('id', (int)$task['instance_id'])
            ->find();
        if (!$instance) {
            throw new \app\platform\flow\FlowException('流程实例不存在');
        }

        $allowed = $this->isSuper
            || (int)$task['assignee_id'] === $this->userId
            || (int)$task['completed_by'] === $this->userId
            || (int)$instance['started_by'] === $this->userId;
        if (!$allowed) {
            throw new \FlowException('无权查看该待办');
        }

        $template = Db::name('flow_template')->where('id', (int)$instance['template_id'])->find();
        $node = Db::name('flow_node')
            ->where('store_id', $this->storeId)
            ->where('template_id', (int)$instance['template_id'])
            ->where('node_key', (string)$task['node_key'])
            ->find();

        $nodeData = $node ? $this->decodeNode($node) : [];
        $actions = $nodeData['actions'] ?? [];
        if (!is_array($actions) || empty($actions)) {
            $actions = isset(Node::TASK_TYPES[(string)($nodeData['type'] ?? 'task')])
                ? [$this->defaultAction((string)$nodeData['type'])]
                : ['approve'];
            $actions[] = 'reject';
        }

        $config = $nodeData['config'] ?? [];
        if (!is_array($config)) $config = [];

        return [
            'task' => $this->formatTask($task),
            'instance' => $this->formatInstance($instance, $template),
            'node' => $nodeData,
            'actions' => array_values(array_unique($actions)),
            'allow_transfer' => ($config['allow_transfer'] ?? true) !== false,
            'allow_add_sign' => ($config['allow_add_sign'] ?? true) !== false,
            'form_schema' => $nodeData['form_schema'] ?? [],
            'logs' => $this->logs((int)$instance['id'], 100),
            'biz_url' => $this->bizUrl($instance),
            'can_handle' => $this->canHandle($task),
        ];
    }

    /**
     * 业务单据的流程步骤
     */
    public function steps(string $bizType, int $bizId): array
    {
        $instance = Db::name('flow_instance')
            ->where('store_id', $this->storeId)
            ->where('biz_type', $bizType)
            ->where('biz_id', $bizId)
            ->order('id', 'desc')
            ->find();

        if (!$instance) {
            return ['instance' => null, 'steps' => [], 'my_tasks' => []];
        }

        $template = Db::name('flow_template')->where('id', (int)$instance['template_id'])->find();
        $nodes = Db::name('flow_node')
            ->where('store_id', $this->storeId)
            ->where('template_id', (int)$instance['template_id'])
            ->order('sort', 'asc')->order('id', 'asc')
            ->select()->toArray();

        $logs = Db::name('flow_log')
            ->where('store_id', $this->storeId)
            ->where('instance_id', (int)$instance['id'])
            ->order('id', 'asc')
            ->select()->toArray();

        $doneKeys = [];
        $skippedKeys = [];
        foreach ($logs as $log) {
            if (in_array((string)$log['action'], ['node_complete', 'end'], true)) {
                $doneKeys[(string)$log['node_key']] = true;
            }
            if ((string)$log['action'] === 'skip') {
                $skippedKeys[(string)$log['node_key']] = true;
            }
        }

        $currentKeys = Db::name('flow_token')
            ->where('store_id', $this->storeId)
            ->where('instance_id', (int)$instance['id'])
            ->whereIn('status', [Token::STATUS_ACTIVE, Token::STATUS_WAITING])
            ->column('node_key');
        $currentKeys = array_flip(array_map('strval', $currentKeys));

        $openTasks = Db::name('flow_task')
            ->where('store_id', $this->storeId)
            ->where('instance_id', (int)$instance['id'])
            ->whereIn('status', Task::OPEN_STATUSES)
            ->select()->toArray();
        $tasksByNode = [];
        foreach ($openTasks as $task) {
            $tasksByNode[(string)$task['node_key']][] = $task;
        }

        $myTasks = [];
        $steps = [];
        foreach ($nodes as $node) {
            $key = (string)$node['node_key'];
            $type = (string)$node['type'];
            if (!isset(Node::TASK_TYPES[$type]) && !in_array($type, [Node::TYPE_START, Node::TYPE_END, Node::TYPE_GATEWAY], true)) {
                continue;
            }
            $status = 'pending';
            if (isset($doneKeys[$key])) $status = 'done';
            elseif (isset($skippedKeys[$key])) $status = 'skipped';
            elseif (isset($currentKeys[$key])) $status = 'current';

            $assignees = [];
            foreach ($tasksByNode[$key] ?? [] as $task) {
                $assignees[] = (string)$task['assignee_name'];
                if ((int)$task['assignee_id'] === $this->userId) {
                    $myTasks[] = $this->formatTask($task);
                }
            }

            $steps[] = [
                'node_key' => $key,
                'name' => (string)$node['name'],
                'type' => $type,
                'type_text' => Node::typeMap()[$type] ?? $type,
                'status' => $status,
                'assignees' => array_values(array_unique(array_filter($assignees))),
            ];
        }

        return [
            'instance' => $this->formatInstance($instance, $template),
            'steps' => $steps,
            'my_tasks' => $myTasks,
        ];
    }

    public function logs(int $instanceId, int $limit = 100): array
    {
        $rows = Db::name('flow_log')
            ->where('store_id', $this->storeId)
            ->where('instance_id', $instanceId)
            ->order('id', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();

        $map = [
            'start' => '启动流程',
            'enter' => '进入节点',
            'skip' => '跳过节点',
            'approve' => '通过',
            'complete' => '完成',
            'sign' => '签字确认',
            'pay' => '确认收款',
            'reject' => '驳回',
            'transfer' => '转交',
            'add_sign' => '加签',
            'merge' => '分支合并',
            'node_complete' => '节点完成',
            'reject_back' => '驳回退回',
            'reject_end' => '驳回终止',
            'terminate' => '终止流程',
            'instance_complete' => '流程完成',
            'dead_end' => '流程异常结束',
        ];

        $list = [];
        foreach ($rows as $row) {
            $detail = json_decode((string)($row['detail'] ?? ''), true);
            $list[] = [
                'id' => (int)$row['id'],
                'node_key' => (string)$row['node_key'],
                'node_name' => (string)($detail['node_name'] ?? ''),
                'action' => (string)$row['action'],
                'action_text' => $map[(string)$row['action']] ?? (string)$row['action'],
                'operator_id' => (int)$row['operator_id'],
                'operator_name' => (string)$row['operator_name'],
                'comment' => (string)$row['comment'],
                'create_time' => (int)$row['create_time'],
                'create_time_text' => date('Y-m-d H:i', (int)$row['create_time']),
            ];
        }
        return $list;
    }

    // ── 内部 ──

    private function enrich(array $rows): array
    {
        if (empty($rows)) return [];

        $instanceIds = array_values(array_unique(array_column($rows, 'instance_id')));
        $instances = Db::name('flow_instance')
            ->where('store_id', $this->storeId)
            ->whereIn('id', $instanceIds)
            ->select()->toArray();
        $instanceMap = [];
        foreach ($instances as $instance) $instanceMap[(int)$instance['id']] = $instance;

        $templateIds = array_values(array_unique(array_column($instances, 'template_id')));
        $templates = empty($templateIds) ? [] : Db::name('flow_template')->whereIn('id', $templateIds)->select()->toArray();
        $templateMap = [];
        foreach ($templates as $template) $templateMap[(int)$template['id']] = $template;

        $list = [];
        foreach ($rows as $row) {
            $task = $this->formatTask($row);
            $instance = $instanceMap[(int)$row['instance_id']] ?? null;
            if ($instance) {
                $task['instance'] = $this->formatInstance($instance, $templateMap[(int)$instance['template_id']] ?? null);
                $task['biz_url'] = $this->bizUrl($instance);
            }
            $list[] = $task;
        }
        return $list;
    }

    private function formatTask(array $row): array
    {
        $row['status_text'] = Task::statusMap()[(string)$row['status']] ?? (string)$row['status'];
        $row['due_at_text'] = !empty($row['due_at']) ? date('Y-m-d H:i', (int)$row['due_at']) : '';
        $row['overdue'] = !empty($row['due_at']) && (int)$row['due_at'] < time()
            && in_array((string)$row['status'], Task::OPEN_STATUSES, true);
        $row['is_mine'] = (int)($row['assignee_id'] ?? 0) === $this->userId;
        return $row;
    }

    private function formatInstance(array $instance, ?array $template): array
    {
        $snapshot = json_decode((string)($instance['business_snapshot'] ?? ''), true);
        if (!is_array($snapshot)) $snapshot = [];

        $bizType = (string)$instance['biz_type'];
        $config = FlowRegistry::has($bizType) ? FlowRegistry::get($bizType) : [];
        $titleField = (string)($config['title_field'] ?? 'title');

        return [
            'id' => (int)$instance['id'],
            'biz_type' => $bizType,
            'biz_label' => FlowRegistry::has($bizType) ? FlowRegistry::label($bizType) : $bizType,
            'biz_id' => (int)$instance['biz_id'],
            'title' => (string)($snapshot[$titleField] ?? $snapshot['title'] ?? ''),
            'snapshot' => $snapshot,
            'status' => (string)$instance['status'],
            'status_text' => \app\platform\flow\model\Instance::statusMap()[(string)$instance['status']] ?? (string)$instance['status'],
            'result' => (string)$instance['result'],
            'template_id' => (int)$instance['template_id'],
            'template_name' => (string)($template['name'] ?? ''),
            'template_version' => (int)$instance['template_version'],
            'started_by' => (int)$instance['started_by'],
            'started_at' => (int)$instance['started_at'],
            'started_at_text' => !empty($instance['started_at']) ? date('Y-m-d H:i', (int)$instance['started_at']) : '',
            'finished_at_text' => !empty($instance['finished_at']) ? date('Y-m-d H:i', (int)$instance['finished_at']) : '',
        ];
    }

    private function decodeNode(array $node): array
    {
        foreach (['assignee_rules', 'actions', 'form_schema', 'condition_json', 'reject_policy', 'sync_fields', 'escalate_to', 'config'] as $field) {
            $raw = $node[$field] ?? '';
            $decoded = is_string($raw) && $raw !== '' ? json_decode($raw, true) : $raw;
            $node[$field] = is_array($decoded) ? $decoded : null;
        }
        return $node;
    }

    private function defaultAction(string $type): string
    {
        return [
            Node::TYPE_SIGN => 'sign',
            Node::TYPE_PAYMENT => 'pay',
            Node::TYPE_APPROVAL => 'approve',
        ][$type] ?? 'complete';
    }

    private function bizUrl(array $instance): string
    {
        $bizType = (string)$instance['biz_type'];
        if (!FlowRegistry::has($bizType)) return '';
        $config = FlowRegistry::get($bizType);
        $route = (string)($config['detail_route'] ?? '');
        if ($route === '') return '';
        return $route . '?id=' . (int)$instance['biz_id'];
    }

    private function canHandle(array $task): bool
    {
        return (int)$task['assignee_id'] === $this->userId;
    }

    private function userName(int $userId): string
    {
        if ($userId <= 0) return '';
        if (!array_key_exists($userId, $this->userNames)) {
            $name = Db::name('store_user')
                ->where('store_id', $this->storeId)
                ->where('store_user_id', $userId)
                ->value('real_name');
            $this->userNames[$userId] = (string)($name ?: '');
        }
        return $this->userNames[$userId];
    }
}
