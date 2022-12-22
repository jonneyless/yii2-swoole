<?php

namespace ijony\yiis\traits;

use ijony\yiis\yii2YiiBuilder;
use Swoole\Http\Server;

trait YiiTrait
{
    protected function initYii(array $conf, Server $swoole)
    {
        $app = new YiiBuilder($conf);
        $app->prepareYii();
        $app->bindSwoole($swoole);

        return $app;
    }
}