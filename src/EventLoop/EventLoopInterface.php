<?php

namespace SimpleWorkerman\EventLoop;

use SimpleWorkerman\Worker;

interface EventLoopInterface
{
    public function run(Worker $worker);
    public function add($socket, callable $cb);
    public function del($socket);
}