<?php

namespace mpp\crm\service;

use app\platform\BaseService;

/**
 * 客户日志统一入口（兼容层）
 *
 * 写 crm_event_log，并由 EventService 自动投影到 crm_touchpoint。
 * 新模块（客服单等）可直接调 TouchpointService::record()；
 * 已有调用点继续走这里，不需要改。
 */
class CustomerLogService extends BaseService
{
    /**
     * 记一条客户日志
     *
     * @param int         $customerId   客户ID，<=0 时不写
     * @param string      $eventType    事件类型，如 contact_added
     * @param string      $title        中文说明，详情页直接展示
     * @param array       $data         附加信息（remark 会单独展示）
     * @param int|null    $actorUserId  操作人
     * @param int|null    $storeId      租户ID；CLI/定时任务必须显式传
     * @param int|null    $projectId    关联项目（项目时间线会用到）
     */
    public static function record(
        int $customerId,
        string $eventType,
        string $title,
        array $data = [],
        ?int $actorUserId = null,
        ?int $storeId = null,
        ?int $projectId = null
    ): void {
        if ($customerId <= 0) return;

        $instance = new static();
        $sid = $storeId ?? $instance->storeId;
        if (empty($sid)) return; // 没有租户上下文不落库，避免写进 store_id=0

        $data['title'] = $title;

        // EventService 负责写 crm_event_log，并自动投影到 crm_touchpoint
        EventService::record($eventType, 'customer', $customerId, $data, $actorUserId, $projectId, $sid);
    }
}
