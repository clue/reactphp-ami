<?php

namespace Clue\Tests\React\Ami\Protocol;

use Clue\React\Ami\Protocol\Parser;
use Clue\Tests\React\Ami\TestCase;

class ParserTest extends TestCase
{
    public function testParseResponse()
    {
        $parser = new Parser();
        $this->assertEquals(array(), $parser->push("Asterisk Call Manager/1.3\r\n"));

        $ret = $parser->push("Response: Success\r\n\r\n");
        $this->assertCount(1, $ret);

        $first = reset($ret);
        /* @var $first \Clue\React\Ami\Protocol\Response */

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
        /* @var $first \Clue\React\Ami\Protocol\Response */

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
        /* @var $first \Clue\React\Ami\Protocol\Event */

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
        /* @var $first \Clue\React\Ami\Protocol\Response */

        $this->assertInstanceOf('Clue\React\Ami\Protocol\Response', $first);
        $this->assertEquals('one', $first->getFieldValue('Message'));
        $this->assertEquals(array('one', 'two'), $first->getFieldValues('Message'));
    }

    public function testParsingAsterisk14CommandResponse()
    {
        $parser = new Parser();
        $this->assertEquals(array(), $parser->push("Asterisk Call Manager/1.3\r\n"));

        $ret = $parser->push("Response: Follows\r\nOutput: Testing: yes\r\nOutput: Another Line\r\n\r\n");
        $this->assertCount(1, $ret);

        $first = reset($ret);
        /* @var $first \Clue\React\Ami\Protocol\Response */

        $this->assertInstanceOf('Clue\React\Ami\Protocol\Response', $first);
        $this->assertEquals('Follows', $first->getFieldValue('Response'));
        $this->assertEquals("Testing: yes\nAnother Line", $first->getCommandOutput());
    }

    public function testParsingLegacyCommandResponse()
    {
        $parser = new Parser();
        $this->assertEquals(array(), $parser->push("Asterisk Call Manager/1.3\r\n"));

        $ret = $parser->push("Response: Follows\r\nTesting: yes\nAnother Line\n--END COMMAND--\r\n\r\n");
        $this->assertCount(1, $ret);

        $first = reset($ret);
        /* @var $first \Clue\React\Ami\Protocol\Response */

        $this->assertInstanceOf('Clue\React\Ami\Protocol\Response', $first);
        $this->assertEquals('Follows', $first->getFieldValue('Response'));
        $this->assertEquals("Testing: yes\nAnother Line\n--END COMMAND--", $first->getCommandOutput());
    }

    public function testParsingLegacyCommandResponseEmpty()
    {
        $parser = new Parser();
        $this->assertEquals(array(), $parser->push("Asterisk Call Manager/1.3\r\n"));

        $ret = $parser->push("Response: Follows\r\n--END COMMAND--\r\n\r\n");
        $this->assertCount(1, $ret);

        $first = reset($ret);
        /* @var $first \Clue\React\Ami\Protocol\Response */

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
        /* @var $first \Clue\React\Ami\Protocol\Response */

        $this->assertInstanceOf('Clue\React\Ami\Protocol\Response', $first);
        $this->assertEquals('Success', $first->getFieldValue('Response'));
        $this->assertEquals('Some message--END COMMAND--', $first->getFieldValue('Message'));
    }

    public function testParsingInvalidResponseFails()
    {
        $parser = new Parser();
        $this->assertEquals(array(), $parser->push("Asterisk Call Manager/1.3\r\n"));

        $this->setExpectedException('UnexpectedValueException');
        $parser->push("invalid response\r\n\r\n");
    }

    public function testParsingMissingSpaceWithValue()
    {
        $parser = new Parser();
        $this->assertEquals(array(), $parser->push("Asterisk Call Manager/1.3\r\n"));

        $ret = $parser->push("Response:NoSpace\r\n\r\n");
        $this->assertCount(1, $ret);

        $first = reset($ret);
        /* @var $first \Clue\React\Ami\Protocol\Response */

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
        /* @var $first \Clue\React\Ami\Protocol\Response */

        $this->assertInstanceOf('Clue\React\Ami\Protocol\Response', $first);
        $this->assertEquals('', $first->getFieldValue('Response'));
    }

    public function testParsingExcessiveNewlines()
    {
        $parser = new Parser();
        $this->assertEquals(array(), $parser->push("Asterisk Call Manager/1.3\r\n"));

        $ret = $parser->push("First: 1\r\n\r\nSecond: 2\r\n\r\n\r\nThird: 3\r\n\r\n\r\n\r\nFourth: 4\r\n\r\n");
        $this->assertCount(4, $ret);

        $last = $ret[count($ret) - 1];
        /* @var $last \Clue\React\Ami\Protocol\Response */

        $this->assertInstanceOf('Clue\React\Ami\Protocol\Response', $last);
        $this->assertEquals('4', $last->getFieldValue('Fourth'));
    }
}
