<?php

namespace ijony\yiis\yii2db;

use PDOException;
use Throwable;
use Yii;
use yii\base\InvalidConfigException;
use yii\db\Exception;

class Connection extends \yii\db\Connection
{

    public $maxErrorTimes = 2;

    /**
     * @var array pool config key in connectionManager
     */
    public $poolKey;

    public $commandClass = 'db\Command';

    protected $errorCount = 0;

    public function init()
    {
        parent::init();
    }

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

    protected function createPdoInstance()
    {
        $pdoClass = $this->pdoClass;
        if ($pdoClass === null) {
            $pdoClass = 'PDO';
            $driver = $this->getDriverName();
            if (isset($driver)) {
                if ($driver === 'mysql') {
                    $pdoClass = 'db\mysql\PDO';
//                    $this->commandClass = 'ijony\yiis\yii2db\mysql\Command';
                } elseif ($driver === 'mssql' || $driver === 'dblib') {
                    $pdoClass = 'yii\db\mssql\PDO';
                } elseif ($driver === 'sqlsrv') {
                    $pdoClass = 'yii\db\mssql\SqlsrvPDO';
                }
            }
        }

        $dsn = $this->dsn;
        if (strncmp('sqlite:@', $dsn, 8) === 0) {
            $dsn = 'sqlite:' . Yii::getAlias(substr($dsn, 7));
        }

        return new $pdoClass($dsn, $this->username, $this->password, $this->attributes);
    }

    public function open()
    {
        if ($this->pdo !== null) {
            return;
        }

        if (!empty($this->masters)) {
            $db = $this->getMaster();
            if ($db !== null) {
                $this->pdo = $db->pdo;

                return;
            }

            throw new InvalidConfigException('None of the master DB servers is available.');
        }

        if (empty($this->dsn)) {
            throw new InvalidConfigException('Connection::dsn cannot be empty.');
        }

        try {

            $this->pdo = $this->createPdoInstance();

            $this->initConnection();
        } catch (PDOException $e) {
            throw new Exception($e->getMessage(), $e->errorInfo, (int) $e->getCode(), $e);
        }
    }

    /**
     * ?????????????????????????????????????????????????????????
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