<?php
namespace cores\traits;

use think\facade\Queue;

/**
 * 快捷发布队列任务
 * 仅限job层下的类use
 * Trait QueueTrait
 * @package cores\traits
 */
trait QueueTrait
{
    // 队列服务名称
    static private string $serveName = 'serve';

    /**
     * 快捷发布队列任务
     * 使用方法: \app\job\Test::dispatch(数据, 延迟);
     * @param array $data
     * @param int $secs 延迟几秒后执行
     * @return void
     */
    public static function dispatch(array $data, int $secs = 0)
    {
        // 发送发布：任务名，参数，队列名
        if ($secs == 0) {
            Queue::push(static::getJobName(), $data, static::$serveName);
        } else {
            Queue::later($secs, static::getJobName(), $data, static::$serveName);
        }
    }

    /**
     * 获取队列任务
     * @return string
     */
    private static function getJobName(): string
    {
        return get_class();
    }
}