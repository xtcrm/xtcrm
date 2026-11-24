<?php

declare(strict_types=1);

namespace app\platform\controller\flow;

use app\platform\flow\FlowException;
use app\platform\flow\FlowRegistry;
use app\platform\flow\service\InstanceService;
use app\platform\flow\service\TaskService;
use app\platform\permission\PermissionChecker;

/**
 * 流程实例
 * 路由：s=/platform/flow.instance/{action}
 */
class Instance extends Base
{
    protected $methodRules = [
        'lists' => 'GET',
        'detail' => 'GET',
        'steps' => 'GET',
        'start' => 'POST',
        'terminate' => 'POST',
    ];

    public function lists()
    {
        return $this->call(function () {
            $super = (bool)($this->actor()['is_super'] ?? false);
            $mine = (int)$this->request->param('mine', 0) === 1;
            if (!$mine && !$super && !PermissionChecker::can('/flow.instance/lists', (int)$this->getUserId())) {
                $mine = true; // 无全量查看权限时，只能看自己发起的
            }
            $params = $this->request->param();
            if ($mine) $params['mine'] = 1;
            return (new InstanceService((int)$this->storeId, (int)$this->getUserId(), $super))->getList(
                $params,
                max(1, (int)$this->request->param('page', 1)),
                min(100, max(1, (int)$this->request->param('limit', 20)))
            );
        });
    }

    public function detail()
    {
        return $this->call(function () {
            $id = (int)$this->request->param('id', 0);
            if ($id <= 0) throw new FlowException('缺少实例 ID');
            $super = (bool)($this->actor()['is_super'] ?? false);
            $canAll = $super || PermissionChecker::can('/flow.instance/detail', (int)$this->getUserId());
            return (new InstanceService((int)$this->storeId, (int)$this->getUserId(), $super))->detail($id, $canAll);
        });
    }

    public function steps()
    {
        return $this->call(function () {
            $bizType = (string)$this->request->param('biz_type', '');
            $bizId = (int)$this->request->param('biz_id', 0);
            if ($bizType === '' || $bizId <= 0) throw new FlowException('缺少业务参数');
            return (new TaskService((int)$this->storeId, (int)$this->getUserId()))->steps($bizType, $bizId);
        });
    }

    public function start()
    {
        if (!$this->checkAction('/flow.instance/start', '启动流程')) return;
        return $this->call(function () {
            $bizType = (string)$this->request->param('biz_type', '');
            $bizId = (int)$this->request->param('biz_id', 0);
            $templateCode = (string)$this->request->param('template_code', '');
            $force = (int)$this->request->param('force', 0) === 1;
            if (!FlowRegistry::has($bizType)) throw new FlowException('业务类型未注册');
            if ($bizId <= 0) throw new FlowException('缺少业务 ID');
            return (new InstanceService((int)$this->storeId, (int)$this->getUserId(), (bool)($this->actor()['is_super'] ?? false)))
                ->start($bizType, $bizId, $this->actor(), $templateCode, $force);
        });
    }

    public function terminate()
    {
        if (!$this->checkAction('/flow.instance/terminate', '终止流程')) return;
        return $this->call(function () {
            $id = (int)$this->request->param('id', 0);
            if ($id <= 0) throw new FlowException('缺少实例 ID');
            $reason = trim((string)$this->request->param('reason', ''));
            if ($reason === '') throw new FlowException('请填写终止原因');
            return (new InstanceService((int)$this->storeId, (int)$this->getUserId(), (bool)($this->actor()['is_super'] ?? false)))
                ->terminate($id, $reason, $this->actor());
        });
    }
}
