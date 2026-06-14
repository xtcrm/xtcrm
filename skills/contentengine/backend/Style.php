<?php
namespace skills\contentengine\backend;

use app\platform\backend\BaseController;
use skills\contentengine\service\StyleService;

class Style extends BaseController
{
    protected $methodRules = [
        'lists' => 'GET', 'copyFromPreset' => 'POST', 'add' => 'POST', 'edit' => 'POST', 'delete' => 'POST',
    ];

    public function lists()
    {
        if (!$this->checkAction('/crm.profile/detail', '查看档案')) return;
        $result = (new StyleService())->listAll($this->storeId);
        return $this->renderSuccess($result['data']);
    }

    public function copyFromPreset()
    {
        $presetKey = $this->request->param('preset_key', '');
        if (empty($presetKey)) return $this->renderError('请选择预设');
        $result = (new StyleService())->copyFromPreset($presetKey, $this->storeId);
        if (!$result['success']) return $this->renderError($result['error']);
        return $this->renderSuccess($result['data'], '复制成功');
    }

    public function add()
    {
        $data = $this->postData();
        $result = (new StyleService())->add($data, $this->storeId);
        if (!$result['success']) return $this->renderError($result['error']);
        return $this->renderSuccess($result['data'], '添加成功');
    }

    public function edit()
    {
        $data = $this->postData();
        $result = (new StyleService())->edit($data, $this->storeId);
        if (!$result['success']) return $this->renderError($result['error']);
        return $this->renderSuccess([], '更新成功');
    }

    public function delete()
    {
        $id = $this->request->param('id', 0);
        $result = (new StyleService())->delete((int)$id, $this->storeId);
        if (!$result['success']) return $this->renderError($result['error']);
        return $this->renderSuccess([], '删除成功');
    }
}
