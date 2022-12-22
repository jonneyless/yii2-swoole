<?php

namespace ijony\yiis\Swoole;

interface ResponseInterface
{
    public function sendStatusCode();

    public function sendHeaders();

    public function sendCookies();

    public function gzip();

    public function sendContent();

    public function send($gzip = false);
}