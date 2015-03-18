<?php

namespace Clue\React\Ami\Protocol;

use UnexpectedValueException;

class UnexpectedMessageException extends UnexpectedValueException
{
    private $response;

    public function __construct(Response $response)
    {
        parent::__construct('Unexpected message with action ID "' . $response->getActionId() . '" received');
        $this->response = $response;
    }

    public function getResponse()
    {
        return $this->response;
    }
}
