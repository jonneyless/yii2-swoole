<?php

namespace ijony\yiis\Swoole\Events;

use Swoole\Http\Server;

interface ServerStartInterface
{
    public function __construct();

    public function handle(Server $server);
}