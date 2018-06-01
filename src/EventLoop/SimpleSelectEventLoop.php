<?php

namespace SimpleWorkerman\EventLoop;

use SimpleWorkerman\Connection\SimpleTcpConnection;
use SimpleWorkerman\SimpleWorker;

class SimpleSelectEventLoop
{
    public static function run(SimpleWorker $worker)
    {
        while (1) {
            $write = null;
            $except = null;
            $read = SimpleWorker::$allSockets;

            stream_select($read, $write, $except, 60);
            foreach ($read as $index => $socket) {
                if ($socket == $worker->main_socket) {
                    $new_conn_socket = stream_socket_accept($worker->main_socket);
                    if ( !$new_conn_socket) {
                        continue;
                    }

                    $conn = new SimpleTcpConnection($new_conn_socket);
                    if ($worker->onConnect) {
                        call_user_func_array($worker->onConnect, [$conn]);
                    }
                } else {
                    $conn = SimpleWorker::$connections[$socket];
                    $buffer = $conn->read();
                    if ($buffer === '' || $buffer === false) {
                        if ($worker->onClose) {
                            call_user_func_array($worker->onClose, [$conn]);
                        }
                        $conn->close();
                        unset(SimpleWorker::$connections[$socket]);
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

