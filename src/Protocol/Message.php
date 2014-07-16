<?php

namespace Clue\React\Ami\Protocol;

abstract class Message
{
    protected $fields = array();

    public function getActionId()
    {
        return $this->getField('ActionId');
    }

    public function getField($key)
    {
        $key = strtolower($key);

        foreach ($this->fields as $part => $value) {
            if (strtolower($part) === $key) {
                return $value;
            }
        }

        return null;
    }

    public function getFields()
    {
        return $this->fields;
    }
}
