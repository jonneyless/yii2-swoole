<?php

namespace ijony\yiis\Swoole\Socket;

use Swoole\Server;

interface UdpInterface
{
    public function onPacket(Server $server, $data, array $clientInfo);
}