<?php

namespace Clue\React\Ami\Protocol;

class Response extends Message
{
    public function __construct(array $parts)
    {
        $this->parts = $parts;
    }
}
