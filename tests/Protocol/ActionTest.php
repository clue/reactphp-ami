<?php

use Clue\React\Ami\Protocol\Action;

class ActionTest extends TestCase
{
    public function testIdDefaultsToNull()
    {
        $action = new Action();

        $this->assertNull($action->getActionId());
    }

    public function testIdCanBeSet()
    {
        $action = new Action(array('ActionID' => '123'));

        $this->assertEquals('123', $action->getActionId());

        $this->assertEquals('123', $action->getFieldValue('ActionID'));
        $this->assertEquals('123', $action->getFieldValue('aCtIoNiD'));

        $this->assertNull($action->getFieldValue('unknown'));
    }

    public function testOneFieldValue()
    {
        $action = new Action(array('Action' => 'name'));

        $this->assertEquals("Action: name\r\n\r\n", $action->getMessageSerialized());
    }

    public function testMultipleFieldsSingleValue()
    {
        $action = new Action(array('Action' => 'name', 'Key' => 'Value'));

        $this->assertEquals("Action: name\r\nKey: Value\r\n\r\n", $action->getMessageSerialized());
    }

    public function testOneFieldNoValue()
    {
        $action = new Action(array('Key' => null));

        $this->assertEquals("\r\n", $action->getMessageSerialized());

        $this->assertNull($action->getFieldValue('Key'));
    }

    public function testOneFieldNoValues()
    {
        $action = new Action(array('Key' => array()));

        $this->assertEquals("\r\n", $action->getMessageSerialized());

        $this->assertNull($action->getFieldValue('Key'));
    }

    public function testOneFieldMultipleValues()
    {
        $action = new Action(array('Key' => array('Value1', 'Value2')));

        $this->assertEquals("Key: Value1\r\nKey: Value2\r\n\r\n", $action->getMessageSerialized());
    }

    public function testOneFieldMultipleValuesIgnoreNulls()
    {
        $action = new Action(array('Key' => array(null, 'value', null)));

        $this->assertEquals("Key: value\r\n\r\n", $action->getMessageSerialized());

        $this->assertEquals('value', $action->getFieldValue('Key'));
    }

    public function testOneFieldMultipleKeyValues()
    {
        $action = new Action(array('Variables' => array('first' => 'on', 'second' => 'off')));

        $this->assertEquals("Variables: first=on\r\nVariables: second=off\r\n\r\n", $action->getMessageSerialized());

        $this->assertEquals('first=on', $action->getFieldValue('Variables'));
    }
}
