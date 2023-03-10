<?php

namespace ijony\yiis\yii2redis;

class Cache extends \yii\redis\Cache
{
    /**
     * @inheritdoc
     */
    protected function setValue($key, $value, $expire)
    {
        if ($expire == 0) {
            return (bool) $this->redis->executeCommand('SET', [$key, $value]);
        } else {
            return (bool) $this->redis->executeCommand('SETEX', [$key, $expire, $value]);
        }
    }

    /**
     * @inheritdoc
     */
    protected function addValue($key, $value, $expire)
    {
        if ($expire == 0) {
            return (bool) $this->redis->executeCommand('SETNX', [$key, $value]);
        } else {
            return (bool) $this->redis->executeCommand('SET', [$key, $value, ['NX', 'EX' => $expire]]);
        }
    }
}