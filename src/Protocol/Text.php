<?php

namespace Sunznx\SimpleWorkerman\Protocol;

use Sunznx\SimpleWorkerman\Connection\ConnectionInterface;
use Sunznx\SimpleWorkerman\Connection\TcpConnection;

class Text implements ProtocolInterface
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

        $pos = strpos($buffer, "\n");
        if ($pos === false) {
            return 0;
        }

        return $pos + 1;
    }

    public static function encode($buffer, ConnectionInterface $connection)
    {
        return $buffer . "\n";
    }

    public static function decode($buffer, ConnectionInterface $connection)
    {
        return trim($buffer);
    }
}

