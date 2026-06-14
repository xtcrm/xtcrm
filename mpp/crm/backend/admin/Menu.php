<?php

declare (strict_types=1);

namespace mpp\crm\backend\admin;

use app\platform\backend\BaseController;
use app\platform\model\Menu as MenuModel;

/**
 * CRM 菜单控制器
 *
 * 路由：/crm.admin.menu/list
 */
class Menu extends BaseController
{
    protected $methodRules = [
        'list' => 'GET',
    ];

    /**
     * 菜单列表
     */
    public function list()
    {
        $list = MenuModel::withoutGlobalScope()
            ->where('fun_type', 'crm')
            ->order(['sort' => 'asc', 'create_time' => 'asc'])
            ->select()
            ->toArray();

        return $this->renderSuccess(compact('list'));
    }
}
