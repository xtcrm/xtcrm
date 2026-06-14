<?php
namespace mpp\crm\api;

use mpp\crm\service\OrderService;

class Order extends Base
{
    protected $methodRules = ['lists'=>'GET','detail'=>'GET','add'=>'POST','edit'=>'POST','delete'=>'POST'];

    public function lists()
    {
        $params = $this->request->param();
        $params['store_id'] = $this->storeId;
        $result = (new OrderService())->getList($params);
        return $this->renderSuccess($result['data'] ?: []);
    }

    public function detail()
    {
        $id = (int)$this->request->param('id', 0);
        $result = (new OrderService())->detail($id);
        return $result['success'] ? $this->renderSuccess($result['data'] ?: []) : $this->renderError($result['error']);
    }
}
