<?php

namespace API\Domain\ValueObject;

abstract class ValueObject
{
    /**
     * Stringify value object.
     *
     * @return string
     */
    abstract public function toString() : string;

    /**
     * Parse value object with the string data.
     *
     * @return API\Domain\ValueObject
     */
    abstract public static function fromString(string $serialized);

    /**
     * Converts this value object to a string when the object is used in any
     * string context.
     *
     * @return string
     */
    public function __toString() : string
    {
        return $this->toString();
    }

    /**
     * Check if the value object is empty.
     *
     * @return bool
     */
    public function isEmpty() : bool
    {
        $properties = array_filter(get_object_vars($this));

        return empty($properties);
    }
}
