<?php

namespace ijony\yiis\yii2di;

use ijony\yiis\yii2YiiBuilder;

/**
 * 以该类来替换Yii::$app的引用,以实现协程态下$app的隔离
 *
 * @package ijony\yiis\yii2coroutine
 */
class ApplicationDecorator
{
    public function &__get($name)
    {
        $application = $this->getApplication();
        if (property_exists($application, $name)) {
            return $application->{$name};
        } else {
            $value = $application->{$name};

            return $value;
        }
    }

    public function __set($name, $value)
    {
        $application = $this->getApplication();
        $application->{$name} = $value;
    }

    /**
     * 根据协程ID
     *
     * @param $coroutineId
     *
     * @return \Yii\base\Application
     *
     */
    public function getApplication($coroutineId = null)
    {
        return YiiBuilder::$context->getApplication($coroutineId);
    }

    public function __call($name, $arguments)
    {
        $application = $this->getApplication();

        return $application->$name(...$arguments);
    }
}