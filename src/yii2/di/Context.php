<?php

namespace ijony\yiis\yii2di;

use RuntimeException;
use Swoole\Coroutine;
use web\Application;

/**
 * 用于存放协程发生的上下文的容器
 *
 * @package ijony\yiis\yii2di
 */
final class Context
{
    const CONTAINER_KEY = 'container';
    const APPLICATION_KEY = 'app';

    private static $coroutineData;

    /**
     * @var array 全局数据
     */
    private static $global;

    public function getContainer()
    {
        $id = self::getCoroutineId();
        $result = self::$coroutineData[$id][self::CONTAINER_KEY] ?? null;
        if (!$result) {
            throw new RuntimeException('current coroutine container is not found');
        }

        return $result;
    }

    /**
     * 获取当前协程的ID,如果处于非协程环境时,返回-1,
     */
    public static function getCoroutineId()
    {
        $id = Coroutine::getuid();

        return $id;
    }

    public function setContainer($container)
    {
        $id = self::getCoroutineId();
        self::$coroutineData[$id][self::CONTAINER_KEY] = $container;
    }

    /**
     * @param null|int $coroutineId
     *
     * @return Application
     */
    public function getApplication($coroutineId = null)
    {
        $id = $coroutineId ?? self::getCoroutineId();
        $result = self::$coroutineData[$id][self::APPLICATION_KEY] ?? null;
        if (!$result) {
            throw new RuntimeException("current coroutine application is not found");
        }

        return $result;
    }

    public function setApplication($application)
    {
        $id = self::getCoroutineId();
        self::$coroutineData[$id][self::APPLICATION_KEY] = $application;
    }

    /**
     * 移除当前协程数据
     */
    public function removeCurrentCoroutineData()
    {
        $id = self::getCoroutineId();
        //只是unset的话,存在内存泄漏或回收过慢问题
        self::$coroutineData[$id] = null;
        unset(self::$coroutineData[$id]);
    }

    public static function getGlobalContext($key)
    {
        return self::$global[$key] ?? null;
    }

    public static function setGlobalContext($key, $value)
    {
        self::$global[$key] = $value;
    }
}