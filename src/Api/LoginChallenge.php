<?php

namespace Clue\React\Ami;

use Clue\React\Ami\Protocol\Response;
use Clue\React\Ami\Api;

class LoginChallenge
{
    private $api;

    public function __construct(Api $api)
    {
        $this->api = $api;
    }

    public function login($username, $secret, $events = null)
    {
        $events = $this->boolParam($events);
        $api = $this->api;

        return $api->challenge('md5')->then(function (Response $response) use ($username, $secret, $api, $events) {
            $key = md5($response->getFieldValue('Challenge') . $secret);
            return $api->request('Login', array('Username' => $username, 'AuthType' => 'md5', 'Key' => $key, 'Events' => $events));
        });
    }
}
