<?php
declare(strict_types=1);

namespace mpp\crm\api;

use mpp\crm\service\CustomerService;

/**
 * 移动端客户管理
 * URL: /api/crm.customer/lists
 */
class Customer extends Base
{
    protected $methodRules = [
        'lists'     => 'GET', 'select' => 'GET', 'detail' => 'GET',
        'poolLists' => 'GET', 'checkName' => 'GET',
        'add'       => 'POST', 'edit' => 'POST',
    ];

    /** GET /api/crm.customer/lists */
    public function lists()
    {
        $params = $this->request->param();
        $params['store_id'] = $this->storeId;
        $result = (new CustomerService())->getList($params);
        return $this->renderSuccess($result['data']);
    }

    /** GET /api/crm.customer/select */
    public function select()
    {
        $result = (new CustomerService())->select();
        return $this->renderSuccess($result['data']);
    }

    /** GET /api/crm.customer/detail */
    public function detail()
    {
        $id = (int)$this->request->param('id', 0);
        $from = $this->request->param('from', 'index');
        if ($id <= 0) return $this->renderError('参数错误：缺少客户ID');
        $result = (new CustomerService())->detail($id, $from);
        if (!$result['success']) return $this->renderError($result['error']);
        return $this->renderSuccess($result['data']);
    }

    /** GET /api/crm.customer/poolLists */
    public function poolLists()
    {
        $params = $this->request->param();
        $params['store_id'] = $this->storeId;
        $result = (new CustomerService())->getPoolList($params);
        return $this->renderSuccess($result['data']);
    }

    /** GET /api/crm.customer/checkName */
    public function checkName()
    {
        $name = $this->request->param('customer_name', '');
        $excludeId = (int)$this->request->param('exclude_id', 0);
        if (empty($name)) return $this->renderSuccess(['exists' => false]);
        $exists = \mpp\crm\model\Customer::where('customer_name', $name)
            ->where('is_delete', 0)
            ->when($excludeId > 0, function ($q) use ($excludeId) {
                $q->where('id', '<>', $excludeId);
            })->count() > 0;
        return $this->renderSuccess(['exists' => $exists]);
    }

    /** POST /api/crm.customer/add */
    public function add()
    {
        $data = $this->postData();
        if (empty($data['customer_name'])) return $this->renderError('客户名称不能为空');
        $userId = $this->getUserId();
        $data['creator_user_id'] = $userId;
        // 兜底：前端未传或传 0 时，默认负责人 = 当前用户
        if (empty($data['owner_user_id'])) {
            $data['owner_user_id'] = $userId;
        }
        $result = (new CustomerService())->add($data, $this->getUserId());
        if ($result['success']) return $this->renderSuccess($result['data'], '添加成功');
        return $this->renderError($result['error']);
    }

    /** POST /api/crm.customer/edit */
    public function edit()
    {
        $data = $this->postData();
        $id = (int)($data['id'] ?? 0);
        if ($id <= 0) return $this->renderError('参数错误：缺少客户ID');
        $result = (new CustomerService())->edit($id, $data, $this->getUserId());
        if ($result['success']) return $this->renderSuccess([], '保存成功');
        return $this->renderError($result['error']);
    }
}
