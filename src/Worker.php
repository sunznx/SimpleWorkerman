<?php

namespace SimpleWorkerman;

use Phalcon\Exception;
use SebastianBergmann\CodeCoverage\Report\PHP;
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

    public static $master_pid;

    public static $daemon = false;
    public static $stdoutFile = "/dev/null";
    public static $STDOUT;
    public static $STDERR;

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
            if (in_array($protocol, static::$_builtinTransports)) {
                $this->protocol = "tcp";
            } else {
                $this->parser = '\\SimpleWorkerman\\Protocol\\' . ucfirst($protocol);
                if ( !class_exists($this->parser)) {
                    echo "class {$this->parser} not found" . PHP_EOL;
                    exit(250);
                }
                $this->protocol = $this->parser::getTransport();
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
        self::installSignal();
        self::parseCommandArg();
        self::daemonize();
        self::savePid();
        static::$event_loop->run($this);
    }

    private function checkEnv()
    {
        $pid_file = self::PID_FILE;
        if ( !is_writable($pid_file)) {
            echo "pid file {$pid_file} is not writable" . PHP_EOL;
            exit(250);
        }

        return true;
    }
    
    private static function installSignal() 
    {

    }

    private static function parseCommandArg()
    {
        global $argv;

        $command1 = "";
        $command2 = "";
        if ( !empty($argv[1])) {
            $command1 = $argv[1];
        }
        if ( !empty($argv[2])) {
            $command2 = $argv[2];
        }

        if (!empty($command1) && in_array($command1, ["start", "stop"]) === false) {
            echo "unsupport command" . PHP_EOL;
            exit(250);
        }

        if ($command1 === "stop") {
            $pid = file_get_contents(self::PID_FILE);
            $process_exist = posix_kill($pid, 0);
            if ( !$process_exist) {
                echo "simpleworkerman is not running" . PHP_EOL;
                exit(250);
            }

            // TODO signal
            if (posix_kill($pid, SIGTERM)) {
                echo "process {$pid} killed" . PHP_EOL;
                exit(0);
            } else {
                echo "process {$pid} kill fail" . PHP_EOL;
                exit(250);
            }
        }

        if ($command1 === "start" && $command2 === "-d") {
            self::$daemon = true;
        }
    }

    private function savePid()
    {
        self::$master_pid = posix_getpid();
        file_put_contents(self::PID_FILE, self::$master_pid);
    }

    private static function daemonize()
    {
        if (self::$daemon === false) {
            return;
        }

        // double fork
        $pid = pcntl_fork();
        if ($pid < 0) {
            echo "daemon error fork" . PHP_EOL;
            exit(250);
        } else if ($pid > 0) { // parent
            exit(0);
        } else {
            $setsid_res = posix_setsid();
            if ($setsid_res == -1) {
                echo "daemon error setsid" . PHP_EOL;
                exit(250);
            }
        }

        umask(0);
        $pid = pcntl_fork();
        if ($pid < 0) {
            echo "daemon error fork" . PHP_EOL;
            exit(250);
        } else if ($pid > 0) { // parent
            exit(0);
        } else {
            // close fd
            $handle = fopen(static::$stdoutFile, "a");
            if ( !$handle) {
                echo "open file " . static::$stdoutFile . " error" . PHP_EOL;
                exit(250);
            }

            fclose(STDERR);
            fclose(STDOUT);
            self::$STDOUT = fopen(static::$stdoutFile, "a");
            self::$STDERR = fopen(static::$stdoutFile, "a");
        }
    }
}