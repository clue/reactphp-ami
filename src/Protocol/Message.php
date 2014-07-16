<?php

namespace Clue\React\Ami\Protocol;

abstract class Message
{
    protected $fields = array();

    public function getActionId()
    {
        return $this->getFieldValue('ActionId');
    }

    /**
     * Returns the first value for the field with the given $key
     *
     * @param string $key
     * @return string|NULL
     */
    public function getFieldValue($key)
    {
        $key = strtolower($key);

        foreach ($this->fields as $part => $value) {
            if (strtolower($part) === $key) {
                if (is_array($value)) {
                    return reset($value);
                } else {
                    return $value;
                }
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
