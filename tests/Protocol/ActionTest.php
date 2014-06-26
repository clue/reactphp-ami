<?php

use Clue\React\Ami\Protocol\Action;
class ActionTest extends TestCase
{
    public function testSerializeSimple()
    {
        $action = new Action('name');
        $id = $action->getActionId();

        $this->assertEquals("Action: name\r\nActionID: $id\r\n\r\n", $action->getMessageSerialized());
    }

    public function testSerializeKeySingle()
    {
        $action = new Action('name', array('Key' => 'Value'));
        $id = $action->getActionId();

        $this->assertEquals("Action: name\r\nKey: Value\r\nActionID: $id\r\n\r\n", $action->getMessageSerialized());
    }

    public function testSerializeKeyMultipleValues()
    {
        $action = new Action('name', array('Key' => array('Value1', 'Value2')));
        $id = $action->getActionId();

        $this->assertEquals("Action: name\r\nKey: Value1\r\nKey: Value2\r\nActionID: $id\r\n\r\n", $action->getMessageSerialized());
    }

    public function testSerializeKeyMultipleKeyValues()
    {
        $action = new Action('name', array('Variables' => array('first' => 'on', 'second' => 'off')));
        $id = $action->getActionId();

        $this->assertEquals("Action: name\r\nVariables: first=on\r\nVariables: second=off\r\nActionID: $id\r\n\r\n", $action->getMessageSerialized());
    }
}
