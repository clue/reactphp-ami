<?php

namespace Clue\React\Ami\Protocol;

class Event extends Message
{
    public function __construct(array $parts)
    {
        $this->parts = $parts;
    }

    public function getName()
    {
        return $this->getPart('Event');
    }
}
