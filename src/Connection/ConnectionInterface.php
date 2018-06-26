<?php

namespace SimpleWorkerman\Connection;

interface ConnectionInterface
{
    const CONN_CONNECTED = 0;
    const CONN_CLOSING = 1;
    const CONN_CLOSED = 2;

    public function recv();
    public function send($buff, $is_raw = false);
    public function close($buff = "");

    public function getRemoteIp();

    public function getRemotePort();
}

