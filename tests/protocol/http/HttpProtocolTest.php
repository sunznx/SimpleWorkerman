<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../vendor/autoload.php';

class HttpProtocolTest extends TestCase
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

    public function test_index_php()
    {
        $this->assertEquals($this->errno, 0, $this->errmsg);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, false);
        curl_setopt($ch, CURLOPT_URL, "http://localhost:9999/index.php");
        $result = curl_exec($ch);
        curl_close($ch);

        $buff = json_encode(['status' => 0,'ok']);
        $this->assertEquals($buff, $result);
    }

    public function test_index_html()
    {
        $this->assertEquals($this->errno, 0, $this->errmsg);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, false);
        curl_setopt($ch, CURLOPT_URL, "http://localhost:9999/index.html");
        $result = curl_exec($ch);
        curl_close($ch);

        $buff = 'ok';
        $this->assertEquals($buff, $result);
    }

    protected function tearDown()
    {
        socket_close($this->socket);
    }
}
