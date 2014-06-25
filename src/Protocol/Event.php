<?php

namespace Clue\React\Ami\Protocol;

class Event extends Message
{
    private $name;

    public function __construct($name, array $parts)
    {
        $this->name = $name;
        $this->parts = $parts;
    }

    public function getName()
    {
        return $this->name;
    }
}
