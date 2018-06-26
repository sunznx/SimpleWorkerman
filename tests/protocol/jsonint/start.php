<?php

use SimpleWorkerman\Worker;
use SimpleWorkerman\Connection\ConnectionInterface;

require_once __DIR__ . '/vendor/autoload.php';

$worker = new Worker('jsonInt://0.0.0.0:9999');
$worker->onMessage = function (ConnectionInterface $connection, $data) {
    $connection->send($data);
};
$worker->run();