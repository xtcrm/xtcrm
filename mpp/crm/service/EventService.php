<?php
namespace mpp\crm\service;

use mpp\crm\model\EventLog;
use app\platform\BaseService;

/**
 * 事件记录服务
 * 所有写操作在 Service 方法内调用，参与调用方的事务
 */
class EventService extends BaseService
{
    /**
     * 记录一条业务事件
     * 不开启自己的事务——由调用方的事务包裹，保证原子性
     */
    public static function record(string $eventType, string $targetType, int $targetId, array $data = [], ?int $actorUserId = null, ?int $projectId = null, ?int $storeId = null): void
    {
        $instance = new static();
        $log = new EventLog();
        $log->event_type = $eventType;
        $log->target_type = $targetType;
        $log->target_id = $targetId;
        $log->project_id = $projectId ?? 0;
        $log->actor_user_id = $actorUserId;
        $log->data = json_encode($data, JSON_UNESCAPED_UNICODE);
        $log->create_time = time();
        $log->store_id = $storeId ?? $instance->storeId ?? 0;
        $log->save();

        // 客户事件自动投影到统一触点（时间轴读 crm_touchpoint）
        if ($targetType === 'customer' && $targetId > 0) {
            TouchpointService::projectCustomerEvent(
                (int)$log->id,
                (int)$log->store_id,
                $targetId,
                $eventType,
                $data,
                $actorUserId,
                $projectId
            );
        }
    }
}
