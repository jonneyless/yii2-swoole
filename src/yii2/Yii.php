<?php

namespace ijony\yiis\Yii2;

class Yii extends BaseYii
{

}

spl_autoload_register(['ijony\yiis\yii2Yii', 'autoload'], true, true);
Yii::$classMap = require(YII2_PATH . '/classes.php');