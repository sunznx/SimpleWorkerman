<?php

namespace Sunznx\SimpleWorkerman\Protocol;

use Sunznx\SimpleWorkerman\Connection\ConnectionInterface;
use Sunznx\SimpleWorkerman\Connection\TcpConnection;
use Sunznx\SimpleWorkerman\Protocol\Redis\RedisProtocolDecoder;

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

        $redisResp = new RedisProtocolDecoder($buffer);
        $connection->redisResp = $redisResp;
        return $redisResp->parseResp();
    }

    public static function encode($buffer, ConnectionInterface $connection)
    {
        return $buffer;
    }

    public static function decode($buffer, ConnectionInterface $connection)
    {
        $redisResp = new RedisProtocolDecoder($buffer);
        $connection->redisResp = $redisResp;
        $redisResp->parseResp();
        return $redisResp->response;
    }

    public static function replyString($buffer)
    {
        return RedisProtocolDecoder::RESP_STRING . $buffer . RedisProtocolDecoder::CRLF;
    }

    public static function replyError($buffer)
    {
        return RedisProtocolDecoder::RESP_ERROR . $buffer . RedisProtocolDecoder::CRLF;
    }

    public static function replyInteger($buffer)
    {
        return RedisProtocolDecoder::RESP_INTEGER . $buffer . RedisProtocolDecoder::CRLF;
    }

    public static function replyBulkString($buffer)
    {
        if ($buffer === null) {
            return RedisProtocolDecoder::RESP_BULK_STRING . -1 . RedisProtocolDecoder::CRLF;
        }

        return RedisProtocolDecoder::RESP_BULK_STRING . strlen($buffer) . RedisProtocolDecoder::CRLF . $buffer . RedisProtocolDecoder::CRLF;
    }

    public static function replyArray($buffer)
    {
        $res = RedisProtocolDecoder::RESP_ARRAY;

        if ($buffer === null) {
            $res .= -1 . RedisProtocolDecoder::CRLF;
        } else {
            $res .= count($buffer) . RedisProtocolDecoder::CRLF;
            foreach ($buffer as $item) {
                if (is_int($item)) {
                    $res .= self::replyInteger($item);
                } else if ($item === null || is_float($item) || is_string($item)) {
                    $res .= self::replyBulkString($item);
                } else if (is_array($item) || is_object($item)) {
                    $res .= self::replyArray($item);
                }
            }
        }

        return $res;
    }

    public static function reply($buffer)
    {
        if (is_int($buffer)) {
            $res = self::replyInteger($buffer);
        } else if ($buffer === null || is_float($buffer) || is_string($buffer)) {
            $res = self::replyBulkString($buffer);
        } else if (is_array($buffer) || is_object($buffer)) {
            $res = self::replyArray($buffer);
        } else {
            $res = "unknow buffer type";
        }

        return $res;
    }
}

