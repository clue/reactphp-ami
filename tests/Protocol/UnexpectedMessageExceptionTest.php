<?php

use Clue\React\Ami\Protocol\Response;
use Clue\React\Ami\Protocol\UnexpectedMessageException;

class UnexpectedMessageExceptionTest extends TestCase
{
    public function testGetResponse()
    {
        $response = new Response(array('ActionID' => 1));

        $exception = new UnexpectedMessageException($response);

        $this->assertSame($response, $exception->getResponse());
    }
}
