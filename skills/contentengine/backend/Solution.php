<?php
namespace skills\contentengine\backend;

use app\platform\backend\BaseController;
use skills\contentengine\service\SolutionService;

class Solution extends BaseController
{
    protected $methodRules = [
        'lists' => 'GET', 'detail' => 'GET', 'add' => 'POST', 'edit' => 'POST', 'delete' => 'POST',
    ];

    public function lists()
    {
        if (!$this->checkAction('/crm.solution/add', '查看方案')) return;
        $result = (new SolutionService())->getList($this->request->param());
        return $this->renderSuccess($result['data']);
    }

    public function detail()
    {
        if (!$this->checkAction('/crm.solution/edit', '查看方案')) return;
        $id = $this->request->param('id', 0);
        $result = (new SolutionService())->detail((int)$id);
        if (!$result['success']) return $this->renderError($result['error']);
        return $this->renderSuccess($result['data']);
    }

    public function add()
    {
        if (!$this->checkAction('/crm.solution/add', '添加方案')) return;
        $data = $this->postData();
        $result = (new SolutionService())->add($data);
        if ($result['success']) return $this->renderSuccess($result['data'], '添加成功');
        return $this->renderError($result['error']);
    }

    public function edit()
    {
        if (!$this->checkAction('/crm.solution/edit', '编辑方案')) return;
        $data = $this->postData();
        $result = (new SolutionService())->edit($data);
        if ($result['success']) return $this->renderSuccess([], '更新成功');
        return $this->renderError($result['error']);
    }

    public function delete()
    {
        if (!$this->checkAction('/crm.solution/delete', '删除方案')) return;
        $id = $this->request->param('id', 0);
        $result = (new SolutionService())->delete((int)$id);
        if ($result['success']) return $this->renderSuccess([], '删除成功');
        return $this->renderError($result['error']);
    }
}
