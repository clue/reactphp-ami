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

    public function testSerializeSimple()
    {
        $action = new Action(array('Action' => 'name'));

        $this->assertEquals("Action: name\r\n\r\n", $action->getMessageSerialized());
    }

    public function testSerializeKeySingle()
    {
        $action = new Action(array('Action' => 'name', 'Key' => 'Value'));

        $this->assertEquals("Action: name\r\nKey: Value\r\n\r\n", $action->getMessageSerialized());
    }

    public function testSerializeKeyMultipleValues()
    {
        $action = new Action(array('Action' => 'name', 'Key' => array('Value1', 'Value2')));

        $this->assertEquals("Action: name\r\nKey: Value1\r\nKey: Value2\r\n\r\n", $action->getMessageSerialized());
    }

    public function testSerializeKeyMultipleKeyValues()
    {
        $action = new Action(array('Action' => 'name', 'Variables' => array('first' => 'on', 'second' => 'off')));

        $this->assertEquals("Action: name\r\nVariables: first=on\r\nVariables: second=off\r\n\r\n", $action->getMessageSerialized());
    }
}
