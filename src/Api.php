<?php

namespace Clue\React\Ami;

use Clue\React\Ami\Client;
use Clue\React\Ami\Protocol\ActionResponse;
use Clue\React\Ami\Protocol\ActionRequest;
use UnexpectedValueException;
use Clue\React\Ami\Protocol\EventMessage;

class Api
{
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function login($username, $secret, $events = null)
    {
        $events = $this->boolParam($events);
        return $this->client->request(new ActionRequest('Login', array('UserName' => $username, 'Secret' => $secret, 'Events' => $events)));
    }

    public function logout()
    {
        return $this->client->request(new ActionRequest('Logout'));
    }

    public function agentLogoff($agentId, $soft = false)
    {
        $bool = $soft ? 'true' : 'false';
        return $this->client->request(new ActionRequest('AgentLogoff', array('Agent' => $agentId, 'Soft' => $bool)));
    }

    public function ping()
    {
        return $this->client->request(new ActionRequest('Ping'));
    }

    public function coreShowChannels()
    {
        return $this->client->request(new ActionRequest('CoreShowChannels'));
    }

    public function command($command)
    {
        return $this->client->request(new ActionRequest('Command', array('Command' => $command)));
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

        return $this->client->request(new ActionRequest('Events', array('EventMask' => $eventMask)));
    }

    public function sipPeers()
    {
        return $this->client->request(new ActionRequest('SIPPeers'));
    }

    public function sipShowPeer($peerName)
    {
        return $this->client->request(new ActionRequest('SIPshowpeer', array('Peer' => $peerName)));
    }

    public function listCommands()
    {
        return $this->client->request(new ActionRequest('ListCommands'));
    }

    public function sendText($channel, $message)
    {
        return $this->client->request(new ActionRequest('Sendtext', array('Channel' => $channel, 'Message' => $message)));
    }

    public function hangup($channel, $cause)
    {
        return $this->client->request(new ActionRequest('Hangup', array('Channel' => $channel, 'Cause' => $cause)));
    }

    public function challenge($authType = 'MD5')
    {
        return $this->client->request(new ActionRequest('Challenge', array('AuthType' => $authType)));
    }

    public function getConfig($filename, $category = null)
    {
        return $this->client->request(new ActionRequest('GetConfig', array('Filename' => $filename, 'Category' => $category)));
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
}
