<?php

namespace SimpleWorkerman\Connection;

use SimpleWorkerman\Worker;

interface ConnectionInterface
{
    public function recv();
    public function send($buff);
    public function close();
}

