<?php

declare(strict_types=1);

namespace app\platform\flow;

/**
 * 业务流程接入契约
 *
 * 引擎只认识这个接口，不认识任何具体业务表。
 * 接入一个新业务 = 实现本接口 + 在 config/flow.php 注册。
 */
interface FlowBizInterface
{
    /**
     * 业务摘要（待办卡片、实例列表展示用）
     * 建议返回 title / subtitle / amount / status / extra
     */
    public function snapshot(int $bizId, int $storeId): array;

    /**
     * 业务详情页地址（引擎拼链接用）
     */
    public function detailUrl(int $bizId, int $storeId): string;

    /**
     * 流程变量（网关条件判断用）
     */
    public function variables(int $bizId, int $storeId): array;

    /**
     * 实例启动
     */
    public function onInstanceStart(array $instance): void;

    /**
     * 节点进入
     */
    public function onNodeEnter(array $instance, string $nodeKey): void;

    /**
     * 节点完成（节点上所有任务都完成时触发一次）
     * 复杂业务逻辑、状态同步放这里；引擎不直接写业务表
     */
    public function onNodeComplete(array $instance, string $nodeKey, array $task): void;

    /**
     * 实例结束：approved / rejected / cancelled / terminated
     */
    public function onInstanceEnd(array $instance, string $result): void;
}
