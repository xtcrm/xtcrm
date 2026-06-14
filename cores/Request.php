<?php

namespace cores;

// 应用请求对象类
class Request extends \think\Request
{
    // 全局过滤规则
    protected $filter = ['my_trim', 'my_htmlspecialchars', 'filter_emoji'];

    // 当前的租户ID (仅在访问store模块和api模块时有值)
    protected $storeId = null;

    // 当前的供应商ID (供应商用户登录后才有值)
    protected $supplierId = null;

    /**
     * 获取当前的租户ID (仅在访问store模块和api模块时有值)
     * @return int|null
     */
    public function storeId(): ?int
    {
        return $this->storeId;
    }

    /**
     * 设置当前租户ID
     * @param int $storeId
     * @return $this
     */
    public function setStoreId(int $storeId): Request
    {
        $this->storeId = $storeId;
        return $this;
    }

    /**
     * 获取当前供应商ID (供应商用户登录后才有值)
     * @return int|null
     */
    public function supplierId(): ?int
    {
        return $this->supplierId;
    }

    /**
     * 设置当前供应商ID
     * @param int $supplierId
     * @return $this
     */
    public function setSupplierId(int $supplierId): Request
    {
        $this->supplierId = $supplierId;
        return $this;
    }
}
