<?php

use PHPUnit\Framework\TestCase;
use SimpleWorkerman\Connection\TcpConnection;
use SimpleWorkerman\Worker;

require_once __DIR__ . '/../../../vendor/autoload.php';

class TextProtocolTest extends TestCase
{
    protected $socket;
    protected $client_socket;
    protected $conn;
    protected $errno;

    protected $address = "0.0.0.0";
    protected $port = "9999";

    protected $errmsg = <<< 'EOF'
将下面的内容添加到 start.php 中
    
```    
<?php

use SimpleWorkerman\Worker;
use SimpleWorkerman\Connection\ConnectionInterface;

require_once __DIR__ . '/vendor/autoload.php';

$worker = new Worker('text://0.0.0.0:9999');
$worker->onMessage = function (ConnectionInterface $connection, $data) {
    $connection->send($data);
};
$worker->run();
```

然后运行 php start.php start
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
