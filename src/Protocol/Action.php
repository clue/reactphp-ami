<?php

namespace Clue\React\Ami\Protocol;

class Action extends Message
{
    public function __construct(array $fields = array())
    {
        foreach ($fields as $key => &$value) {
            if (is_array($value)) {
                foreach ($value as $k => &$v) {
                    if ($v === null) {
                        unset($value[$k]);
                    } elseif (!is_int($k)) {
                        $v = $k . '=' . $v;
                    }
                }
                $value = array_values($value);
            }

            if ($value === null || $value === array()) {
                unset($fields[$key]);
            }
        }
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
                $message .= $key . ': ' . $value . "\r\n";
            }
        }
        $message .= "\r\n";

        return $message;
    }
}
