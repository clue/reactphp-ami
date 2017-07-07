<?php

use React\Stream\Stream;
use Clue\React\Ami\Protocol\Parser;
use Clue\React\Ami\Client;
use React\EventLoop\Factory;
use Clue\React\Ami\Protocol\Response;

class ClientTest extends TestCase
{
    public function testClosingStreamClosesClient()
    {
        $stream = $this->createStreamMock();

        $client = new Client($stream);

        $client->on('close', $this->expectCallableOnce());

        $stream->close();
        //$stream->emit('close', array($this));
    }

    public function testParserExceptionForwardsErrorAndClosesClient()
    {
        $stream = $this->createStreamMock();
        $parser = new Parser();

        $client = new Client($stream, $parser);

        $client->on('error', $this->expectCallableOnce());
        $client->on('close', $this->expectCallableOnce());

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
        return new Stream(fopen('php://memory', 'r+'), $this->getMockBuilder('React\EventLoop\LoopInterface')->getMock());
    }
}
