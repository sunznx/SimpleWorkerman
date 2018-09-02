<?php

namespace Sunznx\SimpleWorkerman\Connection;

use Phalcon\Events\EventInterface;
use Sunznx\SimpleWorkerman\EventLoop\EventLoopInterface;
use Sunznx\SimpleWorkerman\EventLoop\LibeventEventLoop;
use Sunznx\SimpleWorkerman\Protocol\ProtocolInterface;
use Sunznx\SimpleWorkerman\Worker;

class TcpConnection implements ConnectionInterface
{
    public $recv_buff;
    public $send_buff;

    public $parsed_len = 0;     // for parser only

    const MAX_PACKAGE_SIZE = 10485760;
    const DEFAULT_READ_PACKAGE_SIZE = 65535;

    protected $protocol;

    protected $socket;

    protected $remote_address;

    protected $conn_status;

    public $bufferFull = false;

    /**
     * @var Worker
     */
    protected $worker;

    public function __construct($socket, Worker $worker, $remote_address)
    {
        $this->socket = $socket;
        $this->worker = $worker;
        $this->remote_address = $remote_address;
        $this->conn_status = self::CONN_CONNECTED;
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

    public function baseWrite()
    {
        $len = fwrite($this->socket, $this->send_buff, 8192);
        if ($len < 0) {  // error
            $this->destroy();
            return;
        }

        if ($len == strlen($this->send_buff)) {
            if ($this->conn_status == self::CONN_CLOSING) {
                $this->destroy();
                return;
            } else {
                Worker::$event_loop->del($this->socket, EventLoopInterface::EV_WRITE);
            }
        }

        $this->send_buff = substr($this->send_buff, $len);
    }

    public function send($buff, $is_raw = false)
    {
        $parser = $this->worker->parser;
        if (false === $is_raw && $parser !== null) {
            $buff = $parser::encode($buff, $this);
        }

        if (empty($this->send_buff)) {
            Worker::$event_loop->add($this->socket, EventLoopInterface::EV_WRITE, [$this, 'baseWrite']);
        }
        $this->send_buff .= $buff;
    }

    public function close($buff = "")
    {
        if ($this->conn_status == self::CONN_CLOSED || $this->conn_status == self::CONN_CLOSING) {
            return;
        }

        if ( !empty($buff)) {
            $this->send($buff);
        }
        $this->conn_status = self::CONN_CLOSING;
        if (empty($this->send_buff)) {
            $this->destroy();
        }
    }

    public function destroy()
    {
        if ($this->conn_status == self::CONN_CLOSED) {
            return;
        }


        $this->recv_buff = "";
        $this->send_buff = "";
        Worker::$event_loop->del($this->socket, EventLoopInterface::EV_READ);
        Worker::$event_loop->del($this->socket, EventLoopInterface::EV_WRITE);
        fclose($this->socket);

        $this->conn_status = self::CONN_CLOSED;
        unset(Worker::$allSockets[(int)$this->socket]);
        unset(Worker::$connections[(int)$this->socket]);
    }

    protected function parserHandler()
    {
        $parser = $this->worker->parser;
        while (1) {
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

    public function getRemoteIp()
    {
        return explode(':', $this->remote_address)[0];
    }

    public function getRemotePort()
    {
        return explode(':', $this->remote_address)[1];
    }
}

