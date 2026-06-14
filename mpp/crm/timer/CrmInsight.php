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
    protected $taskExpire = 86400;
    private $storeId;

    public function handle(array $param)
    {
        ['storeId' => $this->storeId] = $param;
        $this->setInterval($this->storeId, $this->taskKey, $this->taskExpire, function () {
            $sid = $this->storeId;
            Log::info("CrmInsight[{$sid}] started");

            try {
                // 1. 投影刷新
                $projStats = (new ProjectionService())->refresh($sid);
                Log::info("CrmInsight[{$sid}] projections: " . implode(', ', $projStats));

                // 2. 洞察扫描
                $insightStats = (new InsightService())->dailyScan($sid);
                Log::info("CrmInsight[{$sid}] insights: " . json_encode($insightStats, JSON_UNESCAPED_UNICODE));

                // 3. 通知生成
                $notifyCount = (new NotificationService())->generateDigest($sid);
                Log::info("CrmInsight[{$sid}] notifications: {$notifyCount}");

                // 4. 清理旧通知
                $cleaned = (new NotificationService())->cleanup();
                Log::info("CrmInsight[{$sid}] cleaned notifications: {$cleaned}");

            } catch (\Throwable $e) {
                Log::error("CrmInsight[{$sid}] ERROR: " . $e->getMessage());
            }
        });
    }
}
