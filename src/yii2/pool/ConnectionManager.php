<?php

namespace ijony\yiis\yii2pool;

use pool\ConnectionPool;
use yii\base\Component;
use yii\base\InvalidParamException;

/**
 * 连接池管理门面
 *
 * @package ijony\yiis\yii2pool
 */
class ConnectionManager extends Component
{
    /**
     * 连接池
     *
     * @var ConnectionPool[]
     */
    protected static $poolMap = [];

    public $poolConfig = [];

    /**
     * @param $connectionKey
     *
     * @return null|object
     */
    public function get($connectionKey)
    {
        if (isset(self::$poolMap[$connectionKey])) {
            return $this->getFromPool($connectionKey);
        }
    }

    public function getFromPool($connectionKey)
    {
        $pool = self::$poolMap[$connectionKey];

        $conn = $pool->getConnect();

        return $conn;
    }

    public function getPool($poolKey)
    {
        if (!$this->hasPool($poolKey)) {
            return null;
        }

        return self::$poolMap[$poolKey];
    }

    public function hasPool($poolKey)
    {
        return isset(self::$poolMap[$poolKey]);
    }

    public function addPool($poolKey, $pool)
    {
        if ($pool instanceof ConnectionPool) {
            self::$poolMap[$poolKey] = $pool;
        } else {
            throw new InvalidParamException("invalid pool type, poolKey=$poolKey");
        }
    }

    public function releaseConnection($connectionKey, $connection)
    {
        if (isset(self::$poolMap[$connectionKey])) {
            $pool = self::$poolMap[$connectionKey];

            return $pool->release($connection);
        }
    }
}