<?php

namespace Clue\React\Ami\Protocol;

/**
 * The `Event` value object represents the incoming event received from the AMI.
 * It shares all properties of the [`Message`](#message) parent class.
 */
class Event extends Message
{
    public function __construct(array $fields)
    {
        $this->fields = $fields;
    }

    /**
     * Get the name of the event.
     *
     * This is a shortcut to get the value of the "Event" field.
     *
     * @return ?string
     */
    public function getName()
    {
        return $this->getFieldValue('Event');
    }
}
