<?php

namespace Clue\React\Ami;

use Clue\React\Ami\Client;
use Clue\React\Ami\Protocol\Response;
use Clue\React\Ami\Protocol\Event;
use Clue\React\Ami\Protocol\Collection;
use React\Promise\Deferred;

class Collector
{
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function coreShowChannels()
    {
        return $this->collectEvents('CoreShowChannels', 'CoreShowChannelsComplete');
    }

    public function sipPeers()
    {
        return $this->collectEvents('SIPPeers', 'PeerlistComplete');
    }

    public function agents()
    {
        return $this->collectEvents('Agents', 'AgentsComplete');
    }

    private function collectEvents($command, $expectedEndEvent)
    {
        $req = $this->client->createAction($command);
        $ret = $this->client->request($req);
        $id = $req->getActionId();

        $deferred = new Deferred();

        // collect all intermediary channel events with this action ID
        $collected = array();
        $collector = function (Event $event) use ($id, &$collected, $deferred, $expectedEndEvent) {
            if ($event->getActionId() === $id) {
                $collected []= $event;

                if ($event->getName() === $expectedEndEvent) {
                    $deferred->resolve($collected);
                }
            }
        };
        $this->client->on('event', $collector);

        // unregister collector if client fails
        $client = $this->client;
        $unregister = function () use ($client, $collector) {
            $client->removeListener('event', $collector);
        };
        $ret->then(null, $unregister);

        // stop waiting for events
        $deferred->promise()->then($unregister);

        return $ret->then(function (Response $response) use ($deferred) {
            // final result has been received => merge all intermediary channel events
            return $deferred->promise()->then(function ($collected) use ($response) {
                $last = array_pop($collected);
                return new Collection($response, $collected, $last);
            });
        });
    }
}
