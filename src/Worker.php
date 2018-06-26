<?php

namespace SimpleWorkerman;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use SimpleWorkerman\Connection\TcpConnection;
use SimpleWorkerman\EventLoop\LibeventEventLoop;
use SimpleWorkerman\Protocol\ProtocolInterface;
use SimpleWorkerman\Timer\Timer;

class Worker
{
    const PID_FILE = __DIR__ . '/../var/run/simple_workman.pid';

    const LOG_FILE = __DIR__ . '/../var/log/simple_workman.log';

    const VERSION = '0.0.1';

    /**
     * @var Worker[]
     */
    public static $_workers;

    public $onConnect;

    /**
     * @var Logger
     */
    protected static $log;

    /**
     * @var callable
     */
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

    public $address;

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

    public $onWorkerStart;

    public $count = 1;
    public $name;
    public $worker_id;

    /**
     * @var int[]
     */
    public $childs;

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

        $this->address = "{$this->protocol}:{$target}";
        $this->worker_id = spl_object_hash($this);
    }

    private static function listen(Worker $worker)
    {
        $worker->main_socket = stream_socket_server($worker->address, $errno, $errstr);
        if ($errstr) {
            echo "socket create listen error: {$worker->address} {$errstr}" . PHP_EOL;
            exit(250);
        }
    }

    public static function setProcessTitle($title)
    {
        if ( !empty($title) && function_exists('cli_set_process_title')) {
            cli_set_process_title($title);
        }
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    protected static function forkWorkers(Worker $worker)
    {
        for ($i = 0; $i < $worker->count; $i++) {
            static::forkOneWorker($i, $worker);
        }
    }

    protected static function forkOneWorker($id, Worker $worker)
    {
        $pid = pcntl_fork();
        if ($pid < 0) {
            echo "error fork worker" . PHP_EOL;
            exit(250);
        } else if ($pid === 0) {  // child
            $worker->run();
            exit(0);
        } else {                  // parent
            $worker->childs[$id] = new WorkerChild($id, $worker->worker_id, $pid, $worker);
        }
    }

    private static function initTimer(Worker $worker)
    {
        Timer::$event_loop = static::$event_loop;
    }

    private static function prepareEventLoop(Worker $worker)
    {
        if (static::$event_loop === null) {
            static::$event_loop = new LibeventEventLoop();
            static::$event_loop->prepare();

            static::$allSockets[(int)$worker->main_socket] = $worker->main_socket;
        }
    }

    public function runAll()
    {
        self::checkEnv();
        self::installSignal();
        self::parseCommandArg();
        self::daemonize();

        self::setProcessTitle($this->getName());
        self::savePid();
        self::prepareLog();

        self::listen($this);

        // run children
        self::forkWorkers($this);

        // run parent
        self::monitor($this);
    }

    // children
    public function run()
    {
        self::setProcessTitle("SimpleWorkerman: worker process {$this->getName()}");

        // event_loop
        self::prepareEventLoop($this);

        // timer
        self::initTimer($this);

        // 只有 child 会走到这里
        if ($this->onWorkerStart) {
            call_user_func($this->onWorkerStart, $this);
        }

        static::$event_loop->run($this);  // 死循环
    }

    // parent
    public static function monitor(Worker $worker)
    {
        $worker->unlisten();

        //static::$_status = static::STATUS_RUNNING;
        while (1) {
            // Calls signal handlers for pending signals.
            pcntl_signal_dispatch();
            // Suspends execution of the current process until a child has exited, or until a signal is delivered
            $status = 0;
            $pid = pcntl_wait($status, WUNTRACED);

            // If a child has already exited.
            if ($pid > 0) {
                // Find out witch worker process exited.
                /**
                 * @var WorkerChild $worker_child
                 */
                foreach ($worker->childs as $id => $worker_child) {
                    if ($worker_child->pid == $pid) {
                        if ($status !== 0) {
                            static::log("worker[{$worker->name}:{$pid}] exit with status {$status}");
                        }
                        unset($worker->childs[$id]);
                        break;
                    }
                }
            }
        }
    }

    private function checkEnv()
    {
        $pid_file = self::PID_FILE;
        if ( !is_writable($pid_file) && file_exists($pid_file)) {
            echo "pid file {$pid_file} is not writable" . PHP_EOL;
            exit(250);
        }

        $log_file = self::LOG_FILE;
        if ( !is_writable($log_file) && file_exists($log_file)) {
            echo "log file {$log_file} is not writable" . PHP_EOL;
            exit(250);
        }

        return true;
    }

    private static function installSignal()
    {
        // stop
        pcntl_signal(SIGINT, [self::class, "signalHandler"], false);

        // graceful stop
        pcntl_signal(SIGTERM, [self::class, 'signalHandler'], false);

        // reload
        pcntl_signal(SIGUSR1, [self::class, 'signalHandler'], false);

        // graceful reload
        pcntl_signal(SIGQUIT, [self::class, 'signalHandler'], false);

        // status
        pcntl_signal(SIGUSR2, [self::class, 'signalHandler'], false);

        // connection status
        pcntl_signal(SIGIO, [self::class, 'signalHandler'], false);

        // ignore
        pcntl_signal(SIGPIPE, SIG_IGN, false);
    }

    public static function signalHandler($signal)
    {
        switch ($signal) {
            // Stop.
            case SIGINT:
                static::stopAll(false);
                break;

            // Graceful stop.
            case SIGTERM:
                static::stopAll(true);
                break;

            // Graceful Reload.
            case SIGQUIT:
                static::reload(true);
                break;
            // Reload.
            case SIGUSR1:
                static::reload(false);
                break;
        }
    }

    public static function stopAll($graceful = true)
    {
    }

    public static function reload($graceful = true)
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

        if ( !empty($command1) && in_array($command1, ["start", "stop"]) === false) {
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

    private function prepareLog()
    {
        self::$log = new Logger('simple_workerman_log');
        self::$log->pushHandler(new StreamHandler(self::LOG_FILE));
    }

    public static function log($str, $logger_level = Logger::NOTICE)
    {
        if ($logger_level == Logger::NOTICE) {
            self::$log->notice($str);
        } else if ($logger_level == Logger::WARNING) {
            self::$log->warning($str);
        }
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

    private function unlisten()
    {
        if ($this->main_socket) {
            @socket_close($this->main_socket);
            $this->main_socket = null;
        }
    }
}

class WorkerChild
{
    public $id;
    public $pid;

    public $worker_id;

    /**
     * @var Worker
     */
    protected $worker;

    public function __construct($id, $worker_id, $pid, Worker $worker)
    {
        $this->id = $id;
        $this->worker_id = $worker_id;
        $this->pid = $pid;
        $this->worker = $worker;
    }
}
