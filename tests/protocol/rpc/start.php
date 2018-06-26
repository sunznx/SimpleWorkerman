<?php

use SimpleWorkerman\Worker;
use SimpleWorkerman\Connection\ConnectionInterface;

require_once __DIR__ . '/vendor/autoload.php';

$worker = new Worker('json://0.0.0.0:9999');
$worker->onMessage = function (ConnectionInterface $connection, $data) {
    if (empty($data['model']) || empty($data['method'])) {
        $connection->send([
            'errcode' => 1,
            'errmsg'  => 'data param error'
        ]);
        return;
    }

    $class = "\\SimpleWorkerman" . "\\RPC" . "\\Model" . "\\" . ucfirst($data['model']);
    if ( !class_exists($class)) {
        $file_name = __DIR__ . "/src/RPC/Model/" . ucfirst($data['model']) . ".php";
        if ( !is_file($file_name)) {
            $connection->send([
                'errcode' => 1,
                'errmsg'  => 'error model'
            ]);
            return;
        }
        require_once $file_name;
    }

    $obj = new $class();
    if ( !method_exists($obj, $data['method'])) {
        $connection->send([
            'errcode' => 1,
            'errmsg'  => 'error method'
        ]);
        return;
    }

    $res = call_user_func([$obj, $data['method']], ...$data['param']);

    $connection->send([
        'errcode' => 0,
        'data'    => $res
    ]);
};
$worker->run();

