<?php

namespace Clue\React\Ami\Protocol;

class Response extends Message
{
    /** @internal */
    const FIELD_COMMAND_OUTPUT = '_';

    public function __construct(array $fields)
    {
        $this->fields = $fields;
    }

    public function getCommandOutput()
    {
        return $this->getFieldValue(self::FIELD_COMMAND_OUTPUT);
    }
}
