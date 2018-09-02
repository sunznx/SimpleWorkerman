<?php

namespace Sunznx\SimpleWorkerman\Protocol;

use Sunznx\SimpleWorkerman\Connection\ConnectionInterface;
use Sunznx\SimpleWorkerman\Connection\TcpConnection;

class Json implements ProtocolInterface
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

        $arr = json_decode($buffer, 1);
        if (empty($arr)) {
            return 0;
        }

        return strlen($buffer);
    }

    public static function encode($buffer, ConnectionInterface $connection)
    {
        return json_encode($buffer);
    }

    public static function decode($buffer, ConnectionInterface $connection)
    {
        return json_decode($buffer, 1);
    }
}

