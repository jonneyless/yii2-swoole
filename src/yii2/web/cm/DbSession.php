<?php

namespace ijony\yiis\yii2web\cm;

use ijony\yiis\yii2YiiBuilder;
use web\cm\SessionTrait;
use yii\di\Instance;

class DbSession extends \yii\web\DbSession
{
    use SessionTrait;

    public function init()
    {
        $this->registerSessionHandler();
        if ($this->getIsActive()) {
            YiiBuilder::warning('Session is already started in swoole', __METHOD__);
            $this->updateFlashCounters();
        }
        $this->cache = Instance::ensure($this->cache, 'yii\caching\CacheInterface');
    }
}