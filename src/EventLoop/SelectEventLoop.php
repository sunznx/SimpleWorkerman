<?php

namespace SimpleWorkerman\EventLoop;

use SimpleWorkerman\Connection\TcpConnection;
use SimpleWorkerman\Worker;

class SelectEventLoop implements EventLoopInterface
{
    public static function run(Worker $worker)
    {
        while (1) {
            $write = null;
            $except = null;
            $read = Worker::$allSockets;

            stream_select($read, $write, $except, 60);
            foreach ($read as $index => $socket) {
                if ($socket == $worker->main_socket) {
                    $new_conn_socket = stream_socket_accept($worker->main_socket);
                    if ( !$new_conn_socket) {
                        continue;
                    }

                    socket_set_nonblock($new_conn_socket);
                    $conn = new TcpConnection($new_conn_socket);
                    if ($worker->onConnect) {
                        call_user_func_array($worker->onConnect, [$conn]);
                    }
                } else {
                    $conn = Worker::$connections[$socket];
                    $buffer = $conn->read();
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

