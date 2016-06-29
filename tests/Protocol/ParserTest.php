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

    public function testParseResponseMultipleValues()
    {
        $parser = new Parser();
        $this->assertEquals(array(), $parser->push("Asterisk Call Manager/1.3\r\n"));

        $ret = $parser->push("Response: Success\r\nMessage: one\r\nMessage: two\r\n\r\n");
        $this->assertCount(1, $ret);

        $first = reset($ret);
        /* @var $first Clue\React\Ami\Protocol\Response */

        $this->assertInstanceOf('Clue\React\Ami\Protocol\Response', $first);
        $this->assertEquals('one', $first->getFieldValue('Message'));
        $this->assertEquals(array('one', 'two'), $first->getFieldValues('Message'));
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
        $this->assertEquals("Testing: yes\nAnother Line\n--END COMMAND--", $first->getCommandOutput());
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
        $this->assertEquals('Follows', $first->getFieldValue('Response'));
        $this->assertEquals("--END COMMAND--", $first->getCommandOutput());
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
        $this->assertEquals('Success', $first->getFieldValue('Response'));
        $this->assertEquals('Some message--END COMMAND--', $first->getFieldValue('Message'));
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

    public function testParsingMissingSpaceWithValue()
    {
        $parser = new Parser();
        $this->assertEquals(array(), $parser->push("Asterisk Call Manager/1.3\r\n"));

        $ret = $parser->push("Response:NoSpace\r\n\r\n");
        $this->assertCount(1, $ret);

        $first = reset($ret);
        /* @var $first Clue\React\Ami\Protocol\Response */

        $this->assertInstanceOf('Clue\React\Ami\Protocol\Response', $first);
        $this->assertEquals('NoSpace', $first->getFieldValue('Response'));
    }

    public function testParsingMissingSpaceEmptyValue()
    {
        $parser = new Parser();
        $this->assertEquals(array(), $parser->push("Asterisk Call Manager/1.3\r\n"));

        $ret = $parser->push("Response:\r\n\r\n");
        $this->assertCount(1, $ret);

        $first = reset($ret);
        /* @var $first Clue\React\Ami\Protocol\Response */

        $this->assertInstanceOf('Clue\React\Ami\Protocol\Response', $first);
        $this->assertEquals('', $first->getFieldValue('Response'));
    }
}
