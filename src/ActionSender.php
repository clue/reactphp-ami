<?php

namespace Clue\React\Ami;

use Clue\React\Ami\Protocol\Collection;
use Clue\React\Ami\Protocol\Event;
use Clue\React\Ami\Protocol\Response;
use React\Promise\Deferred;

/**
 * The `ActionSender` wraps a given [`Client`](#client) instance to provide a simple way to execute common actions.
 * This class represents the main interface to execute actions and wait for the corresponding responses.
 *
 * ```php
 * $sender = new Clue\React\Ami\ActionSender($client);
 * ```
 */
class ActionSender
{
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @param string $username
     * @param string $secret
     * @param ?bool  $events
     * @return \React\Promise\PromiseInterface<Response,\Exception>
     * @link https://wiki.asterisk.org/wiki/display/AST/Asterisk+14+ManagerAction_Login
     */
    public function login($username, $secret, $events = null)
    {
        $events = $this->boolParam($events);
        return $this->request('Login', array('UserName' => $username, 'Secret' => $secret, 'Events' => $events));
    }

    /**
     * @return \React\Promise\PromiseInterface<Response,\Exception>
     * @link https://wiki.asterisk.org/wiki/display/AST/Asterisk+14+ManagerAction_Logoff
     */
    public function logoff()
    {
        return $this->request('Logoff');
    }

    /**
     * @param string $agentId
     * @param bool   $soft
     * @return \React\Promise\PromiseInterface<Response,\Exception>
     * @link https://wiki.asterisk.org/wiki/display/AST/Asterisk+14+ManagerAction_AgentLogoff
     */
    public function agentLogoff($agentId, $soft = false)
    {
        $bool = $soft ? 'true' : 'false';
        return $this->request('AgentLogoff', array('Agent' => $agentId, 'Soft' => $bool));
    }

    /**
     * @return \React\Promise\PromiseInterface<Response,\Exception>
     * @link https://wiki.asterisk.org/wiki/display/AST/Asterisk+14+ManagerAction_Ping
     */
    public function ping()
    {
        return $this->request('Ping');
    }

    /**
     * @param string $command
     * @return \React\Promise\PromiseInterface<Response,\Exception>
     * @link https://wiki.asterisk.org/wiki/display/AST/Asterisk+14+ManagerAction_Command
     */
    public function command($command)
    {
        return $this->request('Command', array('Command' => $command));
    }

    /**
     * @param bool|string[] $eventMask
     * @return \React\Promise\PromiseInterface<Response,\Exception>
     * @link https://wiki.asterisk.org/wiki/display/AST/Asterisk+14+ManagerAction_Events
     */
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

    /**
     * @param string $peerName
     * @return \React\Promise\PromiseInterface<Response,\Exception>
     * @link https://wiki.asterisk.org/wiki/display/AST/Asterisk+14+ManagerAction_SIPshowpeer
     */
    public function sipShowPeer($peerName)
    {
        return $this->request('SIPshowpeer', array('Peer' => $peerName));
    }

    /**
     * @return \React\Promise\PromiseInterface<Response,\Exception>
     * @link https://wiki.asterisk.org/wiki/display/AST/Asterisk+14+ManagerAction_ListCommands
     */
    public function listCommands()
    {
        return $this->request('ListCommands');
    }

    /**
     * @param string $channel
     * @param string $message
     * @return \React\Promise\PromiseInterface<Response,\Exception>
     * @link https://wiki.asterisk.org/wiki/display/AST/Asterisk+14+ManagerAction_SendText
     */
    public function sendText($channel, $message)
    {
        return $this->request('Sendtext', array('Channel' => $channel, 'Message' => $message));
    }

    /**
     * @param string $channel
     * @param int $cause
     * @return \React\Promise\PromiseInterface<Response,\Exception>
     * @link https://wiki.asterisk.org/wiki/display/AST/Asterisk+14+ManagerAction_Hangup
     */
    public function hangup($channel, $cause)
    {
        return $this->request('Hangup', array('Channel' => $channel, 'Cause' => (string) $cause));
    }

    /**
     * @param string $authType
     * @return \React\Promise\PromiseInterface<Response,\Exception>
     * @link https://wiki.asterisk.org/wiki/display/AST/Asterisk+14+ManagerAction_Challenge
     */
    public function challenge($authType = 'MD5')
    {
        return $this->request('Challenge', array('AuthType' => $authType));
    }

    /**
     * @param string  $filename
     * @param ?string $category
     * @return \React\Promise\PromiseInterface<Response,\Exception>
     * @link https://wiki.asterisk.org/wiki/display/AST/Asterisk+14+ManagerAction_GetConfig
     */
    public function getConfig($filename, $category = null)
    {
        return $this->request('GetConfig', array('Filename' => $filename, 'Category' => $category));
    }

    /**
     * @return \React\Promise\PromiseInterface<Collection,\Exception> collection with "Event: CoreShowChannel"
     * @link https://wiki.asterisk.org/wiki/display/AST/Asterisk+14+ManagerAction_CoreShowChannels
     */
    public function coreShowChannels()
    {
        return $this->collectEvents('CoreShowChannels', 'CoreShowChannelsComplete');
    }

    /**
     * @return \React\Promise\PromiseInterface<Collection,\Exception> collection with "Event: PeerEntry"
     * @link https://wiki.asterisk.org/wiki/display/AST/Asterisk+14+ManagerAction_SIPpeers
     */
    public function sipPeers()
    {
        return $this->collectEvents('SIPPeers', 'PeerlistComplete');
    }

    /**
     * @return \React\Promise\PromiseInterface<Collection,\Exception>collection with "Event: Agents"
     * @link https://wiki.asterisk.org/wiki/display/AST/Asterisk+14+ManagerAction_Agents
     */
    public function agents()
    {
        return $this->collectEvents('Agents', 'AgentsComplete');
    }

    /**
     * @return \React\Promise\PromiseInterface<Collection,\Exception>collection with "Event: ExtensionStatus"
     * @link https://wiki.asterisk.org/wiki/display/AST/Asterisk+14+ManagerAction_ExtensionStateList
     */
    public function extensionStates()
    {
        return $this->collectEvents('ExtensionStateList', 'ExtensionStateListComplete');
    }

    /**
     * @param mixed $value
     * @return ?string
     */
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

    /**
     * @param string $name
     * @param array<string,string|string[]|null> $args
     * @return \React\Promise\PromiseInterface<Response,\Exception>
     */
    private function request($name, array $args = array())
    {
        return $this->client->request($this->client->createAction($name, $args));
    }

    /**
     * @param string $command
     * @param string $expectedEndEvent
     * @return \React\Promise\PromiseInterface<Collection,\Exception>
     */
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
