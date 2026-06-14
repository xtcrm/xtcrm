<?php

declare (strict_types=1);

namespace app\platform\notification;

use app\platform\model\PlatformNotification;

/**
 * 平台站内通知服务
 */
class InAppNotify
{
    public static function create(
        int $userId,
        string $title,
        string $content,
        string $type = 'system',
        string $sourceModule = '',
        int $sourceId = 0,
        int $storeId = 0
    ): int {
        return PlatformNotification::createOne([
            'store_id'      => $storeId,
            'user_id'       => $userId,
            'title'         => $title,
            'content'       => $content,
            'type'          => $type,
            'is_read'       => 0,
            'source_module' => $sourceModule,
            'source_id'     => $sourceId,
            'create_time'   => time(),
        ]);
    }

    public static function markRead(int $notifyId, int $userId): void
    {
        PlatformNotification::markRead($notifyId, $userId);
    }

    public static function markAllRead(int $userId): void
    {
        PlatformNotification::markAllRead($userId);
    }

    public static function unreadCount(int $userId): int
    {
        return PlatformNotification::unreadCount($userId);
    }

    public static function list(int $userId, int $page = 1, int $limit = 20, string $type = ''): array
    {
        return PlatformNotification::page($userId, $page, $limit, $type);
    }

    public static function delete(int $notifyId, int $userId): void
    {
        PlatformNotification::deleteOne($notifyId, $userId);
    }

    public static function cleanup(int $days = 90): int
    {
        return PlatformNotification::cleanup($days);
    }
}
