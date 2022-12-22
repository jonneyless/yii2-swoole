<?php

namespace ijony\yiis\yii2web;

use ijony\yiis\yii2YiiBuilder;
use web\SessionTrait;
use yii\di\Instance;

class CacheSession extends \yii\web\CacheSession
{
    use SessionTrait;

    public function init()
    {
        if ($this->getIsActive()) {
            YiiBuilder::warning('Session is already started in swoole', __METHOD__);
            $this->updateFlashCounters();
        }
        $this->cache = Instance::ensure($this->cache, 'yii\caching\CacheInterface');
    }

}