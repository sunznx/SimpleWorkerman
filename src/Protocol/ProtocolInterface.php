<?php

namespace Sunznx\SimpleWorkerman\Protocol;

use Sunznx\SimpleWorkerman\Connection\ConnectionInterface;

interface ProtocolInterface
{
    public static function getTransport();

    /**
     * @param                     $buffer
     * @param ConnectionInterface $connection
     * @return int
     *
     * return res = 0   表示这个包不完整，还要继续接收
     * return res > 0   表示这个包已经完整，这个包长为 res
     */
    public static function input($buffer, ConnectionInterface $connection);

    public static function decode($buffer, ConnectionInterface $connection);

    public static function encode($buffer, ConnectionInterface $connection);
}