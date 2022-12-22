<?php

namespace ijony\yiis\Yii2;

class BaseYii extends \yii\BaseYii
{
    /**
     * 由于Yii的静态化,需要另一个上下文对象来处理协程对象
     *
     * @var \di\Context
     */
    public static $context;

    /**
     * @var \Swoole\Server
     */
    public static $swooleServer;
}