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

    /**
     * Get the resulting output of a "command" Action.
     *
     * This value is only available if this is actually a response to a "command"
     * action, otherwise it defaults to null.
     *
     * ```php$sender->command('help')->then(function (Response $response) {
     *     echo $response->getCommandOutput();
     * });
     * ```
     *
     * @return ?string
     */
    public function getCommandOutput()
    {
        // legacy Asterisk uses custom format for command output
        $output = $this->getFieldValue(self::FIELD_COMMAND_OUTPUT);
        if ($output !== null) {
            return $output;
        }

        // Asterisk 14+ uses multiple "Output" fields: https://github.com/asterisk/asterisk/commit/2f418c052ec
        $output = $this->getFieldValues('Output');
        if (!$output) {
            return null;
        }

        return implode("\n", $output);
    }
}
