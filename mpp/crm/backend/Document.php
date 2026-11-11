<?php
namespace mpp\crm\backend;

use app\platform\backend\BaseController;
use mpp\crm\service\DocumentService;

class Document extends BaseController
{
    protected $methodRules = [
        'lists' => 'GET',
        'upload' => 'POST',
        'edit' => 'POST',
        'delete' => 'POST',
    ];

    protected function getUserId(): int
    {
        return $this->store['store_user_id'] ?? ($this->store['uid'] ?? 0);
    }

    /** 列表查询 */
    public function lists()
    {
        $filters = [
            'customer_id'  => $this->request->param('customer_id', 0),
            'project_id'   => $this->request->param('project_id', 0),
            'quotation_id' => $this->request->param('quotation_id', 0),
            'order_id'     => $this->request->param('order_id', 0),
            'contract_id'  => $this->request->param('contract_id', 0),
            'category'     => $this->request->param('category', ''),
            'list_rows'    => $this->request->param('list_rows', 15),
        ];
        $result = (new DocumentService())->getList($filters);
        return $this->renderSuccess($result['data']);
    }

    /** 上传文件 */
    public function upload()
    {
        $data = $this->postData();
        $data['store_id'] = $this->storeId;
        $result = (new DocumentService())->upload($data, $this->getUserId());
        if ($result['success']) return $this->renderSuccess($result['data'], '上传成功');
        return $this->renderError($result['error']);
    }

    /** 编辑 */
    public function edit()
    {
        $id = $this->request->param('id', 0);
        $data = $this->postData();
        $result = (new DocumentService())->edit($id, $data);
        if ($result['success']) return $this->renderSuccess([], '更新成功');
        return $this->renderError($result['error']);
    }

    /** 删除 */
    public function delete()
    {
        $id = $this->request->param('id', 0);
        $result = (new DocumentService())->delete($id);
        if ($result['success']) return $this->renderSuccess([], '删除成功');
        return $this->renderError($result['error']);
    }
}
