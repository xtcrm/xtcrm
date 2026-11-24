<?php

declare(strict_types=1);

namespace app\platform\flow\service;

use app\platform\flow\FlowBizInterface;
use app\platform\flow\FlowException;
use app\platform\flow\FlowRegistry;
use app\platform\flow\model\Instance;
use app\platform\flow\model\Node;
use app\platform\flow\model\Task;
use app\platform\flow\model\Template;
use app\platform\flow\model\Token;
use cores\BaseModel;
use think\facade\Db;

/**
 * 流程引擎
 *
 * 只认识 template / node / edge / instance / token / task / log，
 * 业务状态全部通过 FlowBizInterface 回调，不直接写业务表。
 */
class FlowEngine
{
    private int $storeId = 0;
    private string $bizType = '';
    private array $bizConfig = [];
    private ?FlowBizInterface $hook = null;

    /** @var array{nodes:array,outgoing:array,edges:array} */
    private array $definition = ['nodes' => [], 'outgoing' => [], 'edges' => []];

    private array $variables = [];
    private array $snapshot = [];
    private array $actor = [];
    private array $instance = [];

    /** @var callable[] */
    private array $afterCommit = [];

    private const MAX_DEPTH = 200;

    // ==========================================================
    // 启动流程
    // ==========================================================

    public function start(
        string $bizType,
        int $bizId,
        int $storeId,
        array $actor = [],
        string $templateCode = '',
        array $extraVars = [],
        bool $force = false
    ): array {
        $this->storeId = $storeId;
        $this->bizType = $bizType;
        $this->bizConfig = FlowRegistry::get($bizType);
        $this->hook = FlowRegistry::hook($bizType);
        $this->actor = $actor;
        BaseModel::$storeId = $storeId;

        // 幂等：已有进行中的实例直接返回
        if (!$force) {
            $running = Db::name('flow_instance')
                ->where('store_id', $storeId)
                ->where('biz_type', $bizType)
                ->where('biz_id', $bizId)
                ->where('status', Instance::STATUS_RUNNING)
                ->order('id', 'desc')
                ->find();
            if ($running) {
                return $this->instanceResult($running);
            }
        }

        $template = (new TemplateService($storeId))->resolveForStart($bizType, $templateCode);
        $this->definition = (new TemplateService($storeId))->loadDefinition((int)$template['id']);

        $snapshot = $this->hook->snapshot($bizId, $storeId);
        $this->snapshot = is_array($snapshot) ? $snapshot : [];

        $vars = $this->hook->variables($bizId, $storeId);
        $vars = is_array($vars) ? $vars : [];
        $this->variables = array_merge($vars, $this->snapshot, $extraVars, [
            'biz_type' => $bizType,
            'biz_id' => $bizId,
            'initiator_id' => (int)($actor['id'] ?? 0),
            'initiator_dept_id' => (int)($actor['dept_id'] ?? 0),
            'now' => date('Y-m-d H:i:s'),
            'weekday' => (int)date('N'),
        ]);

        $instanceId = 0;
        Db::startTrans();
        try {
            $instanceId = (int)Db::name('flow_instance')->insertGetId([
                'store_id' => $storeId,
                'template_id' => (int)$template['id'],
                'template_version' => (int)$template['version'],
                'biz_type' => $bizType,
                'biz_id' => $bizId,
                'status' => Instance::STATUS_RUNNING,
                'result' => '',
                'variables' => json_encode($this->variables, JSON_UNESCAPED_UNICODE),
                'business_snapshot' => json_encode($this->snapshot, JSON_UNESCAPED_UNICODE),
                'started_by' => (int)($actor['id'] ?? 0),
                'started_at' => time(),
                'create_time' => time(),
                'update_time' => time(),
            ]);

            $this->instance = $this->loadInstance($instanceId);

            $startKey = $this->findStartNode();
            $tokenId = $this->createToken($instanceId, $startKey, 0, '');
            $token = $this->loadToken($tokenId);

            $this->writeLog($instanceId, $startKey, 'start', '启动流程', [
                'template' => $template['code'] ?? '',
                'version' => $template['version'] ?? 1,
            ]);

            $this->hook->onInstanceStart($this->instance);
            $this->enterNode($this->instance, $token, $startKey, 0);
            $this->finalizeIfDone($this->instance);

            Db::commit();
        } catch (\Throwable $e) {
            $this->afterCommit = [];
            Db::rollback();
            throw $e;
        }

        $this->runAfterCommit();
        return $this->instanceResult($this->loadInstance($instanceId));
    }

    // ==========================================================
    // 任务处理
    // ==========================================================

    /**
     * @param string $action approve|complete|sign|pay|reject
     */
    public function completeTask(
        int $taskId,
        string $action,
        string $comment,
        array $formData,
        array $actor,
        int $storeId
    ): array {
        $this->storeId = $storeId;
        $this->actor = $actor;
        BaseModel::$storeId = $storeId;

        Db::startTrans();
        try {
            $task = Db::name('flow_task')
                ->where('store_id', $storeId)
                ->where('id', $taskId)
                ->lock(true)
                ->find();
            if (!$task) throw new FlowException('待办不存在');

            if (!in_array((string)$task['status'], Task::OPEN_STATUSES, true)) {
                // 幂等：已完成直接返回
                if (in_array((string)$task['status'], [Task::STATUS_DONE, Task::STATUS_REJECTED], true)) {
                    Db::commit();
                    return ['id' => $taskId, 'duplicated' => true];
                }
                throw new FlowException('该待办已取消或已转交');
            }

            $instance = Db::name('flow_instance')
                ->where('store_id', $storeId)
                ->where('id', (int)$task['instance_id'])
                ->lock(true)
                ->find();
            if (!$instance) throw new FlowException('流程实例不存在');
            if ((string)$instance['status'] !== Instance::STATUS_RUNNING) {
                throw new FlowException('流程已结束，不能继续处理');
            }

            $this->bootContext($instance);
            $this->assertCanHandle($task);

            if ($action === 'reject') {
                if (trim($comment) === '') throw new FlowException('驳回必须填写意见');

                $this->markTask($task, Task::STATUS_REJECTED, 'reject', $comment, $formData);
                $token = $this->loadToken((int)$task['token_id']);
                $node = $this->node((string)$task['node_key']);

                $this->writeLog((int)$instance['id'], (string)$task['node_key'], 'reject', $comment, [
                    'task_id' => $taskId,
                ], $taskId, (string)$task['node_name']);

                $this->cancelSiblingTasks((int)$task['token_id'], (int)$taskId);
                $this->applyRejectPolicy($instance, $token, $node, $comment, $task);
                $this->finalizeIfDone($instance);

                Db::commit();
            } else {
                if (!in_array($action, ['approve', 'complete', 'sign', 'pay'], true)) {
                    $action = 'approve';
                }
                $this->markTask($task, Task::STATUS_DONE, $action, $comment, $formData);
                $token = $this->loadToken((int)$task['token_id']);
                $node = $this->node((string)$task['node_key']);

                $this->writeLog((int)$instance['id'], (string)$task['node_key'], $action, $comment, [
                    'task_id' => $taskId,
                    'form' => $formData,
                ], $taskId, (string)$task['node_name']);

                $this->progressNode($instance, $token, $node, array_merge($task, [
                    'action' => $action,
                    'comment' => $comment,
                    'form_data' => $formData,
                ]));
                $this->finalizeIfDone($instance);

                Db::commit();
            }
        } catch (\Throwable $e) {
            $this->afterCommit = [];
            Db::rollback();
            throw $e;
        }

        $this->runAfterCommit();
        return ['id' => $taskId, 'duplicated' => false];
    }

    public function transferTask(int $taskId, int $targetUserId, string $comment, array $actor, int $storeId): array
    {
        $this->storeId = $storeId;
        $this->actor = $actor;
        BaseModel::$storeId = $storeId;

        Db::startTrans();
        try {
            $task = Db::name('flow_task')->where('store_id', $storeId)->where('id', $taskId)->lock(true)->find();
            if (!$task) throw new FlowException('待办不存在');
            if (!in_array((string)$task['status'], Task::OPEN_STATUSES, true)) {
                throw new FlowException('该待办已结束，不能转交');
            }
            $this->assertCanHandle($task);

            $target = $this->requireUser($targetUserId);
            $targetName = $this->userDisplayName($target);
            $old = (string)$task['assignee_name'];

            Db::name('flow_task')->where('id', $taskId)->where('store_id', $storeId)->update([
                'assignee_type' => 'user',
                'assignee_id' => $targetUserId,
                'assignee_name' => $targetName,
                'role_id' => 0,
                'dept_id' => (int)($target['department_id'] ?? 0),
                'status' => Task::STATUS_PENDING,
                'claimed_by' => 0,
                'claimed_at' => 0,
                'update_time' => time(),
            ]);

            $instance = $this->loadInstance((int)$task['instance_id']);
            $this->writeLog((int)$instance['id'], (string)$task['node_key'], 'transfer', $comment, [
                'task_id' => $taskId,
                'from' => $old,
                'to' => $targetName,
            ], $taskId, (string)$task['node_name']);

            $this->queueNotify(fn() => NotificationService::taskAssigned($taskId));

            Db::commit();
        } catch (\Throwable $e) {
            $this->afterCommit = [];
            Db::rollback();
            throw $e;
        }

        $this->runAfterCommit();
        return ['id' => $taskId];
    }

    public function addSign(int $taskId, int $targetUserId, string $comment, array $actor, int $storeId): array
    {
        $this->storeId = $storeId;
        $this->actor = $actor;
        BaseModel::$storeId = $storeId;

        Db::startTrans();
        try {
            $task = Db::name('flow_task')->where('store_id', $storeId)->where('id', $taskId)->lock(true)->find();
            if (!$task) throw new FlowException('待办不存在');
            if (!in_array((string)$task['status'], Task::OPEN_STATUSES, true)) {
                throw new FlowException('该待办已结束，不能加签');
            }
            $this->assertCanHandle($task);

            $target = $this->requireUser($targetUserId);
            $targetName = $this->userDisplayName($target);
            $instance = $this->loadInstance((int)$task['instance_id']);
            if ((string)$instance['status'] !== Instance::STATUS_RUNNING) {
                throw new FlowException('流程已结束，不能加签');
            }
            $this->bootContext($instance);

            $token = $this->loadToken((int)$task['token_id']);
            $node = $this->node((string)$task['node_key']);

            if ((string)($node['complete_mode'] ?: 'any') === 'sequential') {
                throw new FlowException('顺序指派节点不支持加签，请使用转交');
            }

            $newTaskId = $this->insertTask($instance, $token, $node, [
                'id' => $targetUserId,
                'name' => $targetName,
                'type' => 'user',
                'role_id' => 0,
                'dept_id' => (int)($target['department_id'] ?? 0),
            ]);

            // 或签节点加签后改为会签，保证加签人也能表态
            if ((string)($node['complete_mode'] ?: 'any') === 'any') {
                $meta = $this->tokenMeta($token);
                $meta['force_all'] = true;
                $this->saveTokenMeta((int)$token['id'], $meta);
            }

            $this->writeLog((int)$instance['id'], (string)$task['node_key'], 'add_sign', $comment, [
                'task_id' => $taskId,
                'add_task_id' => $newTaskId,
                'to' => $targetName,
            ], $taskId, (string)$task['node_name']);

            $this->queueNotify(fn() => NotificationService::taskAssigned($newTaskId));

            Db::commit();
        } catch (\Throwable $e) {
            $this->afterCommit = [];
            Db::rollback();
            throw $e;
        }

        $this->runAfterCommit();
        return ['id' => $taskId];
    }

    public function terminate(int $instanceId, string $reason, array $actor, int $storeId): array
    {
        $this->storeId = $storeId;
        $this->actor = $actor;
        BaseModel::$storeId = $storeId;

        Db::startTrans();
        try {
            $instance = Db::name('flow_instance')
                ->where('store_id', $storeId)->where('id', $instanceId)->lock(true)->find();
            if (!$instance) throw new FlowException('流程实例不存在');
            if ((string)$instance['status'] !== Instance::STATUS_RUNNING) {
                throw new FlowException('流程已结束');
            }

            $this->bootContext($instance);
            $this->cancelAllOpen($instance);

            Db::name('flow_instance')->where('id', $instanceId)->where('store_id', $storeId)->update([
                'status' => Instance::STATUS_TERMINATED,
                'result' => 'terminated',
                'finished_at' => time(),
                'update_time' => time(),
            ]);
            $this->writeLog($instanceId, '', 'terminate', $reason, []);
            $this->instance['status'] = Instance::STATUS_TERMINATED;
            $this->hook->onInstanceEnd($this->instance, 'terminated');

            Db::commit();
        } catch (\Throwable $e) {
            $this->afterCommit = [];
            Db::rollback();
            throw $e;
        }

        $this->runAfterCommit();
        return ['id' => $instanceId];
    }

    // ==========================================================
    // 节点执行
    // ==========================================================

    private function enterNode(array $instance, array $token, string $nodeKey, int $depth): void
    {
        if ($depth > self::MAX_DEPTH) {
            throw new FlowException('流程推进层级过深，请检查模板是否存在死循环');
        }
        $node = $this->node($nodeKey);
        $this->writeLog((int)$instance['id'], $nodeKey, 'enter', '进入节点', [], 0, (string)$node['name']);

        // 节点进入条件
        if (!ConditionEvaluator::evaluate($node['condition_json'] ?? null, $this->variables)) {
            $this->writeLog((int)$instance['id'], $nodeKey, 'skip', '条件不满足，跳过节点', [], 0, (string)$node['name']);
            $this->markToken($token, Token::STATUS_COMPLETED);
            $this->advanceFromNode($instance, $token, $node, $depth);
            return;
        }

        $type = (string)($node['type'] ?? Node::TYPE_TASK);

        if ($type === Node::TYPE_START) {
            $this->markToken($token, Token::STATUS_COMPLETED);
            $this->advanceFromNode($instance, $token, $node, $depth);
            return;
        }

        if ($type === Node::TYPE_END) {
            $this->markToken($token, Token::STATUS_COMPLETED);
            $this->writeLog((int)$instance['id'], $nodeKey, 'end', '到达结束节点', [], 0, (string)$node['name']);
            return;
        }

        if ($type === Node::TYPE_GATEWAY) {
            $this->handleGateway($instance, $token, $node, $depth);
            return;
        }

        // 任务 / 审批 / 签字 / 收款
        $assignees = $this->resolveAssignees($node);
        if (empty($assignees)) {
            throw new FlowException("节点【{$node['name']}】没有可指派的人员，请检查角色/部门/字段配置");
        }

        $mode = (string)($node['complete_mode'] ?: 'any');
        if ($mode === 'sequential') {
            $meta = $this->tokenMeta($token);
            $meta['queue'] = $assignees;
            $meta['seq_index'] = 0;
            $this->saveTokenMeta((int)$token['id'], $meta);
            $this->insertTask($instance, $token, $node, $assignees[0]);
        } else {
            foreach ($assignees as $assignee) {
                $this->insertTask($instance, $token, $node, $assignee);
            }
        }

        $this->hook->onNodeEnter($instance, $nodeKey);
    }

    private function handleGateway(array $instance, array $token, array $node, int $depth): void
    {
        $nodeKey = (string)$node['node_key'];
        $incoming = 0;
        foreach ($this->definition['edges'] as $edge) {
            if ((string)$edge['to_key'] === $nodeKey) $incoming++;
        }

        if ($incoming > 1) {
            $this->mergeGateway($instance, $token, $node, $incoming, $depth);
            return;
        }

        // 分流
        $this->markToken($token, Token::STATUS_COMPLETED);
        $this->splitGateway($instance, $token, $node, $depth);
    }

    private function mergeGateway(array $instance, array $token, array $node, int $incoming, int $depth): void
    {
        // 当前令牌进入等待
        Db::name('flow_token')->where('id', (int)$token['id'])->where('store_id', $this->storeId)->update([
            'status' => Token::STATUS_WAITING,
            'node_key' => (string)$node['node_key'],
        ]);

        $waiting = Db::name('flow_token')
            ->where('store_id', $this->storeId)
            ->where('instance_id', (int)$instance['id'])
            ->where('node_key', (string)$node['node_key'])
            ->where('status', Token::STATUS_WAITING)
            ->order('id', 'asc')
            ->select()
            ->toArray();

        $joinMode = (string)($node['join_mode'] ?: 'all');
        $gatewayType = (string)($node['gateway_type'] ?? 'parallel');

        if ($joinMode === 'any') {
            $expected = 1;
        } elseif ($gatewayType === 'inclusive') {
            $expected = $this->expectedInclusiveCount($instance, (string)$node['node_key'], $incoming);
        } else {
            $expected = $incoming;
        }

        if (count($waiting) < $expected) {
            return;
        }

        // 合并：保留一条令牌，其余取消
        $keeper = array_shift($waiting);
        $cancelIds = array_column($waiting, 'id');
        if (!empty($cancelIds)) {
            Db::name('flow_token')->where('store_id', $this->storeId)->whereIn('id', $cancelIds)
                ->update(['status' => Token::STATUS_CANCELLED, 'completed_at' => time()]);
        }

        Db::name('flow_token')->where('id', (int)$keeper['id'])->where('store_id', $this->storeId)->update([
            'status' => Token::STATUS_ACTIVE,
            'completed_at' => 0,
        ]);
        $keeper = $this->loadToken((int)$keeper['id']);

        $this->writeLog((int)$instance['id'], (string)$node['node_key'], 'merge', '分支合并', [
            'expected' => $expected,
        ], 0, (string)$node['name']);

        $this->markToken($keeper, Token::STATUS_COMPLETED);
        $this->splitGateway($instance, $keeper, $node, $depth);
    }

    private function splitGateway(array $instance, array $token, array $node, int $depth): void
    {
        $gatewayType = (string)($node['gateway_type'] ?? 'parallel');
        $edges = $this->outgoing((string)$node['node_key'], $token);

        if ($gatewayType === 'exclusive') {
            $chosen = $this->chooseExclusiveEdge($edges);
            if (!$chosen) {
                throw new FlowException("排他网关【{$node['name']}】没有命中的分支");
            }
            $this->activateEdge($instance, $token, $chosen, $depth);
            return;
        }

        if ($gatewayType === 'inclusive') {
            $selected = [];
            $default = null;
            foreach ($edges as $edge) {
                if (!empty($edge['is_default'])) {
                    $default = $edge;
                    continue;
                }
                if (ConditionEvaluator::evaluate($edge['condition_json'] ?? null, $this->variables)) {
                    $selected[] = $edge;
                }
            }
            if (empty($selected)) {
                if ($default) $selected[] = $default;
                else throw new FlowException("包容网关【{$node['name']}】没有命中的分支");
            }
            foreach ($selected as $edge) {
                $this->activateEdge($instance, $token, $edge, $depth);
            }
            return;
        }

        // 并行
        foreach ($edges as $edge) {
            $this->activateEdge($instance, $token, $edge, $depth);
        }
    }

    private function chooseExclusiveEdge(array $edges): ?array
    {
        $default = null;
        foreach ($edges as $edge) {
            if (!empty($edge['is_default'])) {
                $default = $edge;
                continue;
            }
            if (ConditionEvaluator::evaluate($edge['condition_json'] ?? null, $this->variables)) {
                return $edge;
            }
        }
        return $default;
    }

    private function advanceFromNode(array $instance, array $token, array $node, int $depth): void
    {
        $edges = $this->outgoing((string)$node['node_key'], $token);
        if (empty($edges)) {
            $this->writeLog((int)$instance['id'], (string)$node['node_key'], 'dead_end', '节点没有出边', [], 0, (string)$node['name']);
            return;
        }
        foreach ($edges as $edge) {
            $this->activateEdge($instance, $token, $edge, $depth);
        }
    }

    private function activateEdge(array $instance, array $token, array $edge, int $depth): void
    {
        $newTokenId = $this->createToken(
            (int)$instance['id'],
            (string)$edge['to_key'],
            (int)$token['id'],
            (string)$edge['from_key']
        );
        $newToken = $this->loadToken($newTokenId);
        $this->enterNode($instance, $newToken, (string)$edge['to_key'], $depth + 1);
    }

    private function progressNode(array $instance, array $token, array $node, array $task): void
    {
        $mode = (string)($node['complete_mode'] ?: 'any');
        $meta = $this->tokenMeta($token);
        if (!empty($meta['force_all'])) $mode = 'all';

        if ($mode === 'any') {
            $this->cancelSiblingTasks((int)$token['id']);
            $this->completeNode($instance, $token, $node, $task);
            return;
        }

        if ($mode === 'all') {
            $remaining = Db::name('flow_task')
                ->where('store_id', $this->storeId)
                ->where('token_id', (int)$token['id'])
                ->whereIn('status', Task::OPEN_STATUSES)
                ->count();
            if ($remaining > 0) return;
            $this->completeNode($instance, $token, $node, $task);
            return;
        }

        if ($mode === 'ratio') {
            $ratio = (int)($node['complete_ratio'] ?: 100);
            $total = Db::name('flow_task')
                ->where('store_id', $this->storeId)
                ->where('token_id', (int)$token['id'])
                ->whereIn('status', [Task::STATUS_PENDING, Task::STATUS_CLAIMED, Task::STATUS_DONE, Task::STATUS_REJECTED])
                ->count();
            $done = Db::name('flow_task')
                ->where('store_id', $this->storeId)
                ->where('token_id', (int)$token['id'])
                ->where('status', Task::STATUS_DONE)
                ->count();
            if ($total <= 0 || ($done / $total) * 100 < $ratio) return;
            $this->cancelSiblingTasks((int)$token['id']);
            $this->completeNode($instance, $token, $node, $task);
            return;
        }

        if ($mode === 'sequential') {
            $queue = $meta['queue'] ?? [];
            $index = (int)($meta['seq_index'] ?? 0) + 1;
            if ($index < count($queue)) {
                $meta['seq_index'] = $index;
                $this->saveTokenMeta((int)$token['id'], $meta);
                $this->insertTask($instance, $token, $node, $queue[$index]);
                return;
            }
            $this->completeNode($instance, $token, $node, $task);
        }
    }

    private function completeNode(array $instance, array $token, array $node, array $task): void
    {
        $this->hook->onNodeComplete($instance, (string)$node['node_key'], $task);
        $this->writeLog((int)$instance['id'], (string)$node['node_key'], 'node_complete', '节点完成', [], (int)($task['id'] ?? 0), (string)$node['name']);

        $this->markToken($token, Token::STATUS_COMPLETED);
        $this->advanceFromNode($instance, $token, $node, 0);
    }

    // ==========================================================
    // 驳回 / 终止
    // ==========================================================

    private function applyRejectPolicy(array $instance, array $token, array $node, string $comment, array $task): void
    {
        $policy = $node['reject_policy'] ?? [];
        if (is_string($policy)) {
            $decoded = json_decode($policy, true);
            $policy = is_array($decoded) ? $decoded : [];
        }
        $type = (string)($policy['type'] ?? 'to_start');
        $target = (string)($policy['target'] ?? '');

        $this->cancelAllOpen($instance);

        if ($type === 'terminate') {
            Db::name('flow_instance')->where('id', (int)$instance['id'])->where('store_id', $this->storeId)->update([
                'status' => Instance::STATUS_REJECTED,
                'result' => 'rejected',
                'finished_at' => time(),
                'update_time' => time(),
            ]);
            $this->instance['status'] = Instance::STATUS_REJECTED;
            $this->writeLog((int)$instance['id'], (string)$node['node_key'], 'reject_end', $comment, [], (int)($task['id'] ?? 0));
            $this->hook->onInstanceEnd($this->instance, 'rejected');
            return;
        }

        if ($type === 'back_prev') {
            $target = $this->findPreviousTaskNode((int)$instance['id'], (string)$node['node_key']);
        }
        if ($type === 'to_start' || $target === '') {
            $target = $this->findStartNode();
        }
        if (!isset($this->definition['nodes'][$target])) {
            $target = $this->findStartNode();
        }

        $newTokenId = $this->createToken((int)$instance['id'], $target, 0, '');
        $newToken = $this->loadToken($newTokenId);
        $this->writeLog((int)$instance['id'], $target, 'reject_back', '驳回后回到节点', [
            'from' => (string)$node['node_key'],
            'target' => $target,
        ], (int)($task['id'] ?? 0));

        $this->enterNode($this->instance, $newToken, $target, 0);
    }

    private function findPreviousTaskNode(int $instanceId, string $currentNodeKey): string
    {
        $rows = Db::name('flow_log')
            ->where('store_id', $this->storeId)
            ->where('instance_id', $instanceId)
            ->whereIn('action', ['node_complete', 'enter'])
            ->where('node_key', '<>', $currentNodeKey)
            ->order('id', 'desc')
            ->limit(20)
            ->column('node_key');

        foreach ($rows as $key) {
            $node = $this->definition['nodes'][$key] ?? null;
            if ($node && isset(Node::TASK_TYPES[(string)$node['type']])) {
                return (string)$key;
            }
        }
        return '';
    }

    private function cancelAllOpen(array $instance): void
    {
        $tokenIds = Db::name('flow_token')
            ->where('store_id', $this->storeId)
            ->where('instance_id', (int)$instance['id'])
            ->whereIn('status', [Token::STATUS_ACTIVE, Token::STATUS_WAITING])
            ->column('id');

        if (!empty($tokenIds)) {
            Db::name('flow_token')->where('store_id', $this->storeId)->whereIn('id', $tokenIds)
                ->update(['status' => Token::STATUS_CANCELLED, 'completed_at' => time()]);
            Db::name('flow_task')->where('store_id', $this->storeId)->whereIn('token_id', $tokenIds)
                ->whereIn('status', Task::OPEN_STATUSES)
                ->update(['status' => Task::STATUS_CANCELLED, 'update_time' => time()]);
        }
    }

    // ==========================================================
    // 任务 / 令牌
    // ==========================================================

    private function resolveAssignees(array $node): array
    {
        $rules = $node['assignee_rules'] ?? [];
        if (is_string($rules)) {
            $rules = json_decode($rules, true) ?: [];
        }

        $resolver = new OrgResolver($this->storeId);
        $context = [
            'variables' => $this->variables,
            'snapshot' => $this->snapshot,
            'initiator_id' => (int)($this->actor['id'] ?? $this->instance['started_by'] ?? 0),
        ];
        $users = $resolver->resolve($rules, $context);

        if (empty($users)) {
            $config = $node['config'] ?? [];
            if (is_string($config)) $config = json_decode($config, true) ?: [];
            if (!empty($config['fallback_rules']) && is_array($config['fallback_rules'])) {
                $users = $resolver->resolve($config['fallback_rules'], $context);
            }
            $fallback = [];
            if (!empty($config['fallback_role'])) {
                $fallback[] = ['type' => 'role', 'id' => (int)$config['fallback_role']];
            }
            if (!empty($config['fallback_user'])) {
                $fallback[] = ['type' => 'user', 'id' => (int)$config['fallback_user']];
            }
            if (empty($users) && !empty($fallback)) {
                $users = $resolver->resolve($fallback, $context);
            }
        }

        // 最后兜底：谁发起谁处理，避免无人可派的死单
        if (empty($users)) {
            $users = $resolver->resolve([['type' => 'creator']], $context);
        }

        return $users;
    }

    private function insertTask(array $instance, array $token, array $node, array $assignee): int
    {
        $slaHours = (int)($node['sla_hours'] ?? 0);
        $remindBefore = (int)($node['remind_before_hours'] ?? 0);
        $dueAt = $slaHours > 0 ? time() + $slaHours * 3600 : 0;
        $remindAt = ($dueAt > 0 && $remindBefore > 0) ? $dueAt - $remindBefore * 3600 : 0;

        $taskId = (int)Db::name('flow_task')->insertGetId([
            'store_id' => $this->storeId,
            'instance_id' => (int)$instance['id'],
            'token_id' => (int)$token['id'],
            'node_key' => (string)$node['node_key'],
            'node_name' => (string)$node['name'],
            'assignee_type' => (string)($assignee['type'] ?? 'user'),
            'assignee_id' => (int)($assignee['id'] ?? 0),
            'assignee_name' => (string)($assignee['name'] ?? ''),
            'role_id' => (int)($assignee['role_id'] ?? 0),
            'dept_id' => (int)($assignee['dept_id'] ?? 0),
            'status' => Task::STATUS_PENDING,
            'action' => '',
            'comment' => '',
            'form_data' => '',
            'due_at' => $dueAt,
            'remind_at' => $remindAt,
            'create_time' => time(),
            'update_time' => time(),
        ]);

        $this->queueNotify(fn() => NotificationService::taskAssigned($taskId));
        return $taskId;
    }

    private function markTask(array $task, string $status, string $action, string $comment, array $formData): void
    {
        Db::name('flow_task')->where('id', (int)$task['id'])->where('store_id', $this->storeId)->update([
            'status' => $status,
            'action' => $action,
            'comment' => mb_substr($comment, 0, 1000),
            'form_data' => json_encode($formData, JSON_UNESCAPED_UNICODE),
            'claimed_by' => (int)($this->actor['id'] ?? 0),
            'claimed_at' => (int)($task['claimed_at'] ?? 0) ?: time(),
            'completed_by' => (int)($this->actor['id'] ?? 0),
            'completed_at' => time(),
            'update_time' => time(),
        ]);
    }

    private function cancelSiblingTasks(int $tokenId, int $exceptTaskId = 0): void
    {
        $query = Db::name('flow_task')
            ->where('store_id', $this->storeId)
            ->where('token_id', $tokenId)
            ->whereIn('status', Task::OPEN_STATUSES);
        if ($exceptTaskId > 0) $query->where('id', '<>', $exceptTaskId);
        $query->update(['status' => Task::STATUS_CANCELLED, 'update_time' => time()]);
    }

    private function createToken(int $instanceId, string $nodeKey, int $parentTokenId, string $branchKey): int
    {
        return (int)Db::name('flow_token')->insertGetId([
            'store_id' => $this->storeId,
            'instance_id' => $instanceId,
            'node_key' => $nodeKey,
            'status' => Token::STATUS_ACTIVE,
            'parent_token_id' => $parentTokenId,
            'branch_key' => $branchKey,
            'meta' => '',
            'create_time' => time(),
            'completed_at' => 0,
        ]);
    }

    private function markToken(array $token, string $status): void
    {
        Db::name('flow_token')->where('id', (int)$token['id'])->where('store_id', $this->storeId)->update([
            'status' => $status,
            'completed_at' => $status === Token::STATUS_COMPLETED ? time() : 0,
        ]);
        $token['status'] = $status;
    }

    private function tokenMeta(array $token): array
    {
        $raw = (string)($token['meta'] ?? '');
        if ($raw === '') return [];
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function saveTokenMeta(int $tokenId, array $meta): void
    {
        Db::name('flow_token')->where('id', $tokenId)->where('store_id', $this->storeId)
            ->update(['meta' => json_encode($meta, JSON_UNESCAPED_UNICODE)]);
    }

    // ==========================================================
    // 完成判定
    // ==========================================================

    private function finalizeIfDone(array $instance): void
    {
        $open = Db::name('flow_token')
            ->where('store_id', $this->storeId)
            ->where('instance_id', (int)$instance['id'])
            ->whereIn('status', [Token::STATUS_ACTIVE, Token::STATUS_WAITING])
            ->count();

        if ($open > 0) return;

        Db::name('flow_instance')->where('id', (int)$instance['id'])->where('store_id', $this->storeId)->update([
            'status' => Instance::STATUS_COMPLETED,
            'result' => 'approved',
            'finished_at' => time(),
            'update_time' => time(),
        ]);
        $this->instance = $this->loadInstance((int)$instance['id']);
        $this->writeLog((int)$instance['id'], '', 'instance_complete', '流程完成', []);
        $this->hook->onInstanceEnd($this->instance, 'approved');
    }

    // ==========================================================
    // 上下文
    // ==========================================================

    private function bootContext(array $instance): void
    {
        $this->bizType = (string)$instance['biz_type'];
        $this->bizConfig = FlowRegistry::get($this->bizType);
        $this->hook = FlowRegistry::hook($this->bizType);
        $this->instance = $instance;

        $variables = json_decode((string)($instance['variables'] ?? ''), true);
        $this->variables = is_array($variables) ? $variables : [];
        $snapshot = json_decode((string)($instance['business_snapshot'] ?? ''), true);
        $this->snapshot = is_array($snapshot) ? $snapshot : [];

        $service = new TemplateService($this->storeId);
        $this->definition = $service->loadDefinition((int)$instance['template_id']);

        // 业务数据可能已变化，重新取一次变量
        try {
            $fresh = $this->hook->variables((int)$instance['biz_id'], $this->storeId);
            if (is_array($fresh)) {
                $this->variables = array_merge($this->variables, $fresh);
            }
            $freshSnapshot = $this->hook->snapshot((int)$instance['biz_id'], $this->storeId);
            if (is_array($freshSnapshot)) {
                $this->snapshot = array_merge($this->snapshot, $freshSnapshot);
            }
        } catch (\Throwable $e) {
            // 业务侧读取失败不阻塞流程推进
        }
    }

    private function node(string $nodeKey): array
    {
        if (!isset($this->definition['nodes'][$nodeKey])) {
            throw new FlowException("流程节点不存在：{$nodeKey}");
        }
        return $this->definition['nodes'][$nodeKey];
    }

    private function outgoing(string $nodeKey, array $token): array
    {
        return $this->definition['outgoing'][$nodeKey] ?? [];
    }

    private function findStartNode(): string
    {
        foreach ($this->definition['nodes'] as $key => $node) {
            if ((string)$node['type'] === Node::TYPE_START) return (string)$key;
        }
        throw new FlowException('模板缺少开始节点');
    }

    private function expectedInclusiveCount(array $instance, string $gatewayKey, int $fallback): int
    {
        $entered = Db::name('flow_log')
            ->where('store_id', $this->storeId)
            ->where('instance_id', (int)$instance['id'])
            ->whereIn('action', ['enter', 'skip', 'node_complete'])
            ->column('node_key');
        $entered = array_flip(array_map('strval', $entered));

        $count = 0;
        foreach ($this->definition['edges'] as $edge) {
            if ((string)$edge['to_key'] !== $gatewayKey) continue;
            if (isset($entered[(string)$edge['from_key']])) $count++;
        }
        return $count > 0 ? $count : $fallback;
    }

    private function assertCanHandle(array $task): void
    {
        $actorId = (int)($this->actor['id'] ?? 0);
        if (!empty($this->actor['is_super'])) return;
        if ($actorId > 0 && $actorId === (int)$task['assignee_id']) return;
        throw new FlowException('你不是该待办的处理人');
    }

    private function userDisplayName(array $user): string
    {
        return (string)($user['real_name'] ?: $user['user_name']);
    }

    private function requireUser(int $userId): array
    {
        $user = Db::name('store_user')
            ->where('store_id', $this->storeId)
            ->where('store_user_id', $userId)
            ->where('is_delete', 0)
            ->find();
        if (!$user) throw new FlowException('目标用户不存在或不属于当前租户');
        return $user;
    }

    private function loadInstance(int $id): array
    {
        $instance = Db::name('flow_instance')->where('store_id', $this->storeId)->where('id', $id)->find();
        if (!$instance) throw new FlowException('流程实例不存在');
        return $instance;
    }

    private function loadToken(int $id): array
    {
        $token = Db::name('flow_token')->where('store_id', $this->storeId)->where('id', $id)->find();
        if (!$token) throw new FlowException('流程令牌不存在');
        return $token;
    }

    private function writeLog(
        int $instanceId,
        string $nodeKey,
        string $action,
        string $comment,
        array $detail = [],
        int $taskId = 0,
        string $nodeName = ''
    ): void {
        Db::name('flow_log')->insert([
            'store_id' => $this->storeId,
            'instance_id' => $instanceId,
            'task_id' => $taskId,
            'node_key' => $nodeKey,
            'action' => $action,
            'operator_id' => (int)($this->actor['id'] ?? 0),
            'operator_name' => mb_substr((string)($this->actor['name'] ?? '系统'), 0, 50),
            'comment' => mb_substr($comment, 0, 1000),
            'detail' => json_encode(array_merge($detail, ['node_name' => $nodeName]), JSON_UNESCAPED_UNICODE),
            'create_time' => time(),
        ]);
    }

    private function queueNotify(callable $fn): void
    {
        $this->afterCommit[] = $fn;
    }

    private function runAfterCommit(): void
    {
        foreach ($this->afterCommit as $fn) {
            try {
                $fn();
            } catch (\Throwable $e) {
                \think\facade\Log::error('[flow] notify failed: ' . $e->getMessage());
            }
        }
        $this->afterCommit = [];
    }

    private function instanceResult(array $instance): array
    {
        return [
            'instance_id' => (int)$instance['id'],
            'status' => (string)$instance['status'],
            'result' => (string)$instance['result'],
            'biz_type' => (string)$instance['biz_type'],
            'biz_id' => (int)$instance['biz_id'],
        ];
    }
}
