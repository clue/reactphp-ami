<?php

namespace Clue\React\Ami\Protocol;

abstract class Message
{
    protected $fields = array();

    public function getActionId()
    {
        return $this->getFieldValue('ActionId');
    }

    /**
     * Returns the first value for the field with the given $key
     *
     * @param string $key
     * @return string|NULL
     */
    public function getFieldValue($key)
    {
        $key = strtolower($key);

        foreach ($this->fields as $part => $value) {
            if (strtolower($part) === $key) {
                if (is_array($value)) {
                    return reset($value);
                } else {
                    return $value;
                }
            }
        }

        return null;
    }

    /**
     * Returns a list of all values for the field with the given $key
     *
     * @param string $key
     * @return array
     */
    public function getFieldValues($key)
    {
        $values = array();
        $key = strtolower($key);

        foreach ($this->fields as $part => $value) {
            if (strtolower($part) === $key) {
                if (is_array($value)) {
                    foreach ($value as $v) {
                        $values []= $v;
                    }
                } else {
                    $values []= $value;
                }
            }
        }

        return $values;
    }

    /**
     * Returns a hashmap of all variable assignments in the given $key
     *
     * @param string $key
     * @return array
     * @uses self::getFieldValues()
     */
    public function getFieldVariables($key)
    {
        $variables = array();

        foreach ($this->getFieldValues($key) as $value) {
            $temp = explode('=', $value, 2);
            $variables[$temp[0]] = $temp[1];
        }

        return $variables;
    }

    public function getFields()
    {
        return $this->fields;
    }
}
