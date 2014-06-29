<?php

use React\Stream\Stream;
use Clue\React\Ami\Protocol\Parser;
use Clue\React\Ami\Client;
use React\Stream\ThroughStream;

class ClientTest extends TestCase
{
    public function testClosingStreamClosesClient()
    {
        $stream = new ThroughStream();

        $client = new Client($stream);

        $client->on('close', $this->expectCallableOnce());

        $stream->close();
        //$stream->emit('close', array($this));
    }

    public function testParserExceptionForwardsErrorAndClosesClient()
    {
        $stream = new ThroughStream();
        $parser = new Parser();

        $client = new Client($stream, $parser);

        $client->on('error', $this->expectCallableOnce());
        $client->on('close', $this->expectCallableOnce());

        $stream->emit('data', array("invalid chunk\r\n\r\ninvalid chunk\r\n\r\n"));
    }
}
