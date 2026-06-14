<?php

declare (strict_types=1);

namespace app\platform\tenant;

use cores\BaseModel;

/**
 * 多租户解析器
 *
 * 从请求上下文、CLI 参数、或手动注入中解析当前租户 ID。
 * Web 请求自动从 app()->request->storeId() 读取，CLI 需要手动 set()。
 */
class TenantResolver
{
    /** @var int|null 手动注入的租户 ID */
    private static $forcedId = null;

    /**
     * 从当前上下文解析租户 ID
     */
    public static function resolve(): ?int
    {
        // 手动注入优先（CLI 场景）
        if (self::$forcedId !== null) {
            return self::$forcedId;
        }

        // 从 Request 读取（Web 场景）
        try {
            $storeId = app()->request->storeId();
            if (!empty($storeId)) {
                return (int)$storeId;
            }
        } catch (\Throwable $e) {
            // 可能不在请求上下文中
        }

        // 从 BaseModel 静态属性读取
        if (!empty(BaseModel::$storeId)) {
            return (int)BaseModel::$storeId;
        }

        return null;
    }

    /**
     * 手动设置当前租户（定时任务 / CLI 命令场景）
     *
     * @param int $storeId
     */
    public static function set(int $storeId): void
    {
        self::$forcedId = $storeId;
        BaseModel::$storeId = $storeId;
    }

    /**
     * 获取当前租户信息
     */
    public static function current(): ?array
    {
        $storeId = self::resolve();
        if (empty($storeId)) return null;

        try {
            $store = \think\facade\Db::name('store')
                ->where('store_id', $storeId)
                ->find();
            return $store ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * 检查当前上下文是否有租户
     */
    public static function hasTenant(): bool
    {
        return self::resolve() !== null;
    }

    /**
     * 清除手动注入（请求结束时调用）
     */
    public static function clear(): void
    {
        self::$forcedId = null;
    }
}
