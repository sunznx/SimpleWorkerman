<?php

use PHPUnit\Framework\TestCase;
use Sunznx\SimpleWorkerman\Protocol\Redis\RedisResp;

class RedisProtocolTest extends TestCase
{
    public function setUp()
    {

    }

    public function test_parse_string()
    {
        $buffer = "+abc\r\n+abcbcd\r\n";
        $resp = new RedisResp($buffer);

        $this->assertEquals(6, $resp->parseResp());
        $this->assertEquals("abc", $resp->response);
        $this->assertEquals(9, $resp->parseResp());
        $this->assertEquals("abcbcd", $resp->response);
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