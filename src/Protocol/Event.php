<?php

namespace Clue\React\Ami\Protocol;

class Event extends Message
{
    public function __construct(array $fields)
    {
        $this->fields = $fields;
    }

    public function getName()
    {
        return $this->getFieldValue('Event');
    }
}
