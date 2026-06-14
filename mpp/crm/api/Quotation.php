<?php
namespace mpp\crm\api;

use mpp\crm\service\QuotationService;

class Quotation extends Base
{
    protected $methodRules = ['lists'=>'GET','detail'=>'GET','add'=>'POST','edit'=>'POST','delete'=>'POST'];

    public function lists()
    {
        $params = $this->request->param();
        $params['store_id'] = $this->storeId;
        $result = (new QuotationService())->getList($params);
        return $this->renderSuccess($result['data'] ?: []);
    }

    public function detail()
    {
        $id = (int)$this->request->param('id', 0);
        $result = (new QuotationService())->detail($id);
        return $result['success'] ? $this->renderSuccess($result['data'] ?: []) : $this->renderError($result['error']);
    }

    public function add()
    {
        $data = $this->postData();
        $result = (new QuotationService())->add($data, $this->getUserId());
        return $result['success'] ? $this->renderSuccess($result['data'] ?: [], '添加成功') : $this->renderError($result['error']);
    }

    public function edit()
    {
        $data = $this->postData();
        $id = (int)($data['id'] ?? 0);
        $result = (new QuotationService())->edit($id, $data, $this->getUserId());
        return $result['success'] ? $this->renderSuccess($result['data'] ?: [], '保存成功') : $this->renderError($result['error']);
    }

    public function delete()
    {
        $id = (int)$this->request->post('id', 0);
        $result = (new QuotationService())->delete($id, $this->getUserId());
        return $result['success'] ? $this->renderSuccess('删除成功') : $this->renderError($result['error']);
    }
}
