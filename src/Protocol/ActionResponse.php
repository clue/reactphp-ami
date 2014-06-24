<?php

namespace Clue\React\Ami\Protocol;

class ActionResponse extends Message
{
    public function __construct(array $parts)
    {
        $this->parts = $parts;
    }
}
