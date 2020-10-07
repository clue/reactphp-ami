<?php

namespace Clue\Tests\React\Ami;

use Clue\React\Ami\Factory;
use Clue\React\Ami\Client;
use Clue\React\Ami\ActionSender;
use Clue\React\Block;
use React\Promise\PromiseInterface;

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
        self::$loop = \React\EventLoop\Factory::create();
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
        $factory = new Factory(self::$loop);

        $client = $this->waitFor($factory->createClient(self::$address));
        /* @var $client Client */

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

        $pong = $this->waitFor($sender->ping());

        $this->assertInstanceOf('Clue\React\Ami\Protocol\Response', $pong);
    }

    /**
     * @depends testConnection
     * @param Client $client
     */
    public function testInvalidCommandGetsRejected(Client $client)
    {
        $this->setExpectedException('Exception');
        $this->waitFor($client->request($client->createAction('Invalid')));
    }

    /**
     * @depends testConnection
     * @param Client $client
     */
    public function testActionSenderLogoffDisconnects(Client $client)
    {
        $sender = new ActionSender($client);

        $ret = $this->waitFor($sender->logoff());

        $this->assertInstanceOf('Clue\React\Ami\Protocol\Response', $ret);

        $this->assertFalse($client->isBusy());

        //$client->on('close', $this->expectCallableOnce());

        self::$loop->run();

        return $client;
    }

    /**
     * @depends testActionSenderLogoffDisconnects
     * @param Client $client
     */
    public function testSendRejectedAfterClose(Client $client)
    {
        $this->setExpectedException('Exception');
        $this->waitFor($client->request($client->createAction('Ping')));
    }

    private function waitFor(PromiseInterface $promise)
    {
        return Block\await($promise, self::$loop, 5.0);
    }
}
