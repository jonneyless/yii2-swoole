<?php

namespace ijony\yiis\yii2pool;

use Closure;
use pool\ConnectionPool;

class DbPool extends ConnectionPool
{
    /**
     * 数据库池,通过回调来创建链接
     *
     * @var callable
     */
    public $createHandle;

    /**
     * 重建链接的回调
     *
     * @var callable
     */
    public $reConnectHandle;

    public function createConnect()
    {
        if ($this->createHandle instanceof Closure) {
            $conn = call_user_func($this->createHandle);
            $this->reConnect($conn);

            return $conn;
        }
    }

    public function reConnect($client)
    {
        if ($this->reConnectHandle instanceof Closure) {
            call_user_func($this->reConnectHandle, $client);
        }
    }

}