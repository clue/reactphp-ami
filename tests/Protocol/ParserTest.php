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
        $this->assertEquals('Success', $first->getFieldValue('Response'));
    }

    public function testParseResponseSpace()
    {
        $parser = new Parser();
        $this->assertEquals(array(), $parser->push("Asterisk Call Manager/1.3\r\n"));

        $ret = $parser->push("Response: Success\r\nMessage:  spaces  \r\n\r\n");
        $this->assertCount(1, $ret);

        $first = reset($ret);
        /* @var $first Clue\React\Ami\Protocol\Response */

        $this->assertInstanceOf('Clue\React\Ami\Protocol\Response', $first);
        $this->assertEquals(' spaces  ', $first->getFieldValue('Message'));
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
        $this->assertEquals('Follows', $first->getFieldValue('Response'));
        $this->assertEquals("Testing: yes\nAnother Line\n--END COMMAND--", $first->getFieldValue('_'));
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

    /**
     * @expectedException UnexpectedValueException
     */
    public function testParsingInvalidResponseNoSpaceAfterColonFails()
    {
        $parser = new Parser();
        $this->assertEquals(array(), $parser->push("Asterisk Call Manager/1.3\r\n"));

        $parser->push("Response:NoSpace\r\n\r\n");
    }
}
