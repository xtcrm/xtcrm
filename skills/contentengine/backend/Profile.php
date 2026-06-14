<?php
namespace skills\contentengine\backend;

use app\platform\backend\BaseController;
use skills\contentengine\service\ProfileService;

class Profile extends BaseController
{
    protected $methodRules = ['detail' => 'GET', 'edit' => 'POST'];

    public function detail()
    {
        if (!$this->checkAction('/crm.profile/detail', '查看档案')) return;
        $result = (new ProfileService())->detail($this->storeId);
        return $this->renderSuccess($result['data']);
    }

    public function edit()
    {
        if (!$this->checkAction('/crm.profile/edit', '编辑档案')) return;
        $data = $this->postData();
        $result = (new ProfileService())->save($data, $this->storeId);
        if ($result['success']) return $this->renderSuccess([], '保存成功');
        return $this->renderError($result['error']);
    }
}
