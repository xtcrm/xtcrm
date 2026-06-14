<?php

declare (strict_types=1);

namespace mpp\crm\backend;

use app\platform\backend\BaseController;
use app\platform\model\Region as RegionModel;

/**
 * CRM 地区控制器
 *
 * 路由：/crm.region/tree
 */
class Region extends BaseController
{
    protected $methodRules = [
        'all'  => 'GET',
        'tree' => 'GET',
    ];

    public function all()
    {
        $list = RegionModel::getCacheAll();
        return $this->renderSuccess(compact('list'));
    }

    public function tree()
    {
        $list = RegionModel::getCacheTree();
        return $this->renderSuccess(compact('list'));
    }
}
