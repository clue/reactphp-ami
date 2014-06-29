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
}
