<?php

namespace SimpleWorkerman\EventLoop;

use Event;
use EventBase;
use EventConfig;
use SimpleWorkerman\Connection\TcpConnection;
use SimpleWorkerman\Worker;

class LibeventEventLoop implements EventLoopInterface
{
    /**
     * @var Event[]
     */
    protected static $events = [];

    public static function run(Worker $worker)
    {
        $event_config = new EventConfig();
        $evnet_base = new EventBase($event_config);

        $event = new Event($evnet_base, $worker->main_socket, Event::READ | Event::PERSIST, function ($main_socket) use ($evnet_base, $worker) {
            $new_conn_socket = stream_socket_accept($main_socket);
            if ( !$new_conn_socket) {
                return;
            }

            socket_set_nonblock($new_conn_socket);
            $conn = new TcpConnection($new_conn_socket);
            if ($worker->onConnect) {
                call_user_func_array($worker->onConnect, [$conn]);
            }

            $new_event = new Event($evnet_base, $new_conn_socket, Event::READ | Event::PERSIST, function ($socket) use ($worker) {
                $conn = Worker::$connections[(int)$socket];

                $buffer = $conn->read();
                if ($buffer === '' || $buffer === false) {
                    if ($worker->onClose) {
                        call_user_func_array($worker->onClose, [$conn]);
                    }
                    $conn->close();
                    static::$events[(int)$socket]->del();
                    unset(static::$events[(int)$socket]);
                    return;
                }

                if ($worker->onMessage) {
                    call_user_func_array($worker->onMessage, [$conn, $buffer]);
                }
            });

            static::$events[(int)$new_conn_socket] = $new_event;
            $new_event->add();
        });

        static::$events[(int)$worker->main_socket] = $event;
        $event->add();
        $evnet_base->loop();
    }
}

