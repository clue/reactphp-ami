<?php

namespace Clue\React\Ami\Protocol;

use RuntimeException;

class ErrorException extends RuntimeException
{
    private $response;

    public function __construct(Response $response)
    {
        parent::__construct('Error "' . $response->getFieldValue('Message') . '"');
        $this->response = $response;
    }

    public function getResponse()
    {
        return $this->response;
    }
}
