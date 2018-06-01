<?php

namespace SimpleWorkerman;

use SimpleWorkerman\Connection\SimpleTcpConnection;
use SimpleWorkerman\EventLoop\SimpleSelectEventLoop;

class SimpleWorker
{
    public $onConnect;

    public $onMessage;
    public $onClose;

    public $main_socket;

    public static $allSockets = [];

    /**
     * @var SimpleTcpConnection[]
     */
    public static $connections = [];

    /**
     * @var SimpleSelectEventLoop
     */
    public static $event_loop;

    public function __construct($address) {
        $this->main_socket = stream_socket_server($address, $errno, $errstr);
        echo "listen {$address}" . PHP_EOL;
        stream_set_blocking($this->main_socket, 0);
        static::$allSockets[(int)$this->main_socket] = $this->main_socket;
        static::$event_loop = new SimpleSelectEventLoop();
    }

    public function run()
    {
        static::$event_loop::run($this);
    }
}