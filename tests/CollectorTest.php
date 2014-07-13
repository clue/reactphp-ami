<?php

use Clue\React\Ami\Collector;
use Clue\React\Ami\Protocol\Collection;
use Clue\React\Ami\Protocol\Response;
use Clue\React\Ami\Client;
use Clue\React\Ami\Protocol\Event;
use Clue\React\Ami\Protocol\Action;

class CollectorTest extends TestCase
{
    public function testCollectingSIPEvents()
    {
        $client = $this->createClientMock();

        // expect a single outgoing action request (and mock its ID)
        $client->expects($this->once())
                 ->method('createAction')
                 ->with($this->equalTo('SIPPeers'), $this->equalTo(array()))
                 ->will($this->returnValue(new Action(array('Action' => 'SIPPeers', 'ActionID' => '123'))));

        $collector = new Collector($client);

        $promise = $collector->sipPeers();

        // save resolved result for comparisions
        $resolved = null;
        $promise->then(function($result) use (&$resolved) {
            $resolved = $result;
        });

        // should not start out resolved
        $this->assertNull($resolved);

        $response = new Response(array('Response' => 'Success', 'ActionID' => '123'));

        $client->handleMessage($response);
        $client->handleMessage(new Event(array('Event' => 'PeerEntry', 'ActionID' => '123')));
        $client->handleMessage(new Event(array('Event' => 'PeerEntry', 'ActionID' => '123')));

        $this->assertNull($resolved);

        $client->handleMessage(new Event(array('Event' => 'PeerlistComplete', 'EventList' => 'complete', 'ListItems' => '2', 'ActionID' => '123')));

        $this->assertNotNull($resolved);

        $promise->then(
            $this->expectCallableOnce()
        );
    }

    private function createClientMock()
    {
        $stream = $this->getMockBuilder('React\Stream\Stream')->disableOriginalConstructor()->getMock();

        $client = $this->getMockBuilder('Clue\React\Ami\Client')->setMethods(array('createAction'))->setConstructorArgs(array($stream))->getMock();

        return $client;
    }
}
