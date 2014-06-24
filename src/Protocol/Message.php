<?php

namespace Clue\React\Ami\Protocol;

abstract class Message
{
    protected $parts = array();

    public function getActionId()
    {
        return $this->getPart('ActionId');
    }

    public function getPart($key)
    {
        $key = strtolower($key);

        foreach ($this->parts as $part => $value) {
            if (strtolower($part) === $key) {
                return $value;
            }
        }

        return null;
    }

    public function toJson()
    {
        return json_encode($this->getParts());
    }

    public function getParts()
    {
        return $this->parts;
    }
}
