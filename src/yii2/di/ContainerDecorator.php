<?php

namespace ijony\yiis\yii2di;

use di\Container;
use Yii;

/**
 * Class ContainerDecorator
 *
 * @package ijony\yiis\yii2di
 */
class ContainerDecorator
{
    function __get($name)
    {
        $container = $this->getContainer();

        return $container->{$name};
    }

    /**
     * @return Container
     */
    protected function getContainer()
    {
        return Yii::$context->getContainer();
    }

    function __call($name, $arguments)
    {
        $container = $this->getContainer();

        return $container->$name(...$arguments);
    }
}