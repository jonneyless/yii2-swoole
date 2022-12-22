<?php

namespace ijony\yiis\Swoole\Timer;

/**
 * This CronJob is used to renew the cache key of global timer.
 * Class RenewGlobalTimerLockCronJob
 *
 * @package ijony\yiis\Swoole\Timer
 */
class RenewGlobalTimerLockCronJob extends CronJob
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
        static::isCurrentTimerAlive() && static::renewGlobalTimerLock(static::GLOBAL_TIMER_LOCK_SECONDS);
    }
}