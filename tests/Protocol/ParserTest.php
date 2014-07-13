<?php

use Clue\React\Ami\Protocol\Parser;

class ParserTest extends TestCase
{
    public function testParseResponse()
    {
        $parser = new Parser();
        $this->assertEquals(array(), $parser->push("Asterisk Call Manager/1.3\r\n"));

        $ret = $parser->push("Response: Success\r\n\r\n");
        $this->assertCount(1, $ret);

        $first = reset($ret);
        /* @var $first Clue\React\Ami\Protocol\Response */

        $this->assertInstanceOf('Clue\React\Ami\Protocol\Response', $first);
        $this->assertEquals('Success', $first->getPart('Response'));
    }

    public function testParsingMultipleEvents()
    {
        $parser = new Parser();
        $this->assertEquals(array(), $parser->push("Asterisk Call Manager/1.3\r\n"));

        $ret = $parser->push("Event: TestA\r\n\r\nEvent: TestB\r\n\r\n");
        $this->assertCount(2, $ret);

        $first = reset($ret);
        /* @var $first Clue\React\Ami\Protocol\Event */

        $this->assertInstanceOf('Clue\React\Ami\Protocol\Event', $first);
        $this->assertEquals('TestA', $first->getName());
    }

    public function testParsingCommandResponse()
    {
        $parser = new Parser();
        $this->assertEquals(array(), $parser->push("Asterisk Call Manager/1.3\r\n"));

        $ret = $parser->push("Response: Follows\r\nTesting: yes\nAnother Line\n--END COMMAND--\r\n\r\n");
        $this->assertCount(1, $ret);

        $first = reset($ret);
        /* @var $first Clue\React\Ami\Protocol\Response */

        $this->assertInstanceOf('Clue\React\Ami\Protocol\Response', $first);
        $this->assertEquals('Follows', $first->getPart('Response'));
        $this->assertEquals("Testing: yes\nAnother Line\n--END COMMAND--", $first->getPart('_'));
    }

    public function testParsingCommandResponseEmpty()
    {
        $parser = new Parser();
        $this->assertEquals(array(), $parser->push("Asterisk Call Manager/1.3\r\n"));

        $ret = $parser->push("Response: Follows\r\n--END COMMAND--\r\n\r\n");
        $this->assertCount(1, $ret);

        $first = reset($ret);
        /* @var $first Clue\React\Ami\Protocol\Response */

        $this->assertInstanceOf('Clue\React\Ami\Protocol\Response', $first);
        $this->assertEquals('Follows', $first->getPart('Response'));
        $this->assertEquals("--END COMMAND--", $first->getPart('_'));
    }

    public function testParsingResponseIsNotCommandResponse()
    {
        $parser = new Parser();
        $this->assertEquals(array(), $parser->push("Asterisk Call Manager/1.3\r\n"));

        $ret = $parser->push("Response: Success\r\nMessage: Some message--END COMMAND--\r\n\r\n");
        $this->assertCount(1, $ret);

        $first = reset($ret);
        /* @var $first Clue\React\Ami\Protocol\Response */

        $this->assertInstanceOf('Clue\React\Ami\Protocol\Response', $first);
        $this->assertEquals('Success', $first->getPart('Response'));
        $this->assertEquals('Some message--END COMMAND--', $first->getPart('Message'));
    }

    /**
     * @expectedException UnexpectedValueException
     */
    public function testParsingInvalidResponseFails()
    {
        $parser = new Parser();
        $this->assertEquals(array(), $parser->push("Asterisk Call Manager/1.3\r\n"));

        $parser->push("invalid response\r\n\r\n");
    }
}
