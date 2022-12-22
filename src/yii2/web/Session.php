<?php

namespace ijony\yiis\yii2web;

use ijony\yiis\yii2YiiBuilder;
use web\SessionTrait;

/**
 * Class Session
 *
 * @package ijony\yiis\yii2web
 */
class Session extends \yii\web\Session
{
    use SessionTrait;

    public function init()
    {
        parent::init();
//        register_shutdown_function([$this, 'close']);
        if ($this->getIsActive()) {
            YiiBuilder::warning('Session is already started in swoole', __METHOD__);
            $this->updateFlashCounters();
        }
    }
}