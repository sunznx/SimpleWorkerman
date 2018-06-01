<?php

namespace SimpleWorkerman\Connection;

use SimpleWorkerman\SimpleWorker;

class SimpleTcpConnection
{
    protected $socket;

    public function __construct($socket)
    {
        $this->socket = $socket;
        SimpleWorker::$allSockets[(int)$this->socket] = $this->socket;
        SimpleWorker::$connections[(int)$this->socket] = $this;
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
        unset(SimpleWorker::$allSockets[(int)$this->socket]);
    }

    public static function broadcast(SimpleTcpConnection $from, $buff)
    {
        foreach (SimpleWorker::$connections as $to) {
            if ($from === $to) {
                continue;
            }

            $to->send($buff);
        }
    }
}

