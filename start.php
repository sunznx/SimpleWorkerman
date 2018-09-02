<?php

use Sunznx\SimpleWorkerman\Connection\TcpConnection;
use Sunznx\SimpleWorkerman\Timer\Timer;
use Sunznx\SimpleWorkerman\Worker;
use Sunznx\SimpleWorkerman\Connection\ConnectionInterface;

require_once __DIR__ . '/vendor/autoload.php';

$worker = new Worker('text://0.0.0.0:9999');
$worker->count = 4;
$worker->onWorkerStart = function (Worker $worker) {

};

$worker->onMessage = function (TcpConnection $connection, $data) {
    $connection->send($data . "aaaaaaaaaaaa");
};

$worker->runAll();