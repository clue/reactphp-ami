<?php

namespace Clue\Tests\React\Ami;

use Clue\React\Ami\Factory;
use React\Promise\Promise;

class FactoryTest extends TestCase
{
    private $tcp;
    private $factory;

    /**
     * @before
     */
    public function setUpFactory()
    {
        $this->tcp = $this->getMockBuilder('React\Socket\ConnectorInterface')->getMock();

        $this->factory = new Factory(null, $this->tcp);
    }

    public function testDefaultCtorCreatesConnectorAutomatically()
    {
        $this->factory = new Factory();

        $ref = new \ReflectionProperty($this->factory, 'connector');
        if (PHP_VERSION_ID < 80100) {
            $ref->setAccessible(true);
        }
        $connector = $ref->getValue($this->factory);

        $this->assertInstanceOf('React\Socket\Connector', $connector);
    }

    public function testCtorThrowsForInvalidLoop()
    {
        $this->setExpectedException('InvalidArgumentException', 'Argument #1 ($loop) expected null|React\EventLoop\LoopInterface');
        new Factory('loop');
    }

    public function testCtorThrowsForInvalidConnector()
    {
        $this->setExpectedException('InvalidArgumentException', 'Argument #2 ($connector) expected null|React\Socket\ConnectorInterface');
        new Factory(null, 'connector');
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

    public function testCreateClientWithAuthenticationResolvesWhenAuthenticationSucceeds()
    {
        $action = $this->getMockBuilder('Clue\React\Ami\Protocol\Action')->getMock();
        $client = $this->getMockBuilder('Clue\React\Ami\Client')->disableOriginalConstructor()->getMock();
        $client->expects($this->once())->method('createAction')->willReturn($action);
        $client->expects($this->once())->method('request')->with($action)->willReturn(\React\Promise\resolve('ignored'));

        $promiseConnecting = $this->getMockBuilder('React\Promise\PromiseInterface')->getMock();
        $promiseConnecting->expects($this->once())->method('then')->willReturn(\React\Promise\resolve($client));
        $this->tcp->expects($this->once())->method('connect')->willReturn($promiseConnecting);

        $promise = $this->factory->createClient('user%40host:pass+word%21@localhost');

        $client = null;
        $promise->then(function ($value) use (&$client) {
            $client = $value;
        });

        $this->assertInstanceOf('Clue\React\Ami\Client', $client);
    }

    public function testCreateClientWithAuthenticationWillCloseClientAndRejectWhenLoginRequestRejects()
    {
        $error = new \RuntimeException();
        $action = $this->getMockBuilder('Clue\React\Ami\Protocol\Action')->getMock();
        $client = $this->getMockBuilder('Clue\React\Ami\Client')->disableOriginalConstructor()->getMock();
        $client->expects($this->once())->method('createAction')->willReturn($action);
        $client->expects($this->once())->method('request')->with($action)->willReturn(\React\Promise\reject($error));
        $client->expects($this->once())->method('close');

        $promiseConnecting = $this->getMockBuilder('React\Promise\PromiseInterface')->getMock();
        $promiseConnecting->expects($this->once())->method('then')->willReturn(\React\Promise\resolve($client));
        $this->tcp->expects($this->once())->method('connect')->willReturn($promiseConnecting);

        $promise = $this->factory->createClient('user%40host:pass+word%21@localhost');

        $exception = null;
        $promise->then(null, function ($reason) use (&$exception) {
            $exception = $reason;
        });
        $this->assertSame($error, $exception);
    }

    public function testCreateClientWithInvalidUrlWillRejectPromise()
    {
        $promise = $this->factory->createClient('///');

        $promise->then(null, $this->expectCallableOnce());
    }
}
