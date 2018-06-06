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

class JsonProtocolTest extends TestCase
{
    protected $socket;
    protected $client_socket;
    protected $conn;
    protected $errno;

    protected $address = "0.0.0.0";
    protected $port = "9999";

    protected function setUp()
    {
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        $this->client_socket = socket_connect($this->socket, $this->address, $this->port);
        $this->errno = socket_last_error($this->socket);
    }

    public function test_send()
    {
        $this->assertEquals($this->errno, 0);

        $arr = ['key' => 'value'];
        $buff = json_encode($arr);

        $str1 = substr($buff, 0, 3);
        $str2 = substr($buff, 3);
        socket_write($this->socket, $str1, strlen($str1));
        sleep(1);
        socket_write($this->socket, $str2, strlen($str2));
        $recv = socket_read($this->socket, 65535);
        $this->assertEquals(json_encode($arr), $recv);
    }

    protected function tearDown()
    {
        socket_close($this->socket);
    }
}
