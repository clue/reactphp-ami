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
        $parts = array();

        foreach ($lines as $i => $line) {
            $pos = strlen($line) - self::LCOMMAND_END - 1;
            if ($i === $last && substr($line, -self::LCOMMAND_END) === self::COMMAND_END && ($pos < 0 || $line[$pos] === "\n")) {
                $key = '_';
                $value = $line;
            } else {
                $pos = strpos($line, ':');
                if ($pos === false) {
                    throw new \UnexpectedValueException('Parse error, no colon in line "' . $line . '" found');
                }
                $value = ltrim(substr($line, $pos + 1));
                $key = substr($line, 0, $pos);
            }

            $parts[$key] = $value;
        }

        reset($parts);
        $key = key($parts);

        if ($key === 'Event') {
            return new Event($parts);
        }

        return new Response($parts);
    }
}
