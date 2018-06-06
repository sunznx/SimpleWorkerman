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
    protected $events = [];

    /**
     * @var EventConfig
     */
    protected $event_config;

    /**
     * @var EventBase
     */
    protected $event_base;

    public function add($socket, callable $cb)
    {
        $event = new Event($this->event_base, $socket, Event::READ | Event::PERSIST, $cb);
        $this->events[(int)$socket] = $event;
        $event->add();
    }

    public function del($socket)
    {
        $this->events[(int)$socket]->del();
        unset($this->events[(int)$socket]);
    }

    public function run(Worker $worker)
    {
        $this->event_config = new EventConfig();
        $this->event_base = new EventBase($this->event_config);;

        $this->add($worker->main_socket, function ($socket) use ($worker) {
            $new_conn_socket = stream_socket_accept($socket);
            if ( !$new_conn_socket) {
                return;
            }

            socket_set_nonblock($new_conn_socket);
            $conn = new TcpConnection($new_conn_socket, $worker);
            if ($worker->onConnect) {
                call_user_func_array($worker->onConnect, [$conn]);
            }

            $this->add($new_conn_socket, function ($socket) {
                $conn = Worker::$connections[(int)$socket];
                $conn->recv();
            });
        });

        $this->event_base->loop();
    }

    public function stop(Worker $worker)
    {

    }
}