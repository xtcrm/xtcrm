<?php
namespace mpp\crm\backend;

use app\platform\backend\BaseController;
use skills\opencrm\common\ApiKey as ApiKeyModel;

class ApiKey extends BaseController
{
    protected $methodRules = [
        'lists'    => 'GET',
        'generate' => 'POST',
        'delete'   => 'POST',
    ];

    /** API Key 列表 */
    public function lists()
    {
        $list = \think\facade\Db::name('crm_api_key')
            ->where('store_id', $this->storeId)
            ->order('id', 'desc')
            ->field('id, name, api_key, scopes, status, store_user_id, last_used_at, create_time')
            ->select()->toArray();
        // 附带绑定用户的真实姓名（列表可读）
        $userIds = array_unique(array_filter(array_column($list, 'store_user_id')));
        $users = [];
        if (!empty($userIds)) {
            $users = \think\facade\Db::name('store_user')
                ->whereIn('store_user_id', $userIds)
                ->column('real_name', 'store_user_id');
        }
        foreach ($list as &$row) {
            $row['bind_user_name'] = $row['store_user_id'] > 0
                ? ($users[$row['store_user_id']] ?? ('用户#' . $row['store_user_id']))
                : '';
        }
        return $this->renderSuccess(['list' => $list]);
    }

    /** 生成新 Key */
    public function generate()
    {
        if (!$this->checkAction('/crm/apiKey/generate', '生成API密钥')) return;
        $name = $this->request->param('name', '');
        $scopes = $this->request->param('scopes', 'read');
        $storeUserId = (int)$this->request->param('store_user_id', 0);
        $key = ApiKeyModel::generate($this->storeId, $name ?: '未命名', $scopes, $storeUserId);
        return $this->renderSuccess(['api_key' => $key, 'name' => $name ?: '未命名', 'scopes' => $scopes, 'store_user_id' => $storeUserId], '密钥已生成');
    }

    /** 删除 Key */
    public function delete()
    {
        if (!$this->checkAction('/crm/apiKey/delete', '删除API密钥')) return;
        $id = (int)$this->request->param('id', 0);
        \think\facade\Db::name('crm_api_key')->where('id', $id)->where('store_id', $this->storeId)->delete();
        return $this->renderSuccess([], '已删除');
    }
}
