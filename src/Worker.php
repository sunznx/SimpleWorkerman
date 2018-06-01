<?php

namespace SimpleWorkerman;

use SimpleWorkerman\Connection\TcpConnection;
use SimpleWorkerman\EventLoop\EventLoopInterface;
use SimpleWorkerman\EventLoop\LibeventEventLoop;

class Worker
{
    public $onConnect;

    public $onMessage;
    public $onClose;

    public $main_socket;

    public static $allSockets = [];

    /**
     * @var TcpConnection[]
     */
    public static $connections = [];

    /**
     * @var EventLoopInterface
     */
    public static $event_loop;

    public function __construct($address) {
        $this->main_socket = stream_socket_server($address, $errno, $errstr);
        echo "listen {$address}" . PHP_EOL;
        stream_set_blocking($this->main_socket, 0);
        static::$allSockets[(int)$this->main_socket] = $this->main_socket;
        static::$event_loop = new LibeventEventLoop();
    }

    public function run()
    {
        static::$event_loop::run($this);
    }
}