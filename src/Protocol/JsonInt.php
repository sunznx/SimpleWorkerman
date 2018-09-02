<?php

namespace Sunznx\SimpleWorkerman\Protocol;

use Sunznx\SimpleWorkerman\Connection\ConnectionInterface;
use Sunznx\SimpleWorkerman\Connection\TcpConnection;

class JsonInt implements ProtocolInterface
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

        $res = unpack("Nlen/a*str", $buffer);
        if (empty($res) || empty($res['len'])) {
            return 0;
        }

        $arr = json_decode($res['str'], 1);
        if (empty($arr)) {
            return 0;
        }

        return strlen($buffer);
    }

    public static function encode($buffer, ConnectionInterface $connection)
    {
        $buffer = json_encode($buffer);
        $len = strlen($buffer) + 4;
        return pack("Na*", $len, $buffer);
    }

    public static function decode($buffer, ConnectionInterface $connection)
    {
        $buffer = substr($buffer, 4);
        return json_decode($buffer, 1);
    }
}

