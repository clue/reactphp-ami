<?php

namespace Clue\Tests\React\Ami;

use Clue\React\Ami\Factory;
use React\Promise\Promise;

class FactoryTest extends TestCase
{
    private $loop;
    private $tcp;
    private $factory;

    /**
     * @before
     */
    public function setUpFactory()
    {
        $this->loop = $this->getMockBuilder('React\EventLoop\LoopInterface')->getMock();
        $this->tcp = $this->getMockBuilder('React\Socket\ConnectorInterface')->getMock();

        $this->factory = new Factory($this->loop, $this->tcp);
    }

    /**
     * @doesNotPerformAssertions
     */
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

    public function testCreateClientUsesTlsPortForTlsConnection()
    {
        $promise = new Promise(function () { });
        $this->tcp->expects($this->once())->method('connect')->with('tls://localhost:5039')->willReturn($promise);

        $this->factory->createClient('tls://localhost');
    }

    public function testCreateClientUsesTlsConnectorWithTlsLocation()
    {
        $promise = new Promise(function () { });
        $this->tcp->expects($this->once())->method('connect')->with('tls://ami.local:1234')->willReturn($promise);

        $this->factory->createClient('tls://ami.local:1234');
    }

    public function testCreateClientResolvesWithClientWhenConnectionResolves()
    {
        $connection = $this->getMockBuilder('React\Socket\ConnectionInterface')->getMock();
        $this->tcp->expects($this->once())->method('connect')->willReturn(\React\Promise\resolve($connection));

        $promise = $this->factory->createClient('localhost');

        $client = null;
        $promise->then(function ($value) use (&$client) {
            $client = $value;
        });

        $this->assertInstanceOf('Clue\React\Ami\Client', $client);
    }

    public function testCreateClientWithAuthenticationWillSendLoginActionWithDecodedUserInfo()
    {
        $promiseAuthenticated = $this->getMockBuilder('React\Promise\PromiseInterface')->getMock();

        $clientConnected = null;
        $promiseClient = $this->getMockBuilder('React\Promise\PromiseInterface')->getMock();
        $promiseClient->expects($this->once())->method('then')->with($this->callback(function ($callback) use (&$clientConnected) {
            $clientConnected = $callback;
            return true;
        }))->willReturn($promiseAuthenticated);

        $promiseConnecting = $this->getMockBuilder('React\Promise\PromiseInterface')->getMock();
        $promiseConnecting->expects($this->once())->method('then')->willReturn($promiseClient);
        $this->tcp->expects($this->once())->method('connect')->willReturn($promiseConnecting);

        $action = $this->getMockBuilder('Clue\React\Ami\Protocol\Action')->getMock();
        $client = $this->getMockBuilder('Clue\React\Ami\Client')->disableOriginalConstructor()->getMock();
        $client->expects($this->once())->method('createAction')->with('Login', array('UserName' => 'user@host', 'Secret' => 'pass+word!', 'Events' => null))->willReturn($action);
        $client->expects($this->once())->method('request')->with($action)->willReturn($promiseAuthenticated);

        $promise = $this->factory->createClient('user%40host:pass+word%21@localhost');

        $this->assertSame($promiseAuthenticated, $promise);

        $this->assertNotNull($clientConnected);
        $clientConnected($client);
    }

    public function testCreateClientWithInvalidUrlWillRejectPromise()
    {
        $promise = $this->factory->createClient('///');

        $promise->then(null, $this->expectCallableOnce());
    }
}
