<?php

namespace SimpleWorkerman\Connection;

use SimpleWorkerman\Worker;

class TcpConnection
{
    protected $socket;

    public function __construct($socket)
    {
        $this->socket = $socket;
        Worker::$allSockets[(int)$this->socket] = $this->socket;
        Worker::$connections[(int)$this->socket] = $this;
    }

    public function read()
    {
        return fread($this->socket, 65535);
    }

    public function send($buff)
    {
        fwrite($this->socket, $buff);
    }

    public function close()
    {
        fclose($this->socket);
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
}

