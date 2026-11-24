<?php

declare(strict_types=1);

namespace app\platform\controller\flow;

use app\platform\flow\FlowException;
use app\platform\flow\service\FlowEngine;
use app\platform\flow\service\TaskService;

/**
 * 流程待办
 * 路由：s=/platform/flow.task/{action}
 */
class Task extends Base
{
    protected $methodRules = [
        'lists' => 'GET',
        'count' => 'GET',
        'detail' => 'GET',
        'complete' => 'POST',
        'transfer' => 'POST',
        'addSign' => 'POST',
        'steps' => 'GET',
    ];

    public function lists()
    {
        return $this->call(function () {
            $scope = (string)$this->request->param('scope', 'pending');
            if (!in_array($scope, ['pending', 'done', 'created', 'all'], true)) $scope = 'pending';
            $service = new TaskService((int)$this->storeId, (int)$this->getUserId(), (bool)($this->actor()['is_super'] ?? false));
            return $service->getList(
                $scope,
                $this->request->param(),
                max(1, (int)$this->request->param('page', 1)),
                min(100, max(1, (int)$this->request->param('limit', 20)))
            );
        });
    }

    public function count()
    {
        return $this->call(function () {
            return (new TaskService((int)$this->storeId, (int)$this->getUserId(), (bool)($this->actor()['is_super'] ?? false)))->count();
        });
    }

    public function detail()
    {
        return $this->call(function () {
            $id = (int)$this->request->param('id', 0);
            if ($id <= 0) throw new FlowException('缺少待办 ID');
            return (new TaskService((int)$this->storeId, (int)$this->getUserId(), (bool)($this->actor()['is_super'] ?? false)))->detail($id);
        });
    }

    public function complete()
    {
        return $this->call(function () {
            $id = (int)$this->request->param('id', 0);
            if ($id <= 0) throw new FlowException('缺少待办 ID');
            $action = (string)$this->request->param('action', 'approve');
            $comment = (string)$this->request->param('comment', '');
            return (new FlowEngine())->completeTask(
                $id,
                $action,
                $comment,
                $this->formData(),
                $this->actor(),
                (int)$this->storeId
            );
        });
    }

    public function transfer()
    {
        return $this->call(function () {
            $id = (int)$this->request->param('id', 0);
            $target = (int)$this->request->param('target_user_id', 0);
            if ($id <= 0 || $target <= 0) throw new FlowException('请选择转交对象');
            return (new FlowEngine())->transferTask(
                $id,
                $target,
                (string)$this->request->param('comment', ''),
                $this->actor(),
                (int)$this->storeId
            );
        });
    }

    public function addSign()
    {
        return $this->call(function () {
            $id = (int)$this->request->param('id', 0);
            $target = (int)$this->request->param('target_user_id', 0);
            if ($id <= 0 || $target <= 0) throw new FlowException('请选择加签人');
            return (new FlowEngine())->addSign(
                $id,
                $target,
                (string)$this->request->param('comment', ''),
                $this->actor(),
                (int)$this->storeId
            );
        });
    }

    /**
     * 业务单据的流程步骤（详情页嵌入）
     */
    public function steps()
    {
        return $this->call(function () {
            $bizType = (string)$this->request->param('biz_type', '');
            $bizId = (int)$this->request->param('biz_id', 0);
            if ($bizType === '' || $bizId <= 0) throw new FlowException('缺少业务参数');
            return (new TaskService((int)$this->storeId, (int)$this->getUserId(), (bool)($this->actor()['is_super'] ?? false)))->steps($bizType, $bizId);
        });
    }
}
