<?php

use Clue\React\Ami\Factory;
use React\Promise\Promise;
class FactoryTest extends TestCase
{
    private $loop;
    private $tcp;
    private $tls;
    private $factory;

    public function setUp()
    {
        $this->loop = $this->getMockBuilder('React\EventLoop\LoopInterface')->getMock();
        $this->tcp = $this->getMockBuilder('React\SocketClient\ConnectorInterface')->getMock();
        $this->tls = $this->getMockBuilder('React\SocketClient\ConnectorInterface')->getMock();

        $this->factory = new Factory($this->loop, $this->tcp, $this->tls);
    }

    public function testDefaultCtor()
    {
        $this->factory = new Factory($this->loop);
    }

    public function testCreateClientUsesTcpConnectorWithDefaultLocation()
    {
        $promise = new Promise(function () { });
        $this->tcp->expects($this->once())->method('create')->with('127.0.0.1', 5038)->willReturn($promise);

        $this->factory->createClient();
    }

    public function testCreateClientUsesTcpConnectorWithLocalhostLocation()
    {
        $promise = new Promise(function () { });
        $this->tcp->expects($this->once())->method('create')->with('127.0.0.1', 5038)->willReturn($promise);

        $this->factory->createClient('localhost');
    }

    public function testCreateClientUsesTlsConnectorWithTlsLocation()
    {
        $promise = new Promise(function () { });
        $this->tls->expects($this->once())->method('create')->with('ami.local', 1234)->willReturn($promise);

        $this->factory->createClient('tls://ami.local:1234');
    }
}
