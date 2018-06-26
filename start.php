<?php

use SimpleWorkerman\Connection\TcpConnection;
use SimpleWorkerman\Timer\Timer;
use SimpleWorkerman\Worker;
use SimpleWorkerman\Connection\ConnectionInterface;

require_once __DIR__ . '/vendor/autoload.php';

$worker = new Worker('text://0.0.0.0:9999');
$worker->count = 4;
$worker->onWorkerStart = function (Worker $worker) {

};

$worker->onMessage = function (TcpConnection $connection, $data) {
    $connection->send($data);
};

$worker->runAll();