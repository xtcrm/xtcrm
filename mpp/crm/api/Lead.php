<?php
declare(strict_types=1);

namespace mpp\crm\api;

use mpp\crm\service\LeadService;

/**
 * 移动端线索管理
 * URL: /api/crm.lead/lists
 */
class Lead extends Base
{
    protected $methodRules = [
        'lists'   => 'GET', 'detail' => 'GET',
        'add'     => 'POST', 'edit' => 'POST', 'convert' => 'POST',
    ];

    /** GET /api/crm.lead/lists */
    public function lists()
    {
        $params = $this->request->param();
        $params['store_id'] = $this->storeId;
        $result = (new LeadService())->getList($params);
        return $this->renderSuccess($result['data']);
    }

    /** GET /api/crm.lead/detail */
    public function detail()
    {
        $id = (int)$this->request->param('id', 0);
        if ($id <= 0) return $this->renderError('参数错误：缺少线索ID');
        $result = (new LeadService())->detail($id);
        if (!$result['success']) return $this->renderError($result['error']);
        return $this->renderSuccess($result['data']);
    }

    /** POST /api/crm.lead/add */
    public function add()
    {
        $data = $this->postData();
        if (empty($data['lead_name'])) return $this->renderError('线索名称不能为空');
        $data['creator_user_id'] = $this->getUserId();
        $result = (new LeadService())->add($data, $this->getUserId());
        if ($result['success']) return $this->renderSuccess($result['data'], '添加成功');
        return $this->renderError($result['error']);
    }

    /** POST /api/crm.lead/edit */
    public function edit()
    {
        $data = $this->postData();
        $id = (int)($data['id'] ?? 0);
        if ($id <= 0) return $this->renderError('参数错误：缺少线索ID');
        $result = (new LeadService())->edit($id, $data, $this->getUserId());
        if ($result['success']) return $this->renderSuccess([], '保存成功');
        return $this->renderError($result['error']);
    }

    /** POST /api/crm.lead/convert */
    public function convert()
    {
        $id = (int)$this->request->post('id', 0);
        if ($id <= 0) return $this->renderError('参数错误：缺少线索ID');
        $result = (new LeadService())->convert($id, $this->getUserId());
        if ($result['success']) return $this->renderSuccess($result['data'], '转化成功');
        return $this->renderError($result['error']);
    }
}
