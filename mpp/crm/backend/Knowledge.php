<?php
namespace mpp\crm\backend;

use app\platform\backend\BaseController;
use mpp\crm\service\KnowledgeService;

class Knowledge extends BaseController
{
    protected $methodRules = [
        'lists' => 'GET', 'categories' => 'GET',
        'add' => 'POST', 'edit' => 'POST', 'delete' => 'POST',
    ];

    protected function getUserId(): int
    {
        return $this->store['store_user_id'] ?? ($this->store['uid'] ?? 0);
    }

    public function lists()
    {
        if(!$this->checkAction('/crm/knowledge/lists','知识列表')) return;
        $result = (new KnowledgeService())->getList($this->request->param());
        // content 是富文本 HTML，全局 htmlspecialchars 编码了，读取时还原
        if (!empty($result['data']['list']['data'])) {
            foreach ($result['data']['list']['data'] as &$row) {
                if (!empty($row['content'])) {
                    $row['content'] = htmlspecialchars_decode($row['content'], ENT_COMPAT);
                }
            }
        }
        return $this->renderSuccess($result['data']);
    }

    public function categories()
    {
        $result = (new KnowledgeService())->categories();
        return $this->renderSuccess($result['data']);
    }

    public function add()
    {
        if(!$this->checkAction('/crm/knowledge/add','新增知识')) return;
        $data = $this->postData();
        // content 是富文本 HTML，不能走全局 htmlspecialchars 编码
        if (!empty($data['content'])) {
            $data['content'] = htmlspecialchars_decode($data['content'], ENT_COMPAT);
        }
        $result = (new KnowledgeService())->add($data, $this->getUserId());
        if ($result['success']) return $this->renderSuccess($result['data'], '添加成功');
        return $this->renderError($result['error']);
    }

    public function edit()
    {
        if(!$this->checkAction('/crm/knowledge/edit','编辑知识')) return;
        $id = $this->request->param('id', 0);
        $data = $this->postData();
        // content 是富文本 HTML，不能走全局 htmlspecialchars 编码
        if (!empty($data['content'])) {
            $data['content'] = htmlspecialchars_decode($data['content'], ENT_COMPAT);
        }
        $result = (new KnowledgeService())->edit($id, $data, $this->getUserId());
        if ($result['success']) return $this->renderSuccess([], '更新成功');
        return $this->renderError($result['error']);
    }

    public function delete()
    {
        if(!$this->checkAction('/crm/knowledge/delete','删除知识')) return;
        $id = $this->request->param('id', 0);
        $result = (new KnowledgeService())->delete($id, $this->getUserId());
        if ($result['success']) return $this->renderSuccess([], '删除成功');
        return $this->renderError($result['error']);
    }
}
