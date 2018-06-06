<?php

use SimpleWorkerman\Connection\TcpConnection;
use SimpleWorkerman\Worker;

require_once __DIR__ . '/vendor/autoload.php';

$worker = new Worker('http://0.0.0.0:9999');
$worker->onMessage = function (TcpConnection $conn, $buff) {
    //TcpConnection::broadcast($conn, $buff);
    $conn->send($buff);
};
$worker->run();
