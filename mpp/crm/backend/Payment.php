<?php
namespace mpp\crm\backend;

use app\platform\backend\BaseController;
use mpp\crm\service\PaymentService;

/**
 * 项目收款
 *
 * URL：s=/platform/crm.payment/{action}
 */
class Payment extends BaseController
{
    protected $methodRules = [
        'lists' => 'GET',
        'add' => 'POST',
        'delete' => 'POST',
    ];

    protected function getUserId(): int
    {
        return (int)($this->store['store_user_id'] ?? ($this->store['uid'] ?? 0));
    }

    public function lists()
    {
        if (!$this->checkAction('/crm/payment/lists', '查询收款')) return;
        $result = (new PaymentService())->getList(
            (int)$this->request->param('project_id', 0),
            (int)$this->request->param('order_id', 0),
            (int)$this->storeId
        );
        return $this->renderSuccess($result['data']);
    }

    public function add()
    {
        if (!$this->checkAction('/crm/payment/add', '登记收款')) return;
        $result = (new PaymentService())->add($this->postData(), (int)$this->storeId, $this->getUserId());
        if (!$result['success']) return $this->renderError($result['error']);
        return $this->renderSuccess($result['data'], '收款已登记');
    }

    public function delete()
    {
        if (!$this->checkAction('/crm/payment/delete', '删除收款')) return;
        $id = (int)$this->request->param('id', 0);
        $result = (new PaymentService())->delete($id, (int)$this->storeId, $this->getUserId());
        if (!$result['success']) return $this->renderError($result['error']);
        return $this->renderSuccess([], '已冲正');
    }
}
