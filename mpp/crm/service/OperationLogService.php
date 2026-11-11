<?php
namespace mpp\crm\service;

/**
 * 操作日志工具
 */
class OperationLogService
{
    public static function log(
        string $action,
        string $target,
        int $targetId,
        string $summary,
        array $detail = [],
        ?int $overrideUserId = null,
        string $overrideUserName = '',
        ?int $overrideStoreId = null
    ): void {
        try {
            $request = request();
            $storeId = $overrideStoreId ?? $request->storeId();

            $userId = $overrideUserId;
            $userName = $overrideUserName;

            if ($userId === null) {
                $store = \app\platform\auth\TokenService::verify();
                if (!empty($store)) {
                    $userId = $store['store_user_id'] ?? ($store['uid'] ?? 0);
                    $userName = $store['user_name'] ?? '';
                }
            }

            // 兜底：token 中无 user_name，查数据库
            if (empty($userName) && !empty($userId)) {
                $user = \think\facade\Db::name('store_user')
                    ->where('store_user_id', $userId)
                    ->value('user_name');
                $userName = $user ?: '';
            }

            $model = new \mpp\crm\model\OperationLog();
            $model->save([
                'store_id'   => $storeId ?: 0,
                'user_id'    => $userId ?: 0,
                'user_name'  => $userName,
                'action'     => $action,
                'module'     => 'crm',
                'target'     => $target,
                'target_id'  => $targetId,
                'summary'    => mb_substr($summary, 0, 500),
                'detail'     => !empty($detail) ? json_encode($detail, JSON_UNESCAPED_UNICODE) : '',
                'ip'         => $request->ip() ?: '',
                'user_agent' => mb_substr($request->header('User-Agent', ''), 0, 500),
                'create_time' => time(),
            ]);
        } catch (\Throwable $e) {
            // ignore
        }
    }

    public static function logAdd(string $target, int $targetId, string $name): void
    {
        self::log('add', $target, $targetId, "新建{$target}「{$name}」");
    }

    public static function logEdit(string $target, int $targetId, string $name, array $changes = []): void
    {
        $detail = !empty($changes) ? ['changes' => $changes] : [];
        self::log('edit', $target, $targetId, "编辑{$target}「{$name}」", $detail);
    }

    public static function logDelete(string $target, int $targetId, string $name): void
    {
        self::log('delete', $target, $targetId, "删除{$target}「{$name}」");
    }
}
