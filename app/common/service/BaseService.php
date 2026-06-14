<?php

declare (strict_types=1);

namespace app\common\service;

use cores\traits\ErrorTrait;
use think\facade\Request;

/**
 * 系统服务基础类
 * Class BaseService
 * @package app\common\service
 */
class BaseService
{
    use ErrorTrait;

    // 请求管理类
    /* @var $request \cores\Request */
    protected $request;

    // 当前访问的租户ID
    protected $storeId;

    /**
     * 构造方法
     * BaseService constructor.
     */
    public function __construct()
    {
        // 请求管理类
        $this->request = Request::instance();
        // 获取当前操作的租户ID
        $this->getStoreId();
        // 执行子类的构造方法
        $this->initialize();
    }

    /**
     * 构造方法 (供继承的子类使用)
     */
    protected function initialize()
    {
    }

    /**
     * 获取当前操作的租户ID
     * @return int|null
     */
    protected function getStoreId(): ?int
    {
        if (empty($this->storeId)) {
            $this->storeId = $this->request->storeId();
        }
        return $this->storeId;
    }
}
