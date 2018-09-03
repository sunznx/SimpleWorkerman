<?php

use PHPUnit\Framework\TestCase;
use Sunznx\SimpleWorkerman\Protocol\Redis\RedisResp;

class RedisProtocolTest extends TestCase
{
    public function setUp()
    {

    }

    public function test_parse_encode()
    {
        $res = RedisResp::replyString("abc");
        $this->assertEquals("+abc\r\n", $res);

        $res = RedisResp::replyError("abc");
        $this->assertEquals("-abc\r\n", $res);

        $res = RedisResp::replyInteger(100);
        $this->assertEquals(":100\r\n", $res);

        $res = RedisResp::replyInteger(-100);
        $this->assertEquals(":-100\r\n", $res);

        $res = RedisResp::replyBulkString("abc");
        $this->assertEquals("$3\r\nabc\r\n", $res);

        $res = RedisResp::replyArray([]);
        $this->assertEquals("*0\r\n", $res);

        $res = RedisResp::replyArray(null);
        $this->assertEquals("*-1\r\n", $res);

        $res = RedisResp::replyArray(['a', 'b', 'c']);
        $this->assertEquals("*3\r\n$1\r\na\r\n$1\r\nb\r\n$1\r\nc\r\n", $res);

        $res = RedisResp::replyArray(['a', 2, 'c']);
        $this->assertEquals("*3\r\n$1\r\na\r\n:2\r\n$1\r\nc\r\n", $res);

        $res = RedisResp::replyArray(['a', 2, 'c', [null]]);
        $this->assertEquals("*4\r\n$1\r\na\r\n:2\r\n$1\r\nc\r\n*1\r\n$-1\r\n", $res);

        $res = RedisResp::replyArray(['a', 2, 'c', null]);
        $this->assertEquals("*4\r\n$1\r\na\r\n:2\r\n$1\r\nc\r\n$-1\r\n", $res);
    }

    public function test_parse_string()
    {
        $buffer = "+abc\r\n+abcbcd\r\n$-1\r\n*2\r\n$1\r\na\r\n$-1\r\n";
        $resp = new RedisResp($buffer);

        $this->assertEquals(6, $resp->parseResp());
        $this->assertEquals("abc", $resp->response);

        $this->assertEquals(9, $resp->parseResp());
        $this->assertEquals("abcbcd", $resp->response);

        $this->assertEquals(5, $resp->parseResp());
        $this->assertNull($resp->response);

        $this->assertEquals(16, $resp->parseResp());
        $this->assertEquals(["a", null], $resp->response);
    }

    public function test_parse_bulk_string()
    {
        $buffer = "\$3\r\nabc\r\n";
        $resp = new RedisResp($buffer);

        $this->assertEquals(strlen($buffer), $resp->parseResp());
        $this->assertEquals("", $resp->buffer);
        $this->assertEquals("abc", $resp->response);
    }

    public function test_parse_array()
    {
        $buffer = "*2\r\n$4\r\ntest\r\n*2\r\n$4\r\ntest\r\n$6\r\nsecond\r\n";
        $resp = new RedisResp($buffer);
        $this->assertEquals(strlen($buffer), $resp->parseResp());
        var_dump($resp->response);
    }

    public function tearDown()
    {

    }
}