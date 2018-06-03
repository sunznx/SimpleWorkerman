<?php
/**
 * Created by PhpStorm.
 * User: sunx
 * Date: 2018/6/3
 * Time: 22:08
 */

use PHPUnit\Framework\TestCase;
use SimpleWorkerman\Connection\TcpConnection;
use SimpleWorkerman\Worker;

require_once __DIR__ . '/../vendor/autoload.php';

class TextProtocolTest extends TestCase
{
    protected $socket;
    protected $client_socket;
    protected $conn;
    protected $errno;

    const ADDRESS = "0.0.0.0";
    const PORT = "9999";

    protected function setUp()
    {
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        $this->client_socket = socket_connect($this->socket, self::ADDRESS, self::PORT);
        $this->errno = socket_last_error($this->socket);
    }

    public function test_send()
    {
        $this->assertEquals($this->errno, 0);
        $buff = "text1";
        socket_write($this->socket, $buff, strlen($buff));
        sleep(1);
        $buff = "text2";
        socket_write($this->socket, $buff, strlen($buff));
        sleep(1);
        $buff = "\n";
        socket_write($this->socket, $buff, strlen($buff));

        $recv = socket_read($this->socket, 65535);
        $this->assertEquals("text1text2", trim($recv));
    }

    protected function tearDown()
    {
        socket_close($this->socket);
    }
}
