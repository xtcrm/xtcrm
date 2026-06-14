<?php
namespace mpp\crm\api;

use mpp\crm\service\ProductService;

class Product extends Base
{
    protected $methodRules = ['lists'=>'GET','detail'=>'GET','select'=>'GET'];

    public function lists()
    {
        $params = $this->request->param();
        $params['store_id'] = $this->storeId;
        $result = (new ProductService())->getList($params);
        return $this->renderSuccess($result['data'] ?: []);
    }

    public function select()
    {
        $result = (new ProductService())->select();
        return $this->renderSuccess($result['data'] ?: []);
    }
}
