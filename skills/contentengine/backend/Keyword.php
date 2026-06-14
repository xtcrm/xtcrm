<?php
namespace skills\contentengine\backend;

use app\platform\backend\BaseController;
use skills\contentengine\service\KeywordService;

class Keyword extends BaseController
{
    protected $methodRules = [
        'lists' => 'GET', 'add' => 'POST', 'delete' => 'POST', 'batchImport' => 'POST',
    ];

    public function lists()
    {
        if (!$this->checkAction('/crm.keyword/add', '查看关键词')) return;
        $result = (new KeywordService())->getList($this->request->param());
        return $this->renderSuccess($result['data']);
    }

    public function add()
    {
        if (!$this->checkAction('/crm.keyword/add', '添加关键词')) return;
        $data = $this->postData();
        $result = (new KeywordService())->add($data);
        if ($result['success']) return $this->renderSuccess($result['data'], '添加成功');
        return $this->renderError($result['error']);
    }

    public function delete()
    {
        if (!$this->checkAction('/crm.keyword/delete', '删除关键词')) return;
        $id = $this->request->param('id', 0);
        $result = (new KeywordService())->delete((int)$id);
        if ($result['success']) return $this->renderSuccess([], '删除成功');
        return $this->renderError($result['error']);
    }

    public function batchImport()
    {
        if (!$this->checkAction('/crm.keyword/add', '添加关键词')) return;
        $text = $this->request->param('text', '');
        $type = $this->request->param('type', 2);
        $keywords = array_filter(array_map('trim', explode("\n", $text)));
        if (empty($keywords)) return $this->renderError('请输入关键词');
        $result = (new KeywordService())->batchImport($keywords, (int)$type);
        return $this->renderSuccess($result['data'], "导入完成：新增{$result['data']['added']}，跳过{$result['data']['skipped']}");
    }
}
