<?php

namespace Clue\React\Ami\Protocol;

class ActionRequest extends Message
{
    private $action;

    public function __construct($action, array $parts = array())
    {
        $this->action = $action;
        $this->parts = $parts;

        $this->parts['ActionID'] = (string)mt_rand();
    }

    public function getMessageSerialized()
    {
        $message = 'Action: ' . $this->action . "\r\n";
        foreach ($this->parts as $key => $value) {
            $message .= $key . ': ' . $value . "\r\n";
        }
        $message .= "\r\n";

        return $message;
    }
}
