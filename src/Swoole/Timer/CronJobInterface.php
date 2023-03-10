<?php

namespace ijony\yiis\Swoole\Timer;

interface CronJobInterface
{
    public function __construct();

    /**
     * @return int $interval ms
     */
    public function interval();

    /**
     * @return bool $isImmediate
     */
    public function isImmediate();

    public function run();

    public function stop();
}