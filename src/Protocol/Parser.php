<?php

namespace Clue\React\Ami\Protocol;

use Clue\Hexdump\Hexdump;
class Parser
{
    const EOM = "\r\n\r\n";
    const LEOM = 4;

    const EOL = "\r\n";
    const LEOL = 2;

    const COMMAND_END = "--END COMMAND--";
    const LCOMMAND_END = 15;

    private $buffer = '';
    private $gotInitial = false;

    public function push($chunk)
    {
        $this->buffer .= $chunk;
        $messages = array();

        if (!$this->gotInitial && ($pos = strpos($this->buffer, self::EOL)) !== false) {
            //var_dump('initial', substr($this->buffer, 0, $pos));
            $this->gotInitial = true;
            $this->buffer = (string)substr($this->buffer, $pos + self::LEOL);
        }

        while (($pos = strpos($this->buffer, self::EOM)) !== false) {
            $message = substr($this->buffer, 0, $pos);
            $this->buffer = (string)substr($this->buffer, $pos + self::LEOM);

            $messages []= $this->parseMessage($message);
        }

        return $messages;
    }

    private function parseMessage($message)
    {
        $lines = explode(self::EOL, $message);
        $last  = count($lines) - 1;
        $fields = array();

        foreach ($lines as $i => $line) {
            $pos = strlen($line) - self::LCOMMAND_END - 1;
            if ($i === $last && substr($line, -self::LCOMMAND_END) === self::COMMAND_END && ($pos < 0 || $line[$pos] === "\n")) {
                $key = Response::FIELD_COMMAND_OUTPUT;
                $value = $line;
            } else {
                $pos = strpos($line, ':');
                if ($pos === false) {
                    throw new \UnexpectedValueException('Parse error, no colon in line "' . $line . '" found');
                }

                $value = (string)substr($line, $pos + (isset($line[$pos + 1]) && $line[$pos + 1] === ' ' ? 2 : 1));
                $key = substr($line, 0, $pos);
            }

            if (isset($fields[$key])) {
                if (!is_array($fields[$key])) {
                    $fields[$key] = array($fields[$key]);
                }
                $fields[$key][] = $value;
            } else {
                $fields[$key] = $value;
            }
        }

        reset($fields);
        $key = key($fields);

        if ($key === 'Event') {
            return new Event($fields);
        }

        return new Response($fields);
    }
}
