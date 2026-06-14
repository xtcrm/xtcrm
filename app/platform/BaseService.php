<?php

declare (strict_types=1);

namespace app\platform;

use cores\traits\ErrorTrait;
use think\facade\Request;

/**
 * 平台 Service 基类
 *
 * 所有业务模块的 Service 继承此类。
 * 初期内部委托给 app\common\service\BaseService，逐步将能力下沉到自身。
 */
abstract class BaseService
{
    use ErrorTrait;

    /** @var \cores\Request */
    protected $request;

    /** @var int 当前租户 ID */
    protected $storeId;

    /**
     * 构造方法
     */
    public function __construct()
    {
        $this->request = Request::instance();
        $this->getStoreId();
        $this->initialize();
    }

    /**
     * 子类初始化钩子
     */
    protected function initialize()
    {
    }

    /**
     * 获取当前操作的租户 ID
     */
    protected function getStoreId(): ?int
    {
        if (empty($this->storeId)) {
            $this->storeId = $this->request->storeId();
        }
        return $this->storeId;
    }

    /**
     * 链式设置租户 ID（用于 CLI/定时任务场景手动指定）
     * @return static
     */
    public function forStore(int $storeId)
    {
        $this->storeId = $storeId;
        return $this;
    }
}
