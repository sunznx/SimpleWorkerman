<?php

use SimpleWorkerman\Connection\TcpConnection;
use SimpleWorkerman\Worker;

require_once __DIR__ . '/vendor/autoload.php';

$worker = new Worker('tcp://0.0.0.0:9999');
$worker->onConnect = function (TcpConnection $conn) {
    echo 'connected' . PHP_EOL;
};

$worker->onClose = function (TcpConnection $conn) {
    echo 'close' . PHP_EOL;
};

$worker->onMessage = function (TcpConnection $conn, $buff) {
    TcpConnection::broadcast($conn, $buff);
};

$worker->run();
