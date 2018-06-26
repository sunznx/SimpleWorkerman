<?php

namespace SimpleWorkerman\EventLoop;

use SimpleWorkerman\Connection\TcpConnection;
use SimpleWorkerman\Worker;

/**
 * Class SelectEventLoop
 * @package SimpleWorkerman\EventLoop
 * @deprecated
 */
class SelectEventLoop implements EventLoopInterface
{
    public function add($socket, callable $cb)
    {

    }

    public function del($socket)
    {

    }

    public function run(Worker $worker)
    {
        while (1) {
            $write = null;
            $except = null;
            $read = Worker::$allSockets;

            stream_select($read, $write, $except, 60);
            foreach ($read as $index => $socket) {
                if ($socket == $worker->main_socket) {
                    $new_conn_socket = stream_socket_accept($worker->main_socket, 0, $remote_address);
                    if ( !$new_conn_socket) {
                        continue;
                    }

                    $conn = new TcpConnection($new_conn_socket, $worker, $remote_address);
                    if ($worker->onConnect) {
                        call_user_func_array($worker->onConnect, [$conn]);
                    }
                } else {
                    $conn = Worker::$connections[$socket];
                    $buffer = $conn->recv();
                    if ($buffer === '' || $buffer === false) {
                        if ($worker->onClose) {
                            call_user_func_array($worker->onClose, [$conn]);
                        }
                        $conn->close();
                        continue;
                    }

                    if ($worker->onMessage) {
                        call_user_func_array($worker->onMessage, [$conn, $buffer]);
                    }
                }
            }
        }
    }
}

