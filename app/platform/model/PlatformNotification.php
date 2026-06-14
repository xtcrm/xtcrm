<?php

declare (strict_types=1);

namespace app\platform\model;

use cores\BaseModel;

/**
 * 平台站内通知模型（platform_notification 表）
 */
class PlatformNotification extends BaseModel
{
    protected $name = 'platform_notification';
    protected $pk   = 'id';
    protected $createTime = 'create_time';
    protected $updateTime = false;

    public static function createOne(array $data): int
    {
        return (int)static::create($data)->id;
    }

    public static function markRead(int $id, int $userId): void
    {
        static::where('id', $id)->where('user_id', $userId)->update(['is_read' => 1]);
    }

    public static function markAllRead(int $userId): void
    {
        static::where('user_id', $userId)->where('is_read', 0)->update(['is_read' => 1]);
    }

    public static function unreadCount(int $userId): int
    {
        return (int)static::where('user_id', $userId)->where('is_read', 0)->count();
    }

    public static function page(int $userId, int $page = 1, int $limit = 20, string $type = ''): array
    {
        $query = static::where('user_id', $userId);
        if (!empty($type)) $query->where('type', $type);

        $total = (int)(clone $query)->count();
        $list  = $query->order('id', 'desc')->page($page, $limit)->select()->toArray();
        $unread = self::unreadCount($userId);

        return compact('list', 'total', 'unread');
    }

    public static function deleteOne(int $id, int $userId): void
    {
        static::where('id', $id)->where('user_id', $userId)->delete();
    }

    public static function cleanup(int $days = 90): int
    {
        $deadline = time() - $days * 86400;
        return (int)static::where('create_time', '<', $deadline)->delete();
    }
}
