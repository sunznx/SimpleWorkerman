<?php

namespace SimpleWorkerman\EventLoop;

use SimpleWorkerman\Worker;

interface EventLoopInterface
{
    public static function run(Worker $worker);
}