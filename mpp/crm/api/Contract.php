<?php
namespace mpp\crm\api;

use mpp\crm\service\ContractService;

class Contract extends Base
{
    protected $methodRules = ['lists'=>'GET','detail'=>'GET','add'=>'POST','edit'=>'POST','delete'=>'POST'];

    public function lists()
    {
        $params = $this->request->param();
        $params['store_id'] = $this->storeId;
        $result = (new ContractService())->getList($params);
        return $this->renderSuccess($result['data'] ?: []);
    }

    public function detail()
    {
        $id = (int)$this->request->param('id', 0);
        $result = (new ContractService())->detail($id);
        return $result['success'] ? $this->renderSuccess($result['data'] ?: []) : $this->renderError($result['error']);
    }
}
