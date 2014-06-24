<?php

namespace Clue\React\Ami\Protocol;

use RuntimeException;

class ErrorException extends RuntimeException
{
    private $response;

    public function __construct(ActionResponse $response)
    {
        parent::__construct('Error "' . $response->getPart('Message') . '"');
        $this->response = $response;
    }

    public function getResponse()
    {
        return $this->response;
    }
}
