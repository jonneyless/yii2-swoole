<?php

namespace ijony\yiis\Swoole\Socket;

use Swoole\Server\Port;

abstract class Http implements PortInterface, HttpInterface
{
    protected $swoolePort;

    public function __construct(Port $port)
    {
        $this->swoolePort = $port;
    }
}