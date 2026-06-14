<?php
namespace mpp\crm\backend;

use app\platform\backend\BaseController;
use mpp\crm\service\CustomerService;

class Customer extends BaseController
{
    protected $methodRules = [
        'lists' => 'GET', 'select' => 'GET', 'detail' => 'GET',
        'poolLists' => 'GET', 'collabLists' => 'GET', 'checkName' => 'GET',
        'add' => 'POST', 'edit' => 'POST', 'delete' => 'POST',
        'claim' => 'POST', 'release' => 'POST', 'changeStatus' => 'POST',
        'analyze' => 'POST', 'portrait' => 'POST', 'smartSearch' => 'POST',
    ];

    protected function getUserId(): int
    {
        return $this->store['store_user_id'] ?? ($this->store['uid'] ?? 0);
    }

    public function lists()
    {
        if(!$this->checkAction('/crm/customer/lists','客户列表')) return;
        $result = (new CustomerService())->getList($this->request->param());
        return $this->renderSuccess($result['data']);
    }

    public function select()
    {
        if(!$this->checkAction('/crm/customer/select','客户查询')) return;
        $result = (new CustomerService())->select();
        return $this->renderSuccess($result['data']);
    }

    public function detail()
    {
        if(!$this->checkAction('/crm/customer/detail','客户详情')) return;
        $id = $this->request->param('id', 0);
        $from = $this->request->param('from', '');
        $result = (new CustomerService())->detail($id, $from);
        if (!$result['success']) return $this->renderError($result['error']);
        return $this->renderSuccess($result['data']);
    }

    public function poolLists()
    {
        if(!$this->checkAction('/crm/customer/poolLists','公海列表')) return;
        $result = (new CustomerService())->getPoolList($this->request->param());
        return $this->renderSuccess($result['data']);
    }

    public function collabLists()
    {
        $result = (new CustomerService())->getCollabList($this->request->param());
        return $this->renderSuccess($result['data']);
    }

    public function checkName()
    {
        $name = $this->request->param('customer_name', '');
        $excludeId = (int)$this->request->param('exclude_id', 0);
        if (empty($name)) return $this->renderSuccess(['exists' => false]);
        $exists = \mpp\crm\model\Customer::where('customer_name', $name)
            ->where('is_delete', 0)
            ->when($excludeId > 0, function ($q) use ($excludeId) { $q->where('id', '<>', $excludeId); })
            ->count() > 0;
        return $this->renderSuccess(['exists' => $exists]);
    }

    public function add()
    {
        if(!$this->checkAction('/crm/customer/add','新增客户')) return;
        $data = $this->postData();
        $userId = $this->getUserId();
        $data['creator_user_id'] = $userId;
        // 兜底：前端未传或传 0 时，默认负责人 = 当前用户
        if (empty($data['owner_user_id'])) {
            $data['owner_user_id'] = $userId;
        }
        $result = (new CustomerService())->add($data, $userId);
        if ($result['success']) return $this->renderSuccess($result['data'], '添加成功');
        return $this->renderError($result['error']);
    }

    public function edit()
    {
        if(!$this->checkAction('/crm/customer/edit','编辑客户')) return;
        $id = $this->request->param('id', 0);
        $result = (new CustomerService())->edit($id, $this->postData(), $this->getUserId());
        if ($result['success']) return $this->renderSuccess([], '更新成功');
        return $this->renderError($result['error']);
    }

    public function delete()
    {
        if(!$this->checkAction('/crm/customer/delete','删除客户')) return;
        $id = $this->request->param('id', 0);
        $result = (new CustomerService())->delete($id, $this->getUserId());
        if ($result['success']) return $this->renderSuccess([], '删除成功');
        return $this->renderError($result['error']);
    }

    public function claim()
    {
        if(!$this->checkAction('/crm/customer/claim','认领客户')) return;
        $id = $this->request->param('id', 0);
        $result = (new CustomerService())->claim($id, $this->getUserId());
        if ($result['success']) return $this->renderSuccess([], '认领成功');
        return $this->renderError($result['error']);
    }

    public function release()
    {
        if(!$this->checkAction('/crm/customer/release','释放客户')) return;
        $id = $this->request->param('id', 0);
        $result = (new CustomerService())->release($id, $this->getUserId());
        if ($result['success']) return $this->renderSuccess([], '已释放到公海');
        return $this->renderError($result['error']);
    }

    public function changeStatus()
    {
        if(!$this->checkAction('/crm/customer/changeStatus','变更状态')) return;
        $id = $this->request->param('id', 0);
        $status = $this->request->param('status', 1);
        $result = (new CustomerService())->changeStatus($id, intval($status), $this->getUserId());
        if ($result['success']) return $this->renderSuccess([], '状态更新成功');
        return $this->renderError($result['error']);
    }

    public function analyze()
    {
        if(!$this->checkAction('/crm/customer/analyze','AI分析')) return;
        $id = $this->request->param('id', 0);
        $result = (new CustomerService())->analyze($id);
        if (!$result['success']) return $this->renderError($result['error']);
        return $this->renderSuccess($result['data'], 'AI分析完成');
    }

    public function portrait()
    {
        if(!$this->checkAction('/crm/customer/portrait','AI画像')) return;
        $id = $this->request->param('id', 0);
        $result = (new CustomerService())->portrait($id);
        if (!$result['success']) return $this->renderError($result['error']);
        return $this->renderSuccess($result['data'], '画像已生成');
    }

    public function smartSearch()
    {
        $query = $this->request->param('q', '');
        $result = (new CustomerService())->smartSearch($query);
        if (!$result['success']) return $this->renderError($result['error']);
        return $this->renderSuccess($result['data'], '解析成功');
    }
}
