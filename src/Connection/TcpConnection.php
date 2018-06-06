<?php

namespace SimpleWorkerman\Connection;

use SimpleWorkerman\EventLoop\LibeventEventLoop;
use SimpleWorkerman\Protocol\ProtocolInterface;
use SimpleWorkerman\Worker;

class TcpConnection implements ConnectionInterface
{
    public $recv_buff;
    public $parsed_len = 0;     // for parser only

    const MAX_PACKAGE_SIZE = 10485760;
    const DEFAULT_READ_PACKAGE_SIZE = 65535;

    protected $protocol;

    protected $socket;

    /**
     * @var Worker
     */
    protected $worker;

    public function __construct($socket, Worker $worker)
    {
        $this->socket = $socket;
        $this->worker = $worker;
        Worker::$allSockets[(int)$this->socket] = $this->socket;
        Worker::$connections[(int)$this->socket] = $this;
    }

    public function recv()
    {
        $buffer = fread($this->socket, self::DEFAULT_READ_PACKAGE_SIZE);

        if ($buffer === '' || $buffer === false) {
            if ($this->worker->onClose) {
                call_user_func_array($this->worker->onClose, [$this]);
            }
            $this->close();
            return;
        }

        $this->recv_buff .= $buffer;

        // custom protocol parser
        if ( !empty($this->worker->parser)) {
            $this->parserHandler();
            return;
        }

        if ($this->worker->onMessage) {
            call_user_func_array($this->worker->onMessage, [$this, $this->recv_buff]);
        }

        $this->recv_buff = '';
    }

    public function send($buff, $raw = false)
    {
        $parser = $this->worker->parser;
        if (false === $raw && $parser !== null) {
            $buff = $parser::encode($buff, $this);
        }
        fwrite($this->socket, $buff);
    }

    public function close()
    {
        fclose($this->socket);
        $this->recv_buff = "";
        Worker::$event_loop->del($this->socket);
        unset(Worker::$allSockets[(int)$this->socket]);
        unset(Worker::$connections[(int)$this->socket]);
    }

    public static function broadcast(TcpConnection $from, $buff)
    {
        foreach (Worker::$connections as $to) {
            if ($from === $to) {
                continue;
            }

            $to->send($buff);
        }
    }

    protected function parserHandler()
    {
        $parser = $this->worker->parser;
        $this->parsed_len = $parser::input($this->recv_buff, $this);
        if ($this->parsed_len <= 0) {  // 不是一个完整的包，下次继续
            return;
        }

        $recv_len = strlen($this->recv_buff);
        if ($this->parsed_len > self::MAX_PACKAGE_SIZE) {
            echo "error package: package_len={$recv_len} is too large";
            $this->close();
            return;
        }

        if ($this->parsed_len > strlen($this->recv_buff)) { // parser error
            echo "error package: parser_len={$this->parsed_len}, package_len={$recv_len}";
            $this->close();
            return;
        }

        $one_request_buffer = substr($this->recv_buff, 0, $this->parsed_len);
        $this->recv_buff = substr($this->recv_buff, $this->parsed_len);
        if ($this->worker->onMessage) {
            $decode_buff = $parser::decode($one_request_buffer, $this);
            call_user_func_array($this->worker->onMessage, [$this, $decode_buff]);
        }
    }
}

