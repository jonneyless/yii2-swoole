<?php

namespace ijony\yiis\Swoole;

use ijony\yiis\Swoole\Socket\WebSocketInterface;

interface WebSocketHandlerInterface extends WebSocketInterface
{
    public function __construct();
}