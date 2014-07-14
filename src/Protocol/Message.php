<?php

namespace Clue\React\Ami\Protocol;

abstract class Message
{
    protected $fields = array();

    public function getActionId()
    {
        return $this->getFieldValue('ActionId');
    }

    public function getFieldValue($key)
    {
        $key = strtolower($key);

        foreach ($this->fields as $part => $value) {
            if (strtolower($part) === $key) {
                return $value;
            }
        }

        return null;
    }

    public function toJson()
    {
        return json_encode($this->getFields());
    }

    public function getFields()
    {
        return $this->fields;
    }
}
