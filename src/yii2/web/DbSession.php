<?php

namespace ijony\yiis\yii2web;

use web\SessionTrait;
use Yii;
use yii\di\Instance;

class DbSession extends \yii\web\DbSession
{
    use SessionTrait;

    public function init()
    {
        if ($this->getIsActive()) {
            Yii::warning('Session is already started in swoole', __METHOD__);
            $this->updateFlashCounters();
        }
        $this->cache = Instance::ensure($this->cache, 'yii\caching\CacheInterface');
    }
}