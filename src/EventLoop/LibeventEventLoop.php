<?php

namespace Sunznx\SimpleWorkerman\EventLoop;

use Event;
use EventBase;
use EventConfig;
use Sunznx\SimpleWorkerman\Connection\TcpConnection;
use Sunznx\SimpleWorkerman\Worker;

class LibeventEventLoop implements EventLoopInterface
{
    /**
     * @var Event[][]
     */
    protected $events = [];

    /**
     * @var EventConfig
     */
    protected $event_config;

    /**
     * @var EventBase
     */
    public $event_base;

    public function add($socket, $event_type, callable $cb)
    {
        $type = self::getEventFromType($event_type);
        $event = new Event($this->event_base, $socket, $type, $cb);
        $this->events[(int)$socket][$event_type] = $event;
        $event->add();
    }

    public function addTimer($sec, callable $cb, $persist = true)
    {
        $event_type = EventLoopInterface::EV_TIMER;
        $type = self::getEventFromType($event_type, $persist);
        $event = new Event($this->event_base, -1, $type, $cb);
        $event->addTimer($sec);
        return $event;
    }

    public static function getEventFromType($event_type, $persist = true)
    {
        $ret = 0;

        if ($event_type & EventLoopInterface::EV_READ) {
            $ret |= Event::READ;
        }
        if ($event_type & EventLoopInterface::EV_WRITE) {
            $ret |= Event::WRITE;
        }
        if ($event_type & EventLoopInterface::EV_TIMER) {
            $ret |= Event::TIMEOUT;
        }
        if ($event_type & EventLoopInterface::EV_SIGNAL) {
            $ret |= Event::SIGNAL;
        }

        if ($persist) {
            $ret |= Event::PERSIST;
        }

        return $ret;
    }

    public function del($socket, $event_type)
    {
        if (isset($this->events[(int)$socket][$event_type])) {
            $this->events[(int)$socket][$event_type]->del();
            unset($this->events[(int)$socket][$event_type]);
        }
        if (empty($this->events[(int)$socket])) {
            unset($this->events[(int)$socket]);
        }
    }

    public function prepare()
    {
        $this->event_config = new EventConfig();
        $this->event_base = new EventBase($this->event_config);
    }

    public function run(Worker $worker)
    {
        $this->add($worker->main_socket, EventLoopInterface::EV_READ, function ($socket) use ($worker) {
            $new_conn_socket = stream_socket_accept($socket, 0, $remote_address);
            if ( !$new_conn_socket) {
                return;
            }

            stream_set_blocking($new_conn_socket, false);
            $conn = new TcpConnection($new_conn_socket, $worker, $remote_address);
            if ($worker->onConnect) {
                call_user_func_array($worker->onConnect, [$conn]);
            }

            $this->add($new_conn_socket, EventLoopInterface::EV_READ, function ($socket) {
                $conn = Worker::$connections[(int)$socket];
                $conn->recv();
            });
        });

        $this->event_base->dispatch();
        exit(250);
    }
}