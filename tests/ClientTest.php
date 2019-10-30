<?php

namespace Clue\Tests\React\Ami;

use Clue\React\Ami\Client;
use Clue\React\Ami\Protocol\Parser;
use Clue\React\Ami\Protocol\Response;

class ClientTest extends TestCase
{
    public function testClosingStreamClosesClient()
    {
        $stream = $this->createStreamMock();

        $client = new Client($stream);

        $client->on('close', $this->expectCallableOnce());

        $stream->emit('close');
    }

    public function testParserExceptionForwardsErrorAndClosesClient()
    {
        $stream = $this->createStreamMock();
        $stream->expects($this->once())->method('close');

        $parser = new Parser();

        $client = new Client($stream, $parser);

        $client->on('error', $this->expectCallableOnce());

        $stream->emit('data', array("invalid chunk\r\n\r\ninvalid chunk\r\n\r\n"));
    }

    public function testUnexpectedResponseEmitsErrorAndClosesClient()
    {
        $client = new Client($this->createStreamMock());

        $client->on('error', $this->expectCallableOnce());
        $client->on('close', $this->expectCallableOnce());

        $client->handleMessage(new Response(array('ActionID' => 1)));
    }

    private function createStreamMock()
    {
        return $this->getMockBuilder('React\Socket\Connection')->disableOriginalConstructor()->setMethods(array('write', 'close'))->getMock();
    }
}
