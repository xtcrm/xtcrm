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
    public static function record(string $eventType, string $targetType, int $targetId, array $data = [], ?int $actorUserId = null): void
    {
        $instance = new static();
        $log = new EventLog();
        $log->event_type = $eventType;
        $log->target_type = $targetType;
        $log->target_id = $targetId;
        $log->actor_user_id = $actorUserId;
        $log->data = json_encode($data, JSON_UNESCAPED_UNICODE);
        $log->create_time = time();
        $log->store_id = $instance->storeId;
        $log->save();
    }
}
