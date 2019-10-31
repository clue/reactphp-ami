<?php

namespace Clue\React\Ami\Protocol;

/**
 * The `Action` value object represents an outgoing action message to be sent to the AMI.
 * It shares all properties of the [`Message`](#message) parent class.
 */
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

    /**
     * Get the serialized version of this outgoing action to send to Asterisk.
     *
     * This method is considered advanced usage and mostly used internally only.
     *
     * @return string
     */
    public function getMessageSerialized()
    {
        $message = '';
        foreach ($this->fields as $key => $values) {
            if (!is_array($values)) {
                $values = array($values);
            }
            foreach ($values as $value) {
                $message .= $key . ': ' . $value . "\r\n";
            }
        }
        $message .= "\r\n";

        return $message;
    }
}
