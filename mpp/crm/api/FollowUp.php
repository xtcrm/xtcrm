<?php
declare(strict_types=1);

namespace mpp\crm\api;

use mpp\crm\service\FollowUpService;

/**
 * 移动端跟进记录
 * URL: /api/crm.followUp/lists
 */
class FollowUp extends Base
{
    protected $methodRules = [
        'lists' => 'GET',
        'add'   => 'POST',
    ];

    /** GET /api/crm.followUp/lists */
    public function lists()
    {
        $customerId = (int)$this->request->param('customer_id', 0);
        $result = (new FollowUpService())->getList($customerId);
        return $this->renderSuccess($result['data'] ?: []);
    }

    /** POST /api/crm.followUp/add */
    public function add()
    {
        $data = $this->postData();
        if (empty($data['customer_id'])) {
            return $this->renderError('参数错误：缺少客户ID');
        }
        $result = (new FollowUpService())->add($data, $this->getUserId());
        if ($result['success']) {
            return $this->renderSuccess($result['data'] ?: [], '添加成功');
        }
        return $this->renderError($result['error']);
    }
}
