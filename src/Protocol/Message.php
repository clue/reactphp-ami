<?php

namespace Clue\React\Ami\Protocol;

/**
 * The `Message` is an abstract base class for the [`Response`](#response),
 * [`Action`](#action) and [`Event`](#event) value objects.
 * It provides a common interface for these three message types.
 *
 * Each `Message` consists of any number of fields with each having a name and one or multiple values.
 * Field names are matched case-insensitive. The interpretation of values is application-specific.
 */
abstract class Message
{
    protected $fields = array();

    /**
     * Get the unique action ID of this message.
     *
     * This is a shortcut to get the value of the "ActionID" field.
     *
     * @return string
     */
    public function getActionId()
    {
        return $this->getFieldValue('ActionId');
    }

    /**
     * Get the first value for the given field key.
     *
     * If no value was found, `null` is returned.
     *
     * @param string $key
     * @return ?string
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
     * Get a list of all values for the given field key.
     *
     * If no value was found, an empty `array()` is returned.
     *
     * @param string $key
     * @return string[]
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
     * Get a hashmap of all variable assignments in the given $key.
     *
     * If no value was found, an empty `array()` is returned.
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

    /**
     * Get an array of all fields.
     *
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }
}
