<?php
use SimpleWorkerman\WebServer;

require_once __DIR__ . '/vendor/autoload.php';

$webserver = new WebServer('http://0.0.0.0:9999');

$config = [
    'root' => __DIR__ . '/tests/protocol/http'
];
$webserver->addRoot('localhost', $config);
$webserver->run();