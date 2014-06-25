<?php

namespace Clue\React\Ami;

use Clue\React\Ami\Client;
use Clue\React\Ami\Protocol\Response;
use Clue\React\Ami\Protocol\Action;
use UnexpectedValueException;
use Clue\React\Ami\Protocol\Event;

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
        return $this->client->request(new Action('Login', array('UserName' => $username, 'Secret' => $secret, 'Events' => $events)));
    }

    public function logout()
    {
        return $this->client->request(new Action('Logout'));
    }

    public function agentLogoff($agentId, $soft = false)
    {
        $bool = $soft ? 'true' : 'false';
        return $this->client->request(new Action('AgentLogoff', array('Agent' => $agentId, 'Soft' => $bool)));
    }

    public function ping()
    {
        return $this->client->request(new Action('Ping'));
    }

    public function coreShowChannels()
    {
        return $this->client->request(new Action('CoreShowChannels'));
    }

    public function command($command)
    {
        return $this->client->request(new Action('Command', array('Command' => $command)));
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

        return $this->client->request(new Action('Events', array('EventMask' => $eventMask)));
    }

    public function sipPeers()
    {
        return $this->client->request(new Action('SIPPeers'));
    }

    public function sipShowPeer($peerName)
    {
        return $this->client->request(new Action('SIPshowpeer', array('Peer' => $peerName)));
    }

    public function listCommands()
    {
        return $this->client->request(new Action('ListCommands'));
    }

    public function sendText($channel, $message)
    {
        return $this->client->request(new Action('Sendtext', array('Channel' => $channel, 'Message' => $message)));
    }

    public function hangup($channel, $cause)
    {
        return $this->client->request(new Action('Hangup', array('Channel' => $channel, 'Cause' => $cause)));
    }

    public function challenge($authType = 'MD5')
    {
        return $this->client->request(new Action('Challenge', array('AuthType' => $authType)));
    }

    public function getConfig($filename, $category = null)
    {
        return $this->client->request(new Action('GetConfig', array('Filename' => $filename, 'Category' => $category)));
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
