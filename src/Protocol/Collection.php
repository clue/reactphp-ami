<?php

namespace Clue\React\Ami\Protocol;

use UnexpectedValueException;

class Collection extends Message
{
    private $response;
    private $entryEvents;
    private $completeEvent;

    public function __construct(Response $response, array $entryEvents, Event $completeEvent)
    {
        $this->fields = $response->getFields();
        $this->response = $response;
        $this->entryEvents = $entryEvents;
        $this->completeEvent = $completeEvent;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function getEntryEvents()
    {
        return $this->entryEvents;
    }

    public function getCompleteEvent()
    {
        return $this->completeEvent;
    }
}
