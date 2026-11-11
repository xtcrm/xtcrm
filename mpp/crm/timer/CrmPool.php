<?php
namespace mpp\crm\timer;

use app\timer\controller\Task;
use think\facade\Db;
use think\facade\Log;

/**
 * 定时任务：公海自动掉入
 * 每天扫描超过N天未跟进的客户，自动掉入公海
 *
 * 默认关闭（pool_enabled = 0）：新租户不需要公海规则也能用 CRM，
 * 只有到「设置 → 公海规则」显式打开后才会真的动客户归属。
 */
class CrmPool extends Task
{
    private $taskKey = 'CrmPool';
    protected $taskExpire = 3600; 

    private $storeId;

    public function handle(array $param)
    {
        ['storeId' => $this->storeId] = $param;
        $this->setInterval($this->storeId, $this->taskKey, $this->taskExpire, function () {
            //Log::info("CrmPool[{$this->storeId}] started");
            try {
                $count = $this->run();
                //Log::info("CrmPool[{$this->storeId}] moved to pool: {$count}");
            } catch (\Throwable $e) {
                //Log::error("CrmPool[{$this->storeId}] ERROR: " . $e->getMessage());
            }
        });
    }

    /**
     * 扫描超过N天未跟进的客户，自动掉入公海
     */
    public function run(): int
    {
        $storeId = $this->storeId;

        // 公海规则默认关闭：没显式开启就直接跳过（连查询都省了）
        $enabled = Db::name('config')
            ->where('store_id', $storeId)
            ->where('config_type', 'crm_setting')
            ->where('config_name', 'pool_enabled')
            ->value('config_value');
        if (intval($enabled) !== 1) return 0;

        // 读取配置的公海掉入天数，默认180天
        $poolDays = 180;
        $config = Db::name('config')
            ->where('store_id', $storeId)
            ->where('config_type', 'crm_setting')
            ->where('config_name', 'pool_days')
            ->find();
        if ($config && !empty($config['config_value'])) {
            $poolDays = intval($config['config_value']);
        }

        // 如果配置为0，不自动掉入公海
        if ($poolDays <= 0) return 0;

        $deadline = time() - $poolDays * 86400;

        // 查询需要掉入公海的客户
        $customers = Db::name('crm_customer')
            ->where('store_id', $storeId)
            ->where('owner_user_id', '>', 0)
            ->where('is_delete', 0)
            ->where('status', 1)
            ->where(function ($query) use ($deadline) {
                $query->where('last_followup_time', '<', $deadline)
                    ->whereOr('last_followup_time', null)
                    ->whereOr('last_followup_time', 0);
            })
            ->select()
            ->toArray();

        $now = time();
        $count = 0;

        foreach ($customers as $customer) {
            // 最后跟进时间为空但是有创建时间，用创建时间判断
            $lastTime = $customer['last_followup_time'] ?: $customer['create_time'];
            if ($lastTime > $deadline) continue;

            Db::name('crm_customer')
                ->where('id', $customer['id'])
                ->where('store_id', $storeId)
                ->update([
                    'owner_user_id' => 0,
                    'enter_pool_time' => $now,
                    'update_time' => $now,
                ]);

            // 记录认领日志
            Db::name('crm_customer_claim_log')->insert([
                'store_id' => $storeId,
                'customer_id' => $customer['id'],
                'from_user_id' => $customer['owner_user_id'],
                'to_user_id' => 0,
                'action' => 1,
                'remark' => "系统自动掉入公海（超过{$poolDays}天未跟进）",
                'create_time' => $now,
            ]);

            $count++;
        }

        return $count;
    }
}
