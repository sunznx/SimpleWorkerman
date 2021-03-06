<?php

use Sunznx\SimpleWorkerman\Worker;
use Sunznx\SimpleWorkerman\Connection\ConnectionInterface;

require_once __DIR__ . '/vendor/autoload.php';

$worker = new Worker('json://0.0.0.0:9999');
$worker->onMessage = function (ConnectionInterface $connection, $data) {
    $connection->send($data);
};
$worker->run();

