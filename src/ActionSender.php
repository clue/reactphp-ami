<?php

namespace Clue\React\Ami;

use Clue\React\Ami\Client;
use Clue\React\Ami\Protocol\Collection;
use Clue\React\Ami\Protocol\Event;
use Clue\React\Ami\Protocol\Response;
use React\Promise\Deferred;

class ActionSender
{
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function login($username, $secret, $events = null)
    {
        $events = $this->boolParam($events);
        return $this->request('Login', array('UserName' => $username, 'Secret' => $secret, 'Events' => $events));
    }

    public function logoff()
    {
        return $this->request('Logoff');
    }

    public function agentLogoff($agentId, $soft = false)
    {
        $bool = $soft ? 'true' : 'false';
        return $this->request('AgentLogoff', array('Agent' => $agentId, 'Soft' => $bool));
    }

    public function ping()
    {
        return $this->request('Ping');
    }

    public function command($command)
    {
        return $this->request('Command', array('Command' => $command));
    }

    public function events($eventMask)
    {
        if ($eventMask === false) {
            $eventMask = 'off';
        } elseif ($eventMask === true) {
            $eventMask = 'on';
        } else {
            $eventMask = implode(',', $eventMask);
        }

        return $this->request('Events', array('EventMask' => $eventMask));
    }

    public function sipShowPeer($peerName)
    {
        return $this->request('SIPshowpeer', array('Peer' => $peerName));
    }

    public function listCommands()
    {
        return $this->request('ListCommands');
    }

    public function sendText($channel, $message)
    {
        return $this->request('Sendtext', array('Channel' => $channel, 'Message' => $message));
    }

    public function hangup($channel, $cause)
    {
        return $this->request('Hangup', array('Channel' => $channel, 'Cause' => $cause));
    }

    public function challenge($authType = 'MD5')
    {
        return $this->request('Challenge', array('AuthType' => $authType));
    }

    public function getConfig($filename, $category = null)
    {
        return $this->request('GetConfig', array('Filename' => $filename, 'Category' => $category));
    }

    /**
     * @return \React\Promise\PromiseInterface Promise<Collection> collection with "Event: CoreShowChannel"
     */
    public function coreShowChannels()
    {
        return $this->collectEvents('CoreShowChannels', 'CoreShowChannelsComplete');
    }

    /**
     *
     * @return \React\Promise\PromiseInterface Promise<Collection> collection with "Event: PeerEntry"
     */
    public function sipPeers()
    {
        return $this->collectEvents('SIPPeers', 'PeerlistComplete');
    }

    /**
     *
     * @return \React\Promise\PromiseInterface Promise<Collection> collection with "Event: Agents"
     */
    public function agents()
    {
        return $this->collectEvents('Agents', 'AgentsComplete');
    }

    private function boolParam($value)
    {
        if ($value === true) {
            return 'on';
        }
        if ($value === false) {
            return 'off';
        }
        return null;
    }

    private function request($name, array $args = array())
    {
        return $this->client->request($this->client->createAction($name, $args));
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
