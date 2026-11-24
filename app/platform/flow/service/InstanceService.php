<?php

declare(strict_types=1);

namespace app\platform\flow\service;

use app\platform\flow\FlowException;
use app\platform\flow\FlowRegistry;
use app\platform\flow\model\Instance;
use app\platform\flow\model\Node;
use app\platform\flow\model\Task;
use app\platform\flow\model\Token;
use think\facade\Db;

/**
 * 流程实例查询与操作
 */
class InstanceService
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

    public function getList(array $where = [], int $page = 1, int $limit = 20): array
    {
        $query = Db::name('flow_instance')->where('store_id', $this->storeId);

        foreach (['biz_type', 'status', 'biz_id'] as $key) {
            $value = $where[$key] ?? '';
            if ($value !== '' && $value !== null) $query->where($key, $value);
        }
        if (!empty($where['mine'])) {
            $query->where('started_by', $this->userId);
        } elseif (!empty($where['started_by'])) {
            $query->where('started_by', (int)$where['started_by']);
        }

        $total = (clone $query)->count();
        $rows = $query->order('id', 'desc')->page($page, $limit)->select()->toArray();

        $templateIds = array_values(array_unique(array_column($rows, 'template_id')));
        $templates = empty($templateIds) ? [] : Db::name('flow_template')->whereIn('id', $templateIds)->select()->toArray();
        $templateMap = [];
        foreach ($templates as $template) $templateMap[(int)$template['id']] = $template;

        $list = [];
        foreach ($rows as $row) {
            $list[] = $this->formatInstance($row, $templateMap[(int)$row['template_id']] ?? null);
        }

        return ['list' => $list, 'total' => $total, 'page' => $page, 'limit' => $limit];
    }

    public function detail(int $instanceId, bool $canViewAll = true): array
    {
        $instance = Db::name('flow_instance')
            ->where('store_id', $this->storeId)
            ->where('id', $instanceId)
            ->find();
        if (!$instance) throw new FlowException('流程实例不存在');
        if (!$canViewAll && !$this->isSuper && (int)$instance['started_by'] !== $this->userId) {
            throw new FlowException('无权查看该流程');
        }

        $template = Db::name('flow_template')->where('id', (int)$instance['template_id'])->find();
        $nodes = Db::name('flow_node')
            ->where('store_id', $this->storeId)
            ->where('template_id', (int)$instance['template_id'])
            ->order('sort', 'asc')->order('id', 'asc')
            ->select()->toArray();
        $edges = Db::name('flow_edge')
            ->where('store_id', $this->storeId)
            ->where('template_id', (int)$instance['template_id'])
            ->order('sort', 'asc')->order('id', 'asc')
            ->select()->toArray();
        $tokens = Db::name('flow_token')
            ->where('store_id', $this->storeId)
            ->where('instance_id', $instanceId)
            ->order('id', 'asc')->select()->toArray();
        $tasks = Db::name('flow_task')
            ->where('store_id', $this->storeId)
            ->where('instance_id', $instanceId)
            ->order('id', 'asc')->select()->toArray();

        $logs = (new TaskService($this->storeId, $this->userId))->logs($instanceId, 200);
        $steps = (new TaskService($this->storeId, $this->userId))
            ->steps((string)$instance['biz_type'], (int)$instance['biz_id']);

        return [
            'instance' => $this->formatInstance($instance, $template),
            'template' => $template ? [
                'id' => (int)$template['id'],
                'code' => (string)$template['code'],
                'name' => (string)$template['name'],
                'version' => (int)$template['version'],
            ] : null,
            'nodes' => array_map([$this, 'formatNode'], $nodes),
            'edges' => $edges,
            'tokens' => $tokens,
            'tasks' => array_map([$this, 'formatTask'], $tasks),
            'logs' => $logs,
            'steps' => $steps['steps'] ?? [],
            'variables' => json_decode((string)($instance['variables'] ?? ''), true) ?: [],
        ];
    }

    public function start(string $bizType, int $bizId, array $actor, string $templateCode = '', bool $force = false): array
    {
        return (new FlowEngine())->start($bizType, $bizId, $this->storeId, $actor, $templateCode, [], $force);
    }

    public function terminate(int $instanceId, string $reason, array $actor): array
    {
        $instance = Db::name('flow_instance')
            ->where('store_id', $this->storeId)
            ->where('id', $instanceId)
            ->find();
        if (!$instance) throw new FlowException('流程实例不存在');
        if (!$this->isSuper && (int)$instance['started_by'] !== $this->userId) {
            throw new FlowException('只有发起人或管理员可以终止流程');
        }
        return (new FlowEngine())->terminate($instanceId, $reason, $actor, $this->storeId);
    }

    private function formatInstance(array $row, ?array $template): array
    {
        $snapshot = json_decode((string)($row['business_snapshot'] ?? ''), true);
        if (!is_array($snapshot)) $snapshot = [];

        $bizType = (string)$row['biz_type'];
        $config = FlowRegistry::has($bizType) ? FlowRegistry::get($bizType) : [];
        $titleField = (string)($config['title_field'] ?? 'title');

        return [
            'id' => (int)$row['id'],
            'biz_type' => $bizType,
            'biz_label' => FlowRegistry::has($bizType) ? FlowRegistry::label($bizType) : $bizType,
            'biz_id' => (int)$row['biz_id'],
            'title' => (string)($snapshot[$titleField] ?? $snapshot['title'] ?? ''),
            'snapshot' => $snapshot,
            'status' => (string)$row['status'],
            'status_text' => Instance::statusMap()[(string)$row['status']] ?? (string)$row['status'],
            'result' => (string)$row['result'],
            'template_id' => (int)$row['template_id'],
            'template_name' => (string)($template['name'] ?? ''),
            'template_version' => (int)$row['template_version'],
            'started_by' => (int)$row['started_by'],
            'started_by_name' => $this->userName((int)$row['started_by']),
            'started_at' => (int)$row['started_at'],
            'started_at_text' => !empty($row['started_at']) ? date('Y-m-d H:i', (int)$row['started_at']) : '',
            'finished_at_text' => !empty($row['finished_at']) ? date('Y-m-d H:i', (int)$row['finished_at']) : '',
            'biz_url' => FlowRegistry::has($bizType) && !empty($config['detail_route'])
                ? $config['detail_route'] . '?id=' . (int)$row['biz_id']
                : '',
        ];
    }

    private function formatNode(array $node): array
    {
        $node['type_text'] = Node::typeMap()[(string)$node['type']] ?? (string)$node['type'];
        return $node;
    }

    private function formatTask(array $task): array
    {
        $task['status_text'] = Task::statusMap()[(string)$task['status']] ?? (string)$task['status'];
        return $task;
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
