<?php

use Clue\React\Ami\Factory;
use React\Promise\PromiseInterface;
use Clue\React\Ami\Client;
use Clue\React\Ami\ActionSender;
use Clue\React\Ami\Protocol\Response;

class FunctionalTest extends TestCase
{
    private static $address = false;
    private static $loop;

    public static function setUpBeforeClass()
    {
        self::$address = getenv('LOGIN');
        self::$loop = React\EventLoop\Factory::create();
    }

    public function setUp()
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
        /* @var $pong Response */
    }

    /**
     * @depends testConnection
     * @param Client $client
     * @expectedException Exception
     */
    public function testInvalidCommandGetsRejected(Client $client)
    {
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
        /* @var $ret Response */

        $this->assertFalse($client->isBusy());

        //$client->on('close', $this->expectCallableOnce());

        self::$loop->run();

        return $client;
    }

    /**
     * @depends testActionSenderLogoffDisconnects
     * @param Client $client
     * @expectedException Exception
     */
    public function testSendRejectedAfterClose(Client $client)
    {
        $this->waitFor($client->request($client->createAction('Ping')));
    }

    private function waitFor(PromiseInterface $promise)
    {
        $resolved = null;
        $exception = null;

        $promise->then(function ($c) use (&$resolved) {
            $resolved = $c;
        }, function($error) use (&$exception) {
            $exception = $error;
        });

        while ($resolved === null && $exception === null) {
            self::$loop->tick();
        }

        if ($exception !== null) {
            throw $exception;
        }

        return $resolved;
    }
}
