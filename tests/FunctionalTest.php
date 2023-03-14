<?php

namespace Clue\Tests\React\Ami;

use Clue\React\Ami\ActionSender;
use Clue\React\Ami\Client;
use Clue\React\Ami\Factory;
use Clue\React\Ami\Protocol\Response;
use React\EventLoop\Loop;

class FunctionalTest extends TestCase
{
    private static $address = false;
    private static $loop;

    /**
     * @beforeClass
     */
    public static function setUpLoopBeforeClass()
    {
        self::$address = getenv('LOGIN');
    }

    /**
     * @before
     */
    public function setUpSkipTest()
    {
        if (self::$address === false) {
            $this->markTestSkipped('No ENV named LOGIN found. Please use "export LOGIN=\'user:pass@host\'.');
        }
    }

    public function testConnection()
    {
        $factory = new Factory();

        $client = \React\Async\await($factory->createClient(self::$address));
        assert($client instanceof Client);

        // let loop tick for reactphp/async v4 to clean up any remaining references
        // @link https://github.com/reactphp/async/pull/65 reported upstream // TODO remove me once merged
        if (function_exists('React\Async\async')) {
            \React\Async\delay(0.0);
        }

        $this->assertFalse($client->isBusy());

        return $client;
    }

    /**
     * @depends testConnection
     * @param Client $client
     */
    public function testPing(Client $client)
    {
        $sender = new ActionSender($client);

        $pong = \React\Async\await($sender->ping());

        $this->assertInstanceOf('Clue\React\Ami\Protocol\Response', $pong);
    }

    /**
     * @depends testConnection
     * @param Client $client
     */
    public function testInvalidCommandGetsRejected(Client $client)
    {
        $this->setExpectedException('Exception');
        \React\Async\await($client->request($client->createAction('Invalid')));
    }

    /**
     * @depends testConnection
     * @param Client $client
     */
    public function testActionSenderLogoffDisconnects(Client $client)
    {
        $sender = new ActionSender($client);

        $ret = \React\Async\await($sender->logoff());
        assert($ret instanceof Response);

        // let loop tick for reactphp/async v4 to clean up any remaining references
        // @link https://github.com/reactphp/async/pull/65 reported upstream // TODO remove me once merged
        if (function_exists('React\Async\async')) {
            \React\Async\delay(0.0);
        }

        $this->assertFalse($client->isBusy());

        //$client->on('close', $this->expectCallableOnce());

        Loop::run();

        return $client;
    }

    /**
     * @depends testActionSenderLogoffDisconnects
     * @param Client $client
     */
    public function testSendRejectedAfterClose(Client $client)
    {
        $this->setExpectedException('Exception');
        \React\Async\await($client->request($client->createAction('Ping')));
    }
}
