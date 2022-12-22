<?php

namespace ijony\yiis\yii2db\cm;

use PDOException;
use Throwable;

/**
 * 非协程模式下的链接类
 *
 * @package ijony\yiis\yii2cmode
 */
class Connection extends \yii\db\Connection
{

    public $maxErrorTimes = 2;

    protected $errorCount = 0;

    /**
     * @inheritdoc
     */
    public function beginTransaction($isolationLevel = null)
    {
        try {
            return parent::beginTransaction($isolationLevel);
        } catch (Throwable $exception) {
            if ($this->isConnectionError($exception) && $this->errorCount < $this->maxErrorTimes) {
                $this->close();
                $this->open();
                $this->errorCount++;

                return $this->beginTransaction($isolationLevel);
            }
            $this->errorCount = 0;
            throw  $exception;
        }
    }

    /**
     * 检查指定的异常是否为可以重连的错误类型
     *
     * @param \Exception $exception
     *
     * @return bool
     */
    public function isConnectionError($exception)
    {
        if ($exception instanceof PDOException) {
            $errorCode = $exception->getCode();
            if ($errorCode == 70100 || $errorCode == 2006 || $errorCode == 2013) {
                return true;
            }
        }
        $message = $exception->getMessage();
        if (strpos($message, 'Error while sending QUERY packet.') !== false) {
            return true;
        }
        // Error reading result set's header
        if (strpos($message, 'Error reading result set\'s header') !== false) {
            return true;
        }
        // MySQL server has gone away
        if (strpos($message, 'MySQL server has gone away') !== false) {
            return true;
        }

        return false;
    }
}