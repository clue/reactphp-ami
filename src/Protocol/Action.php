<?php

namespace Clue\React\Ami\Protocol;

class Action extends Message
{
    public function __construct(array $fields = array())
    {
        $this->fields = $fields;
    }

    public function getMessageSerialized()
    {
        $message = '';
        foreach ($this->fields as $key => $values) {
            if (!is_array($values)) {
                $values = array($values);
            }
            foreach ($values as $i => $value) {
                if (!is_int($i)) {
                    $value = $i . '=' . $value;
                }
                $message .= $key . ': ' . $value . "\r\n";
            }
        }
        $message .= "\r\n";

        return $message;
    }
}
