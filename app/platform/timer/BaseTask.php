<?php

declare (strict_types=1);

namespace app\platform\timer;

use think\facade\Cache;

/**
 * 平台定时任务基类
 *
 * 封装定时任务通用模式：setInterval 防重、租户上下文、异常处理。
 * 参考 CRM 的 CrmPool/CrmInsight 任务模式。
 */
abstract class BaseTask
{
    /** @var string 任务唯一标识（用于缓存防重） */
    protected $taskKey;

    /** @var int 防重间隔（秒），默认 86400 = 1天 */
    protected $taskExpire = 86400;

    /** @var int 当前租户 ID */
    protected $storeId;

    /**
     * 任务入口
     * @param array $param 参数 ['storeId' => int]
     */
    abstract public function handle(array $param): void;

    /**
     * 防重执行：同一租户的同一任务，在 $taskExpire 秒内只执行一次
     *
     * @param int      $storeId 租户 ID
     * @param string   $key     任务标识
     * @param int      $expire  防重缓存过期时间（秒）
     * @param callable $fn      要执行的任务回调
     */
    protected function setInterval(int $storeId, string $key, int $expire, callable $fn): void
    {
        $cacheKey = "platform_task:{$storeId}:{$key}";

        if (Cache::has($cacheKey)) return;

        try {
            $fn();
        } catch (\Throwable $e) {
            // 任务异常不阻断，记录日志
            log_record([
                'task'   => $key,
                'store'  => $storeId,
                'error'  => $e->getMessage(),
                'file'   => $e->getFile() . ':' . $e->getLine(),
            ], 'error');
            return;
        }

        Cache::set($cacheKey, time(), $expire);
    }
}
