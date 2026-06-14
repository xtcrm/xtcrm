<?php
namespace mpp\crm\backend;

use app\platform\backend\BaseController;
use mpp\crm\service\ProductService;

class Product extends BaseController
{
    protected $methodRules = [
        'lists' => 'GET', 'select' => 'GET', 'detail' => 'GET',
        'add' => 'POST', 'edit' => 'POST', 'delete' => 'POST',
    ];

    protected function getUserId(): int
    {
        return $this->store['store_user_id'] ?? ($this->store['uid'] ?? 0);
    }

    public function lists()
    {
        if(!$this->checkAction('/crm/product/lists','产品列表')) return;
        $result = (new ProductService())->getList($this->request->param());
        return $this->renderSuccess($result['data']);
    }

    public function select()
    {
        if(!$this->checkAction('/crm/product/select','产品查询')) return;
        $result = (new ProductService())->select();
        return $this->renderSuccess($result['data']);
    }

    public function detail()
    {
        if(!$this->checkAction('/crm/product/detail','产品详情')) return;
        $id = $this->request->param('id', 0);
        $result = (new ProductService())->detail($id);
        if (!$result['success']) return $this->renderError($result['error']);
        return $this->renderSuccess($result['data']);
    }

    public function add()
    {
        if(!$this->checkAction('/crm/product/add','新增产品')) return;
        $result = (new ProductService())->add($this->postData(), $this->getUserId());
        if ($result['success']) return $this->renderSuccess($result['data'], '添加成功');
        return $this->renderError($result['error']);
    }

    public function edit()
    {
        if(!$this->checkAction('/crm/product/edit','编辑产品')) return;
        $id = $this->request->param('id', 0);
        $result = (new ProductService())->edit($id, $this->postData(), $this->getUserId());
        if ($result['success']) return $this->renderSuccess([], '更新成功');
        return $this->renderError($result['error']);
    }

    public function delete()
    {
        if(!$this->checkAction('/crm/product/delete','删除产品')) return;
        $id = $this->request->param('id', 0);
        $result = (new ProductService())->delete($id, $this->getUserId());
        if ($result['success']) return $this->renderSuccess([], '删除成功');
        return $this->renderError($result['error']);
    }
}
