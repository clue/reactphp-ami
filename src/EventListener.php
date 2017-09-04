<?php

namespace Clue\React\Ami;

use Clue\React\Ami\Client;
use Clue\React\Ami\Protocol\Event;
use Clue\React\Ami\Protocol\Message;
use Evenement\EventEmitter;

class EventListener extends EventEmitter
{

    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;

        $client->on('event', [$this, 'handleMessage']);
        $client->on('close', [$this, 'close']);
        $client->on('error', [$this, 'error']);
    }

    private function handleMessage(Event $event)
    {
        $this->emit($event->getName(), [$event]);
    }

    private function close(Message $message)
    {
        $this->emit('close', [$message]);
    }

    private function error(Message $message)
    {
        $this->emit('error', [$message]);
    }
}