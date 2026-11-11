<?php
namespace mpp\crm\timer;

use app\timer\controller\Task;
use mpp\crm\service\ProjectionService;
use mpp\crm\service\InsightService;
use mpp\crm\service\NotificationService;
use think\facade\Log;

/**
 * 定时任务：CRM 洞察引擎
 */
class CrmInsight extends Task
{
    private $taskKey = 'CrmInsight';
    protected $taskExpire = 3600;
    private $storeId;

    public function handle(array $param)
    {
        ['storeId' => $this->storeId] = $param;

        // 节流：每小时最多执行一次，防止定时器 1s tick 高频空转写爆 binlog
        // （历史事故：调试期去掉本缓存后，cleanup() 每 tick 跑 ~86400 次/天，9 天写满 9.6G binlog）
        $this->setInterval($this->storeId, $this->taskKey, $this->taskExpire, function () {
            $sid = $this->storeId;

            try {
                // 1. 投影刷新
                (new ProjectionService())->refresh($sid);

                // 2. 洞察扫描（含需求规则扫描）
                (new InsightService())->dailyScan($sid);

                // 3. 通知生成
                (new NotificationService())->generateDigest($sid);

                // 4. 清理旧通知（显式传 storeId：定时进程无请求租户上下文，全局 scope 不生效，必须手动隔离）
                (new NotificationService())->cleanup($sid);

            } catch (\Throwable $e) {
                Log::error("CrmInsight[{$sid}] ERROR: " . $e->getMessage());
            }
        });
    }
}
