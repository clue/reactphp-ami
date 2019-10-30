<?php

namespace Clue\Tests\React\Ami\Protocol;

use Clue\Tests\React\Ami\TestCase;
use Clue\React\Ami\Protocol\Response;

class ResponseTest extends TestCase
{
    public function testGetCommandOutputReturnsOutputFieldsConcatenated()
    {
        $response = new Response(array(
            'Output' => array(
                'First',
                'Second',
                'Third'
            )
        ));

        $this->assertEquals("First\nSecond\nThird", $response->getCommandOutput());
    }

    public function testGetCommandOutputReturnsLegacyOutputFieldsWhenPresent()
    {
        $response = new Response(array(
            Response::FIELD_COMMAND_OUTPUT => 'legacy',
            'Output' => 'ignored'
        ));

        $this->assertEquals("legacy", $response->getCommandOutput());
    }

    public function testGetCommandOutputReturnsNullForEmptyResponse()
    {
        $response = new Response(array(
            'Foo' => 'bar'
        ));

        $this->assertNull($response->getCommandOutput());
    }
}
