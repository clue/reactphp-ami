<?php

namespace Clue\React\Ami\Protocol;

class Action extends Message
{
    public function __construct(array $parts = array())
    {
        $this->parts = $parts;
    }

    public function getMessageSerialized()
    {
        $message = '';
        foreach ($this->parts as $key => $values) {
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
