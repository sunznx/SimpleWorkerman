<?php

use PHPUnit\Framework\TestCase;

class JsonrpcTest extends TestCase
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

    public function test_user_info()
    {
        $this->assertEquals($this->errno, 0, $this->errmsg);

        $user_id = 10;

        $arr = [
            'model'  => 'user',
            'method' => 'info',
            'param'  => [$user_id]
        ];

        $buff = json_encode($arr);

        socket_write($this->socket, $buff, strlen($buff));
        $recv = socket_read($this->socket, 65535);
        $this->assertEquals(json_encode([
            'errcode' => 0,
            'data'    => [
                'id'     => $user_id,
                'name'   => 'john',
                'age'    => 13,
                'gender' => 0
            ]
        ]), $recv);
    }

    public function test_user_list()
    {
        $this->assertEquals($this->errno, 0);

        $arr = [
            'model'  => 'user',
            'method' => 'list'
        ];
        $buff = json_encode($arr);

        socket_write($this->socket, $buff, strlen($buff));
        $recv = socket_read($this->socket, 65535);
        $this->assertEquals(json_encode([
            'errcode' => 0,
            'data'    => [
                [
                    'id'   => 1,
                    'name' => 'user1'
                ],
                [
                    'id'   => 2,
                    'name' => 'user2'
                ]
            ]
        ]), $recv);
    }

    protected function tearDown()
    {
        socket_close($this->socket);
    }
}
