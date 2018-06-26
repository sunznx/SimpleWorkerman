<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../vendor/autoload.php';

class JsonIntProtocolTest extends TestCase
{
    protected $socket;
    protected $client_socket;
    protected $conn;
    protected $errno;

    protected $address = "0.0.0.0";
    protected $port = "9999";

    protected $errmsg = <<< 'EOF'
运行 php start.php start
EOF;

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
        $len = strlen($buff) + 4;
        $buff = pack("Na*", $len, $buff);

        $str1 = substr($buff, 0, 3);
        $str2 = substr($buff, 3);
        socket_write($this->socket, $str1, strlen($str1));
        sleep(3);
        socket_write($this->socket, $str2, strlen($str2));
        $recv = socket_read($this->socket, 65535);

        $res = unpack("Nlen/a*str", $recv);
        $this->assertEquals($res['str'], json_encode($arr));
    }

    protected function tearDown()
    {
        socket_close($this->socket);
    }
}
