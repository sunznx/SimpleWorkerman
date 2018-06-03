<?php

namespace SimpleWorkerman;

use Phalcon\Exception;
use SimpleWorkerman\Connection\TcpConnection;
use SimpleWorkerman\EventLoop\EventLoopInterface;
use SimpleWorkerman\EventLoop\LibeventEventLoop;
use SimpleWorkerman\Protocol\ProtocolInterface;

class Worker
{
    const PID_FILE = __DIR__ . '/../var/run/simple_workman.pid';
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
     * @var LibeventEventLoop
     */
    public static $event_loop;

    public $protocol = "tcp";

    /**
     * @var ProtocolInterface | string
     */
    public $parser;

    protected static $_builtinTransports = array(
        'tcp'  => 'tcp',
        'udp'  => 'udp',
        'unix' => 'unix',
        'ssl'  => 'tcp'
    );

    public function __construct($address)
    {
        list($protocol, $target) = explode(':', $address, 2);
        if ($protocol === "udp") {
            $this->protocol = $protocol;
        } else {
            if (in_array($protocol, static::$_builtinTransports) === false) {
                $this->parser = '\\SimpleWorkerman\\Protocol\\' . ucfirst($protocol);
                if ( !class_exists($this->parser)) {
                    throw new Exception("class {$this->parser} not found" . PHP_EOL);
                }
            }
        }

        $address = "{$this->protocol}:{$target}";
        $this->main_socket = stream_socket_server($address, $errno, $errstr);
        stream_set_blocking($this->main_socket, 0);
        static::$allSockets[(int)$this->main_socket] = $this->main_socket;
        static::$event_loop = new LibeventEventLoop();
    }

    public function run()
    {
        self::checkEnv();
        self::init();
        static::$event_loop->run($this);
    }

    private function init()
    {
        file_put_contents(self::PID_FILE, posix_getpid());
    }

    public function checkEnv()
    {
        $pid_file = self::PID_FILE;
        if ( !is_writable($pid_file)) {
            echo "pid file {$pid_file} is not wirteable" . PHP_EOL;
            exit(250);
        }

        return true;
    }
}