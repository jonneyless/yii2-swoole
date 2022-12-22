<?php

namespace ijony\yiis\yii2bootstrap;

use Swoole\Server;

/**
 * 服务启动器接口
 *
 * @package ijony\yiis\yii2bootstrap
 */
interface BootstrapInterface
{
    /**
     * worker进程启动，
     *
     * @see https://wiki.swoole.com/wiki/page/46.html about swoole
     *
     * @param Server $server
     * @param $worker_id
     */
    public function onWorkerStart($server, $worker_id);

    /**
     * worker停止
     *
     * @see https://wiki.swoole.com/wiki/page/47.html about swoole
     *
     * @param Server $server
     * @param $worker_id
     */
    public function onWorkerStop($server, $worker_id);

    /**
     * 接收请求事件
     *
     * @see https://wiki.swoole.com/wiki/page/330.html about swoole
     *
     * @param $request
     * @param $response
     *
     * @return mixed
     */
    public function onRequest($request, $response);

    public function onTask($server, $taskId, $srcWorkerId, $data);

    public function onFinish($server, $taskId, $data);

    public function onWorkerError($server, $workerId, $workerPid, $exitCode, $sigNo);
}