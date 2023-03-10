<?php

namespace ijony\yiis\Swoole\Events;

use Swoole\Http\Server;

interface WorkerErrorInterface
{
    public function __construct();

    public function handle(Server $server, $workerId, $workerPId, $exitCode, $signal);
}