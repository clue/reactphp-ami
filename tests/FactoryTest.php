<?php

use Clue\React\Ami\Factory;
use React\Promise\Promise;

class FactoryTest extends TestCase
{
    private $loop;
    private $tcp;
    private $factory;

    public function setUp()
    {
        $this->loop = $this->getMockBuilder('React\EventLoop\LoopInterface')->getMock();
        $this->tcp = $this->getMockBuilder('React\Socket\ConnectorInterface')->getMock();

        $this->factory = new Factory($this->loop, $this->tcp);
    }

    public function testDefaultCtor()
    {
        $this->factory = new Factory($this->loop);
    }

    public function testCreateClientUsesDefaultPortForTcpConnection()
    {
        $promise = new Promise(function () { });
        $this->tcp->expects($this->once())->method('connect')->with('tcp://localhost:5038')->willReturn($promise);

        $this->factory->createClient('localhost');
    }

    public function testCreateClientUsesTlsConnectorWithTlsLocation()
    {
        $promise = new Promise(function () { });
        $this->tcp->expects($this->once())->method('connect')->with('tls://ami.local:1234')->willReturn($promise);

        $this->factory->createClient('tls://ami.local:1234');
    }

    public function testCreateClientWithInvalidUrlWillRejectPromise()
    {
        $promise = $this->factory->createClient('///');

        $promise->then(null, $this->expectCallableOnce());
    }
}
