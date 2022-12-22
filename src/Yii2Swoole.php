<?php

namespace ijony\yiis;

use ijony\yiis\Swoole\DynamicResponse;
use ijony\yiis\Swoole\Events\ServerStartInterface;
use ijony\yiis\Swoole\Events\ServerStopInterface;
use ijony\yiis\Swoole\Events\WorkerErrorInterface;
use ijony\yiis\Swoole\Events\WorkerStartInterface;
use ijony\yiis\Swoole\Events\WorkerStopInterface;
use ijony\yiis\Swoole\InotifyTrait;
use ijony\yiis\Swoole\Process\CustomProcessTrait;
use ijony\yiis\Swoole\Process\ProcessTitleTrait;
use ijony\yiis\Swoole\Request;
use ijony\yiis\Swoole\Server;
use ijony\yiis\Swoole\StaticResponse;
use ijony\yiis\Swoole\Timer\TimerTrait;
use ijony\yiis\traits\LogTrait;
use ijony\yiis\traits\YiiTrait;
use ijony\yiis\yii2YiiBuilder;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Swoole\Http\Server as HttpServer;
use Swoole\Server\Port;
use Symfony\Component\Console\Style\OutputStyle;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use yii\web\Request as YiiRequest;

/**
 * Swoole Request => Yii Request
 * Yii Request => Yii handle => Yii Response
 * Yii Response => Swoole Response
 */
class Yii2Swoole extends Server
{
    /**
     * Fix conflicts of traits
     */
    use InotifyTrait, YiiTrait, LogTrait, ProcessTitleTrait, TimerTrait, CustomProcessTrait;

    /**@var OutputStyle */
    protected static $outputStyle;

    /**@var array */
    protected $yiiConf;

    /**@var YiiBuilder */
    protected $yii;

    public function __construct(array $svrConf, array $yiiConf)
    {
        parent::__construct($svrConf);
        $this->yiiConf = $yiiConf;

        $timerConf = $this->conf['timer'] ?? [];
        $timerConf['process_prefix'] = $svrConf['process_prefix'];
        $this->swoole->timerProcess = $this->addTimerProcess($this->swoole, $timerConf, $this->yiiConf);

        $inotifyConf = $this->conf['inotify_reload'] ?? [];
        if (!isset($inotifyConf['watch_path'])) {
            $inotifyConf['watch_path'] = $this->yiiConf['root_path'];
        }
        $inotifyConf['process_prefix'] = $svrConf['process_prefix'];
        $this->swoole->inotifyProcess = $this->addInotifyProcess($this->swoole, $inotifyConf, $this->yiiConf);

        $processes = $this->conf['processes'] ?? [];
        $this->swoole->customProcesses = $this->addCustomProcesses($this->swoole, $svrConf['process_prefix'], $processes, $this->yiiConf);

        // Fire ServerStart event
        if (isset($this->conf['event_handlers']['ServerStart'])) {
            YiiBuilder::autoload($this->yiiConf['root_path']);
            $this->fireEvent('ServerStart', ServerStartInterface::class, [$this->swoole]);
        }
    }

    protected function triggerWebSocketEvent($event, array $params)
    {
        if ($event === 'onHandShake') {
            $this->beforeWebSocketHandShake($params[0]);
            $params[1]->header('Server', $this->conf['server']);
        }

        parent::triggerWebSocketEvent($event, $params);

        switch ($event) {
            case 'onHandShake':
                if (isset($params[1]->header['Sec-Websocket-Accept'])) {
                    // Successful handshake
                    parent::triggerWebSocketEvent('onOpen', [$this->swoole, $params[0]]);
                }
                $this->afterWebSocketOpen($params[0]);
                break;
            case 'onOpen':
                $this->afterWebSocketOpen($params[1]);
                break;
        }
    }

    protected function triggerPortEvent(Port $port, $handlerClass, $event, array $params)
    {
        switch ($event) {
            case 'onHandShake':
                $this->beforeWebSocketHandShake($params[0]);
            case 'onRequest':
                $params[1]->header('Server', $this->conf['server']);
                break;
        }

        parent::triggerPortEvent($port, $handlerClass, $event, $params);

        switch ($event) {
            case 'onHandShake':
                if (isset($params[1]->header['Sec-Websocket-Accept'])) {
                    // Successful handshake
                    parent::triggerPortEvent($port, $handlerClass, 'onOpen', [$this->swoole, $params[0]]);
                }
                $this->afterWebSocketOpen($params[0]);
                break;
            case 'onOpen':
                $this->afterWebSocketOpen($params[1]);
                break;
        }
    }

    public function onShutdown(HttpServer $server)
    {
        parent::onShutdown($server);

        // Fire ServerStop event
        if (isset($this->conf['event_handlers']['ServerStop'])) {
            $this->yii = $this->initYii($this->yiiConf, $this->swoole);
            $this->fireEvent('ServerStop', ServerStopInterface::class, [$server]);
        }
    }

    public function onWorkerStart(HttpServer $server, $workerId)
    {
        parent::onWorkerStart($server, $workerId);

        // To implement gracefully reload
        // Delay to create Yii
        // Delay to include Yii's autoload.php
        $this->yii = $this->initYii($this->yiiConf, $this->swoole);

        // Fire WorkerStart event
        $this->fireEvent('WorkerStart', WorkerStartInterface::class, func_get_args());
    }

    public function onWorkerStop(HttpServer $server, $workerId)
    {
        parent::onWorkerStop($server, $workerId);

        // Fire WorkerStop event
        $this->fireEvent('WorkerStop', WorkerStopInterface::class, func_get_args());
    }

    public function onWorkerError(HttpServer $server, $workerId, $workerPId, $exitCode, $signal)
    {
        parent::onWorkerError($server, $workerId, $workerPId, $exitCode, $signal);

        YiiBuilder::autoload($this->yiiConf['root_path']);

        // Fire WorkerError event
        $this->fireEvent('WorkerError', WorkerErrorInterface::class, func_get_args());
    }

    public function onRequest(SwooleRequest $swooleRequest, SwooleResponse $swooleResponse)
    {
        try {
            parent::onRequest($swooleRequest, $swooleResponse);
            $yiiRequest = $this->convertRequest($this->yii, $swooleRequest);
            $this->yii->bindRequest($yiiRequest);
            $this->yii->fireEvent('laravels.received_request', [$yiiRequest]);
            $handleStaticSuccess = false;
            if ($this->conf['handle_static']) {
                // For Swoole < 1.9.17
                $handleStaticSuccess = $this->handleStaticResource($this->yii, $yiiRequest, $swooleResponse);
            }
            if (!$handleStaticSuccess) {
                $this->handleDynamicResource($this->yii, $yiiRequest, $swooleResponse);
            }
        } catch (\Exception $e) {
            $this->handleException($e, $swooleResponse);
        }
    }

    protected function beforeWebSocketHandShake(SwooleRequest $request)
    {
        // Start Yii's lifetime, then support session ...middleware.
        $yiiRequest = $this->convertRequest($this->yii, $request);
        $this->yii->bindRequest($yiiRequest);
        $this->yii->fireEvent('laravels.received_request', [$yiiRequest]);
        $this->yii->cleanProviders();
        $yiiResponse = $this->yii->handleDynamic($yiiRequest);
        $this->yii->fireEvent('laravels.generated_response', [$yiiRequest, $yiiResponse]);
    }

    protected function afterWebSocketOpen(SwooleRequest $request)
    {
        // End Yii's lifetime.
        $this->yii->saveSession();
        $this->yii->clean();
    }

    protected function convertRequest(YiiBuilder $yii, SwooleRequest $request)
    {
        $rawGlobals = $yii->getRawGlobals();

        return (new Request($request))->toIlluminateRequest($rawGlobals['_SERVER'], $rawGlobals['_ENV']);
    }

    protected function handleStaticResource(YiiBuilder $yii, YiiRequest $yiiRequest, SwooleResponse $swooleResponse)
    {
        $yiiResponse = $yii->handleStatic($yiiRequest);
        if ($yiiResponse === false) {
            return false;
        }
        $yiiResponse->headers->set('Server', $this->conf['server'], true);
        $yii->fireEvent('laravels.generated_response', [$yiiRequest, $yiiResponse]);
        $response = new StaticResponse($swooleResponse, $yiiResponse);
        $response->setChunkLimit($this->conf['swoole']['buffer_output_size']);
        $response->send($this->conf['enable_gzip']);

        return true;
    }

    protected function handleDynamicResource(YiiBuilder $yii, YiiRequest $yiiRequest, SwooleResponse $swooleResponse)
    {
        $yii->cleanProviders();
        $yiiResponse = $yii->handleDynamic($yiiRequest);
        $yiiResponse->headers->set('Server', $this->conf['server'], true);
        $yii->fireEvent('laravels.generated_response', [$yiiRequest, $yiiResponse]);
        if ($yiiResponse instanceof BinaryFileResponse) {
            $response = new StaticResponse($swooleResponse, $yiiResponse);
        } else {
            $response = new DynamicResponse($swooleResponse, $yiiResponse);
        }
        $response->setChunkLimit($this->conf['swoole']['buffer_output_size']);
        $response->send($this->conf['enable_gzip']);
        $yii->clean();

        return true;
    }

    /**
     * @param \Exception $e
     * @param SwooleResponse $response
     */
    protected function handleException($e, SwooleResponse $response)
    {
        $error = sprintf(
            'onRequest: Uncaught exception "%s"([%d]%s) at %s:%s, %s%s',
            get_class($e),
            $e->getCode(),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            PHP_EOL,
            $e->getTraceAsString()
        );
        $this->error($error);
        try {
            $response->status(500);
            $response->end('Oops! An unexpected error occurred');
        } catch (\Exception $e) {
            $this->logException($e);
        }
    }

    public static function getOutputStyle()
    {
        return static::$outputStyle;
    }

    public static function setOutputStyle(OutputStyle $outputStyle)
    {
        static::$outputStyle = $outputStyle;
    }
}
