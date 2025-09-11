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
    protected $client;

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
        }
        elseif ($eventMask === true) {
            $eventMask = 'on';
        }
        else {
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
     * @param mixed $value
     * @return ?string
     */
    protected function boolParam($value)
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
    protected function request($name, array $args = array())
    {
        return $this->client->request($this->client->createAction($name, $args));
    }

    /**
     * @param string $command
     * @param string $expectedEndEvent
     * @return \React\Promise\PromiseInterface<Collection,\Exception>
     */
    protected function collectEvents($command, $expectedEndEvent)
    {
        $req = $this->client->createAction($command);
        $ret = $this->client->request($req);
        $id = $req->getActionId();

        $deferred = new Deferred();

        // collect all intermediary channel events with this action ID
        $collected = array();
        $collector = function (Event $event) use ($id, &$collected, $deferred, $expectedEndEvent) {
            if ($event->getActionId() === $id) {
                $collected[] = $event;

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

    /**
     * Collect list-style events for actions that use the standard EventList lifecycle.
     *
     * Resolves when an event with field "EventList: Complete" is received for the same ActionID.
     *
     * @param string $command
     * @return \React\Promise\PromiseInterface<Collection,\Exception>
     */
    protected function collectEventsAuto($command)
    {
        $req = $this->client->createAction($command);
        $ret = $this->client->request($req);
        $id = $req->getActionId();

        $deferred = new Deferred();

        $collected = array();
        $collector = function (Event $event) use ($id, &$collected, $deferred) {
            if ($event->getActionId() === $id) {
                $collected[] = $event;

                if ($event->getFieldValue('EventList') === 'Complete') {
                    $deferred->resolve($collected);
                }
            }
        };
        $this->client->on('event', $collector);

        $client = $this->client;
        $unregister = function () use ($client, $collector) {
            $client->removeListener('event', $collector);
        };
        $ret->then(null, $unregister);
        $deferred->promise()->then($unregister);

        return $ret->then(function (Response $response) use ($deferred) {
            return $deferred->promise()->then(function ($collected) use ($response) {
                $last = array_pop($collected);
                return new Collection($response, $collected, $last);
            });
        });
    }

    /**
     * Same as collectEventsAuto() but allows passing arguments to the action and
     * ensures a single request is created so ActionID matches collected events.
     *
     * @param string $command
     * @param array<string,string|string[]|null> $args
     * @return \React\Promise\PromiseInterface<Collection,\Exception>
     */
    protected function collectEventsAutoWithArgs($command, array $args = array())
    {
        $req = $this->client->createAction($command, $args);
        $ret = $this->client->request($req);
        $id = $req->getActionId();

        $deferred = new Deferred();

        $collected = array();
        $collector = function (Event $event) use ($id, &$collected, $deferred) {
            if ($event->getActionId() === $id) {
                $collected[] = $event;

                if ($event->getFieldValue('EventList') === 'Complete') {
                    $deferred->resolve($collected);
                }
            }
        };
        $this->client->on('event', $collector);

        $client = $this->client;
        $unregister = function () use ($client, $collector) {
            $client->removeListener('event', $collector);
        };
        $ret->then(null, $unregister);
        $deferred->promise()->then($unregister);

        return $ret->then(function (Response $response) use ($deferred) {
            return $deferred->promise()->then(function ($collected) use ($response) {
                $last = array_pop($collected);
                return new Collection($response, $collected, $last);
            });
        });
    }

    /**
     * @return \React\Promise\PromiseInterface<Collection,\Exception> collection with "Event: Status"
     * @link https://docs.asterisk.org/Asterisk_18_Documentation/API_Documentation/AMI_Actions/Status/
     */
    public function status()
    {
        return $this->collectEventsAuto('Status');
    }

    /**
     * @param ?string $queue
     * @param ?string $member
     * @return \React\Promise\PromiseInterface<Collection,\Exception> collection with queue status events
     * @link https://docs.asterisk.org/Asterisk_18_Documentation/API_Documentation/AMI_Actions/QueueStatus/
     */
    public function queueStatus($queue = null, $member = null)
    {
        $args = array('Queue' => $queue, 'Member' => $member);
        return $this->collectEventsAutoWithArgs('QueueStatus', $args);
    }

    /**
     * @param ?string $queue
     * @return \React\Promise\PromiseInterface<Collection,\Exception> collection with queue summaries
     * @link https://docs.asterisk.org/Asterisk_18_Documentation/API_Documentation/AMI_Actions/QueueSummary/
     */
    public function queueSummary($queue = null)
    {
        $args = array('Queue' => $queue);
        return $this->collectEventsAutoWithArgs('QueueSummary', $args);
    }

    /**
     * @return \React\Promise\PromiseInterface<Collection,\Exception> collection with parked calls
     * @link https://docs.asterisk.org/Asterisk_18_Documentation/API_Documentation/AMI_Actions/ParkedCalls/
     */
    public function parkedCalls()
    {
        return $this->collectEventsAuto('ParkedCalls');
    }

    /**
     * @return \React\Promise\PromiseInterface<Collection,\Exception> collection with parking lots
     * @link https://docs.asterisk.org/Asterisk_18_Documentation/API_Documentation/AMI_Actions/Parkinglots/
     */
    public function parkinglots()
    {
        return $this->collectEventsAuto('Parkinglots');
    }

    /**
     * @return \React\Promise\PromiseInterface<Collection,\Exception> collection with bridges
     * @link https://docs.asterisk.org/Asterisk_18_Documentation/API_Documentation/AMI_Actions/BridgeList/
     */
    public function bridgeList()
    {
        return $this->collectEventsAuto('BridgeList');
    }

    /**
     * @return \React\Promise\PromiseInterface<Collection,\Exception> collection with bridge technologies
     * @link https://docs.asterisk.org/Asterisk_18_Documentation/API_Documentation/AMI_Actions/BridgeTechnologyList/
     */
    public function bridgeTechnologyList()
    {
        return $this->collectEventsAuto('BridgeTechnologyList');
    }

    /**
     * @param string $conference
     * @return \React\Promise\PromiseInterface<Collection,\Exception> collection with ConfBridge participants
     * @link https://docs.asterisk.org/Asterisk_18_Documentation/API_Documentation/AMI_Actions/ConfbridgeList/
     */
    public function confbridgeList($conference)
    {
        return $this->collectEventsAutoWithArgs('ConfbridgeList', array('Conference' => $conference));
    }

    /**
     * @return \React\Promise\PromiseInterface<Collection,\Exception> collection with ConfBridge rooms
     * @link https://docs.asterisk.org/Asterisk_18_Documentation/API_Documentation/AMI_Actions/ConfbridgeListRooms/
     */
    public function confbridgeListRooms()
    {
        return $this->collectEventsAuto('ConfbridgeListRooms');
    }

    /**
     * @return \React\Promise\PromiseInterface<Collection,\Exception>
     * @link https://docs.asterisk.org/Asterisk_18_Documentation/API_Documentation/AMI_Actions/DeviceStateList/
     */
    public function deviceStateList()
    {
        return $this->collectEventsAuto('DeviceStateList');
    }

    /**
     * @return \React\Promise\PromiseInterface<Collection,\Exception>
     * @link https://docs.asterisk.org/Asterisk_18_Documentation/API_Documentation/AMI_Actions/ExtensionStateList/
     */
    public function extensionStateList()
    {
        return $this->collectEventsAuto('ExtensionStateList');
    }

    /**
     * @return \React\Promise\PromiseInterface<Collection,\Exception>
     * @link https://docs.asterisk.org/Asterisk_18_Documentation/API_Documentation/AMI_Actions/SIPshowregistry/
     */
    public function sipShowRegistry()
    {
        return $this->collectEventsAuto('SIPshowregistry');
    }

    /**
     * @return \React\Promise\PromiseInterface<Collection,\Exception>
     * @link https://docs.asterisk.org/Asterisk_18_Documentation/API_Documentation/AMI_Actions/IAXpeerlist/
     */
    public function iaxPeerlist()
    {
        return $this->collectEventsAuto('IAXpeerlist');
    }

    /**
     * @return \React\Promise\PromiseInterface<Collection,\Exception> collection with PJSIP endpoints
     * @link https://docs.asterisk.org/Asterisk_18_Documentation/API_Documentation/AMI_Actions/PJSIPShowEndpoints/
     */
    public function pjsipShowEndpoints()
    {
        return $this->collectEventsAuto('PJSIPShowEndpoints');
    }

    /**
     * @return \React\Promise\PromiseInterface<Collection,\Exception> collection with PJSIP AORs
     * @link https://docs.asterisk.org/Asterisk_18_Documentation/API_Documentation/AMI_Actions/PJSIPShowAors/
     */
    public function pjsipShowAors()
    {
        return $this->collectEventsAuto('PJSIPShowAors');
    }

    /**
     * @return \React\Promise\PromiseInterface<Collection,\Exception> collection with PJSIP contacts
     * @link https://docs.asterisk.org/Asterisk_18_Documentation/API_Documentation/AMI_Actions/PJSIPShowContacts/
     */
    public function pjsipShowContacts()
    {
        return $this->collectEventsAuto('PJSIPShowContacts');
    }

    /**
     * @return \React\Promise\PromiseInterface<Collection,\Exception>
     * @link https://docs.asterisk.org/Asterisk_18_Documentation/API_Documentation/AMI_Actions/PJSIPShowRegistrationsInbound/
     */
    public function pjsipShowRegistrationsInbound()
    {
        return $this->collectEventsAuto('PJSIPShowRegistrationsInbound');
    }

    /**
     * @return \React\Promise\PromiseInterface<Collection,\Exception>
     * @link https://docs.asterisk.org/Asterisk_18_Documentation/API_Documentation/AMI_Actions/PJSIPShowRegistrationsOutbound/
     */
    public function pjsipShowRegistrationsOutbound()
    {
        return $this->collectEventsAuto('PJSIPShowRegistrationsOutbound');
    }

    /**
     * @return \React\Promise\PromiseInterface<Collection,\Exception>
     * @link https://docs.asterisk.org/Asterisk_18_Documentation/API_Documentation/AMI_Actions/PJSIPShowResourceLists/
     */
    public function pjsipShowResourceLists()
    {
        return $this->collectEventsAuto('PJSIPShowResourceLists');
    }

    /**
     * @return \React\Promise\PromiseInterface<Collection,\Exception>
     * @link https://docs.asterisk.org/Asterisk_18_Documentation/API_Documentation/AMI_Actions/PJSIPShowSubscriptionsInbound/
     */
    public function pjsipShowSubscriptionsInbound()
    {
        return $this->collectEventsAuto('PJSIPShowSubscriptionsInbound');
    }

    /**
     * @return \React\Promise\PromiseInterface<Collection,\Exception>
     * @link https://docs.asterisk.org/Asterisk_18_Documentation/API_Documentation/AMI_Actions/PJSIPShowSubscriptionsOutbound/
     */
    public function pjsipShowSubscriptionsOutbound()
    {
        return $this->collectEventsAuto('PJSIPShowSubscriptionsOutbound');
    }

    /**
     * @param ?string $context
     * @param ?string $extension
     * @param ?int $priority
     * @return \React\Promise\PromiseInterface<Collection,\Exception>
     * @link https://docs.asterisk.org/Asterisk_18_Documentation/API_Documentation/AMI_Actions/ShowDialPlan/
     */
    public function showDialPlan($context = null, $extension = null, $priority = null)
    {
        $args = array('Context' => $context, 'Extension' => $extension, 'Priority' => $priority);
        return $this->collectEventsAutoWithArgs('ShowDialPlan', $args);
    }

    /**
     * @return \React\Promise\PromiseInterface<Collection,\Exception>
     * @link https://docs.asterisk.org/Asterisk_18_Documentation/API_Documentation/AMI_Actions/VoicemailUsersList/
     */
    public function voicemailUsersList()
    {
        return $this->collectEventsAuto('VoicemailUsersList');
    }

}
