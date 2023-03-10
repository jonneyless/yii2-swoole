<?php

namespace ijony\yiis\Swoole\Timer;

/**
 * This CronJob is used to check global timer alive.
 * Class CheckGlobalTimerAliveCronJob
 *
 * @package ijony\yiis\Swoole\Timer
 */
class CheckGlobalTimerAliveCronJob extends CronJob
{
    public function interval()
    {
        return (int) (static::GLOBAL_TIMER_LOCK_SECONDS * 0.9) * 1000;
    }

    public function isImmediate()
    {
        return false;
    }

    public function run()
    {
        static::checkSetEnable();
    }
}