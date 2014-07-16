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

    public function testOneFieldMultipleValues()
    {
        $action = new Action(array('Key' => array('Value1', 'Value2')));

        $this->assertEquals("Key: Value1\r\nKey: Value2\r\n\r\n", $action->getMessageSerialized());
    }

    public function testOneFieldMultipleKeyValues()
    {
        $action = new Action(array('Variables' => array('first' => 'on', 'second' => 'off')));

        $this->assertEquals("Variables: first=on\r\nVariables: second=off\r\n\r\n", $action->getMessageSerialized());
    }
}
