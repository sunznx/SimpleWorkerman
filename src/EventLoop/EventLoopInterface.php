<?php

namespace Sunznx\SimpleWorkerman\EventLoop;

use Sunznx\SimpleWorkerman\Worker;

interface EventLoopInterface
{
    const EV_READ = 1;
    const EV_WRITE = 2;
    const EV_SIGNAL = 4;
    const EV_TIMER = 8;

    public function run(Worker $worker);
    public function add($socket, $event_type, callable $cb);
    public function del($socket, $event_type);
}