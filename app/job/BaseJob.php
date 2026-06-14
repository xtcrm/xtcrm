<?php
// +----------------------------------------------------------------------
// | XiongTao CRM
// +----------------------------------------------------------------------
// | Copyright (c) 2017~2025 https://www.xtocn.com
// +----------------------------------------------------------------------
// | Licensed under the Apache License, Version 2.0
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace app\job;

use think\queue\Job;
use app\common\library\Log;
use app\common\service\system\Process as SystemProcessService;

/**
 * 队列任务基类
 * Class BaseJob
 * @package app\job
 */
abstract class BaseJob
{
    /**
     * 队列任务初始化
     */
    public function __construct()
    {
        // 记录定时任务最后执行时间
        SystemProcessService::setLastWorkingTime('queue');
    }

    // 执行具体的任务 (消费者)
    abstract public function handle(array $data): bool;

    /**
     * 队列任务线程 (消费者)
     * @param Job $job 当前的任务对象
     * @param array $data 发布任务时自定义的数据
     */
    public function fire(Job $job, array $data)
    {
        try {
            // ....这里执行具体的任务
            // 任务中如果要中断当前进程比如exit die，那么需要在这之前执行$job->delete()，否则会一直重复
            $this->handle($data);
            tre('队列任务成功');
            // 成功删除任务
            $job->delete();
        } catch (\Throwable $e) {
            tre('队列任务出错');
            tre('当前执行次数：' . $job->attempts());
            tre('错误信息：' . $e->getMessage());
            tre('trace：' . $e->getTraceAsString());
            // 记录日志
            Log::error([
                'name' => '队列任务出错',
                'jobClass' => get_called_class(),
                'attempts' => $job->attempts(),
                'errMessage' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // 任务有报错 重试执行3次后删除
            if (is_debug() || $job->attempts() >= 3) {
                $job->delete();
            } else {
                // 不成功延迟10秒再次发布
                $job->release(10);
            }
        }
    }

    /**
     * 发布失败
     * @param $data
     */
    public function failed($data)
    {
    }
}