<?php

namespace ijony\yiis\yii2web\cm;

use ijony\yiis\yii2YiiBuilder;
use web\Response;
use yii\base\InvalidParamException;

/**
 * 普通(非协程)模式下session处理类
 * swoole对原生php session的部分操作存在部分不支持,如session_set_cookie_params
 *
 * @package ijony\yiis\yii2cmode
 */
trait SessionTrait
{
    private $_hasSessionId = null;

    /**
     * @var array parameter-value pairs to override default session cookie parameters that are used for session_set_cookie_params() function
     * Array may have the following possible keys: 'lifetime', 'path', 'domain', 'secure', 'httponly'
     * @see http://www.php.net/manual/en/function.session-set-cookie-params.php
     */
    private $_cookieParams = ['httponly' => true];

    public function close()
    {
        parent::close();
        //在session_write_close后,清除当前进程session数据
        $_SESSION = [];
        session_abort();
        $this->setHasSessionId(null);
        $this->setCookieParams(['httponly' => true]);
    }

    /**
     * @inheritdoc
     */
    public function regenerateID($deleteOldSession = false)
    {
        if ($this->getIsActive()) {
            // add @ to inhibit possible warning due to race condition
            // https://github.com/yiisoft/yii2/pull/1812
            if (YII_DEBUG && !headers_sent()) {
                $this->sessionRegenerateId($deleteOldSession);
            } else {
                @$this->sessionRegenerateId($deleteOldSession);
            }
        }
    }

    /**
     * session_regenerate_id的实现
     *
     * @param $deleteOldSession
     */
    protected function sessionRegenerateId($deleteOldSession)
    {
        @session_regenerate_id();
        $this->_hasSessionId = false;
        $this->implantSessionId();
        $this->_hasSessionId = true;
    }

    private function implantSessionId()
    {
        if ($this->getHasSessionId() === false) {
            /** @var Response $response */
            $response = YiiBuilder::$app->getResponse();
            $data = $this->getCookieParams();
            if (isset($data['lifetime'], $data['path'], $data['domain'], $data['secure'], $data['httponly'])) {
                $expire = $data['lifetime'] ? time() + $data['lifetime'] : 0;
                $response->getSwooleResponse()->cookie($this->getName(), $this->getId(), $expire, $data['path'], $data['domain'], $data['secure'], $data['httponly']);
            } else {
                $response->getSwooleResponse()->cookie($this->getName(), $this->getId());
            }
        }
    }

    public function getHasSessionId()
    {
        if ($this->_hasSessionId === null) {
            $name = $this->getName();
            $request = YiiBuilder::$app->getRequest();
            $cookie = $request->getSwooleRequest()->cookie;
            if (!empty($cookie[$name]) && ini_get('session.use_cookies')) {
                $this->_hasSessionId = true;
            } elseif (!ini_get('session.use_only_cookies') && ini_get('session.use_trans_sid')) {
                $this->_hasSessionId = $request->get($name) != '';
            } else {
                $this->_hasSessionId = false;
            }
        }

        return $this->_hasSessionId;
    }

    public function open()
    {
        if ($this->getIsActive()) {
            return;
        }
        $this->setCookieParamsInternal();
        $this->sessionStart();
        if ($this->getIsActive()) {
            YiiBuilder::info('Session started', __METHOD__);
            $this->updateFlashCounters();
            $this->implantSessionId();
        } else {
            $error = error_get_last();
            $message = isset($error['message']) ? $error['message'] : 'Failed to start session.';
            YiiBuilder::error($message, __METHOD__);
        }
    }

    /**
     * Sets the session cookie parameters.
     * This method is called by [[open()]] when it is about to open the session.
     *
     * @throws InvalidParamException if the parameters are incomplete.
     * @see http://us2.php.net/manual/en/function.session-set-cookie-params.php
     */
    private function setCookieParamsInternal()
    {
        $data = $this->getCookieParams();
        if (isset($data['lifetime'], $data['path'], $data['domain'], $data['secure'], $data['httponly'])) {
            session_set_cookie_params($data['lifetime'], $data['path'], $data['domain'], $data['secure'], $data['httponly']);
        } else {
            throw new InvalidParamException('Please make sure cookieParams contains these elements: lifetime, path, domain, secure and httponly.');
        }
    }

    private function sessionStart($option = null)
    {
        $cookie = YiiBuilder::$app->request->getSwooleRequest()->cookie;
        $sid = $cookie[$this->getName()] ?? null;
        if ($sid) {
            $this->setId($sid);
        } else {
            $sid = $this->newSessionId();
            $this->setId($sid);
        }
        @session_start();
    }

    /**
     * 生成新的session Id
     *
     * @return string
     */
    protected function newSessionId()
    {
        //7.1,有新的方法
        if (version_compare(PHP_VERSION, '7.1', '<')) {
            $sid = md5($_SERVER['REMOTE_ADDR'] . microtime() . rand(0, 100000));
        } else {
            $sid = session_create_id();
        }

        return $sid;
    }
}