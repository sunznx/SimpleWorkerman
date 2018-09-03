<?php

namespace Sunznx\SimpleWorkerman\Protocol;

use Sunznx\SimpleWorkerman\Connection\ConnectionInterface;
use Sunznx\SimpleWorkerman\Connection\TcpConnection;
use Sunznx\SimpleWorkerman\Protocol\Redis\RedisResp;

class Redis implements ProtocolInterface
{
    public static function getTransport()
    {
        return "tcp";
    }

    public static function input($buffer, ConnectionInterface $connection)
    {
        if (strlen($buffer) >= TcpConnection::MAX_PACKAGE_SIZE) {
            $connection->close();
            return 0;
        }

        $redisResp = new RedisResp($buffer);
        $connection->redisResp = $redisResp;
        return $redisResp->parseResp();
    }

    public static function encode($buffer, ConnectionInterface $connection)
    {

    }

    public static function decode($buffer, ConnectionInterface $connection)
    {
        $redisResp = new RedisResp($buffer);
        $connection->redisResp = $redisResp;
        $redisResp->parseResp();
        return $redisResp->response;
    }

    public static function reply($buffer)
    {

    }
}

