<?php

namespace Clue\React\Ami\Protocol;

class Collection extends Response
{
    private $entryEvents;
    private $completeEvent;

    public function __construct(Response $response, array $entryEvents, Event $completeEvent)
    {
        $this->fields = $response->getFields();
        $this->entryEvents = $entryEvents;
        $this->completeEvent = $completeEvent;
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
