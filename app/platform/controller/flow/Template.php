<?php

declare(strict_types=1);

namespace app\platform\controller\flow;

use app\platform\flow\FlowRegistry;
use app\platform\flow\model\Node;
use app\platform\flow\service\OrgResolver;
use app\platform\flow\service\TemplateService;

/**
 * 流程模板
 * 路由：s=/platform/flow.template/{action}
 */
class Template extends Base
{
    protected $methodRules = [
        'lists' => 'GET',
        'detail' => 'GET',
        'create' => 'POST',
        'saveDraft' => 'POST',
        'check' => 'GET',
        'publish' => 'POST',
        'archive' => 'POST',
        'setDefault' => 'POST',
        'copyVersion' => 'POST',
        'remove' => 'POST',
        'meta' => 'GET',
        'presets' => 'GET',
        'assignmentCheck' => 'GET',
    ];

    public function lists()
    {
        if (!$this->checkAction('/flow.template/lists', '查询流程模板')) return;
        return $this->call(function () {
            $service = new TemplateService((int)$this->storeId);
            $params = $this->request->param();
            $params['visible_biz_types'] = array_column(FlowRegistry::options((int)$this->storeId), 'value');
            return $service->getList(
                $params,
                max(1, (int)$this->request->param('page', 1)),
                min(100, max(1, (int)$this->request->param('limit', 20)))
            );
        });
    }

    public function detail()
    {
        if (!$this->checkAction('/flow.template/detail', '流程模板详情')) return;
        return $this->call(function () {
            $id = (int)$this->request->param('id', 0);
            if ($id <= 0) throw new \app\platform\flow\FlowException('缺少模板 ID');
            return (new TemplateService((int)$this->storeId))->detail($id);
        });
    }

    public function create()
    {
        if (!$this->checkAction('/flow.template/create', '新建流程模板')) return;
        return $this->call(function () {
            $service = new TemplateService((int)$this->storeId);
            $post = $this->request->post();
            $bizType = (string)($post['biz_type'] ?? '');
            if (!FlowRegistry::bizVisible($bizType, (int)$this->storeId)) {
                throw new \app\platform\flow\FlowException('该业务类型未开通');
            }
            return $service->create($post, (int)$this->actor()['id']);
        });
    }

    public function saveDraft()
    {
        if (!$this->checkAction('/flow.template/saveDraft', '保存流程草稿')) return;
        return $this->call(function () {
            $id = (int)$this->request->param('id', 0);
            if ($id <= 0) throw new \app\platform\flow\FlowException('缺少模板 ID');
            $payload = [
                'nodes' => $this->request->param('nodes', []),
                'edges' => $this->request->param('edges', []),
                'graph' => $this->request->param('graph', null),
                'name' => $this->request->param('name', null),
                'description' => $this->request->param('description', null),
            ];
            return (new TemplateService((int)$this->storeId))->saveDraft($id, $payload, (int)$this->actor()['id']);
        });
    }

    public function check()
    {
        if (!$this->checkAction('/flow.template/check', '校验流程模板')) return;
        return $this->call(function () {
            $id = (int)$this->request->param('id', 0);
            if ($id <= 0) throw new \app\platform\flow\FlowException('缺少模板 ID');
            return (new TemplateService((int)$this->storeId))->validateTemplate($id);
        });
    }

    public function publish()
    {
        if (!$this->checkAction('/flow.template/publish', '发布流程模板')) return;
        return $this->call(function () {
            $id = (int)$this->request->param('id', 0);
            if ($id <= 0) throw new \app\platform\flow\FlowException('缺少模板 ID');
            $asDefault = (int)$this->request->param('as_default', 1) === 1;
            $confirm = (int)$this->request->param('confirm_assignment', 0) === 1;
            return (new TemplateService((int)$this->storeId))->publish($id, $asDefault, (int)$this->actor()['id'], $confirm);
        });
    }

    public function archive()
    {
        if (!$this->checkAction('/flow.template/archive', '归档流程模板')) return;
        return $this->call(function () {
            $id = (int)$this->request->param('id', 0);
            if ($id <= 0) throw new \app\platform\flow\FlowException('缺少模板 ID');
            return (new TemplateService((int)$this->storeId))->archive($id);
        });
    }

    public function setDefault()
    {
        if (!$this->checkAction('/flow.template/setDefault', '设为默认流程')) return;
        return $this->call(function () {
            $id = (int)$this->request->param('id', 0);
            if ($id <= 0) throw new \app\platform\flow\FlowException('缺少模板 ID');
            return (new TemplateService((int)$this->storeId))->setDefault($id);
        });
    }

    public function copyVersion()
    {
        if (!$this->checkAction('/flow.template/copyVersion', '复制流程版本')) return;
        return $this->call(function () {
            $id = (int)$this->request->param('id', 0);
            if ($id <= 0) throw new \app\platform\flow\FlowException('缺少模板 ID');
            return (new TemplateService((int)$this->storeId))->copyVersion($id, (int)$this->actor()['id']);
        });
    }

    public function remove()
    {
        if (!$this->checkAction('/flow.template/remove', '删除流程模板')) return;
        return $this->call(function () {
            $id = (int)$this->request->param('id', 0);
            if ($id <= 0) throw new \app\platform\flow\FlowException('缺少模板 ID');
            return (new TemplateService((int)$this->storeId))->remove($id);
        });
    }

    /**
     * 发布前指派检查
     */
    public function assignmentCheck()
    {
        if (!$this->checkAction('/flow.template/check', '指派检查')) return;
        return $this->call(function () {
            $id = (int)$this->request->param('id', 0);
            if ($id <= 0) throw new \app\platform\flow\FlowException('缺少模板 ID');
            return (new TemplateService((int)$this->storeId))->assignmentCheck($id);
        });
    }

    /**
     * 系统预置模板（库里没有任何记录时也能选）
     */
    public function presets()
    {
        if (!$this->checkAction('/flow.template/create', '系统预设模板')) return;
        return $this->call(function () {
            $bizType = (string)$this->request->param('biz_type', '');
            if (!FlowRegistry::has($bizType)) throw new \app\platform\flow\FlowException('业务类型未注册');
            if (!FlowRegistry::bizVisible($bizType, (int)$this->storeId)) {
                throw new \app\platform\flow\FlowException('该业务类型未开通');
            }
            $list = [];
            foreach (FlowRegistry::presets((int)$this->storeId, $bizType) as $preset) {
                $nodes = [];
                foreach ($preset['nodes'] as $node) {
                    if (!is_array($node)) continue;
                    $nodes[] = [
                        'node_key' => (string)($node['node_key'] ?? ''),
                        'name' => (string)($node['name'] ?? ''),
                        'type' => (string)($node['type'] ?? ''),
                        'sort' => (int)($node['sort'] ?? 100),
                    ];
                }
                $list[] = [
                    'key' => $preset['key'],
                    'name' => $preset['name'],
                    'description' => $preset['description'],
                    'node_count' => count($nodes),
                    'nodes' => $nodes,
                ];
            }
            return $list;
        });
    }

    /**
     * 设计器元数据：业务类型、节点类型、人员/角色/部门
     */
    public function meta()
    {
        return $this->call(function () {
            $storeId = (int)$this->storeId;
            $bizType = (string)$this->request->param('biz_type', '');
            return [
                'biz_types' => FlowRegistry::options((int)$this->storeId),
                'node_types' => Node::typeMap(),
                'gateway_types' => ['exclusive' => '排他', 'parallel' => '并行', 'inclusive' => '包容'],
                'complete_modes' => ['any' => '或签', 'all' => '会签', 'ratio' => '比例', 'sequential' => '顺序'],
                'allowed_variables' => $bizType !== '' && FlowRegistry::has($bizType)
                    ? FlowRegistry::allowedVariableOptions($bizType)
                    : array_map(fn($value) => [
                        'value' => $value,
                        'label' => FlowRegistry::SYSTEM_VARIABLE_LABELS[$value] ?? $value,
                        'group' => '系统',
                    ], FlowRegistry::SYSTEM_VARIABLES),
                'users' => OrgResolver::userOptions($storeId),
                'roles' => OrgResolver::roleOptions($storeId),
                'departments' => OrgResolver::deptTree($storeId),
            ];
        });
    }
}
