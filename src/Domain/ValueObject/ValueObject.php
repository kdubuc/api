<?php

namespace API\Domain\ValueObject;

use Exception;
use JsonSerializable;
use ArrayIterator;
use API\Domain\Feature\CollectionBuilder;
use API\Domain\CanBuildCollection;
use API\Domain\Collection;

abstract class ValueObject implements JsonSerializable, CanBuildCollection
{
    use CollectionBuilder;

    /**
     * Convert the value object into an array.
     *
     * @return array
     */
    public function toArray() : array
    {
        $iterator = new ArrayIterator(get_object_vars($this), ARRAY_FILTER_USE_KEY);

        $iterator = (array) $iterator;

        array_walk_recursive($iterator, function(&$item) {

            if($item instanceof ValueObject) {

                $item = $item->toArray();

            }
            elseif($item instanceof Collection) {

                $item = array_map(function($element) {

                    return $element instanceof ValueObject ? $element->toArray() : $element;

                }, $item->toArray());

            }

        });

        $iterator['value_object_class_name'] = get_called_class();

        return $iterator;
    }

    /**
     * Merge with another ValueObject. Return new ValueObject (immutable)
     *
     * @param API\Domain\ValueObject
     *
     * @return API\Domain\ValueObject
     */
    public function merge(ValueObject ...$value_objects) : ValueObject
    {
        $patcher = function ($target, $patch) use (&$patcher) {
            if (!is_object($patch)) {
                return $patch;
            }
            if (!is_object($target)) {
                $target = (object) [];
            }
            foreach ($patch as $name => $value) {
                if ($value === null) {
                    unset($target->$name);
                } else {
                    if (!isset($target->$name)) {
                        $target->$name = null;
                    }
                    $target->$name = $patcher($target->$name, $value);
                }
            }

            return $target;
        };

        $new_value_object = array_shift($value_objects);

        foreach($value_objects as $value_object) {

            if(!is_a($value_object, get_class($new_value_object))) {
                throw new Exception("Error Processing Request", 1);
            }

            $new_value_object = self::fromArray((array) json_decode($patcher(
                json_encode($new_value_object->toArray(), true),
                json_encode($value_object->toArray(), true)
            )));

        }

        return $new_value_object;
    }

    /**
     * Build the value object from array.
     *
     * @return array $input
     *
     * @return API\Domain\ValueObject\ValueObject
     */
    abstract public static function fromArray(array $input) : ValueObject;

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

    /**
     * Check if the value object is equal to another.
     *
     * @return bool
     */
    public function isEqual(ValueObject $value_object) : bool
    {
        return $this->toArray() === $value_object->toArray();
    }

    /**
     * Implements JsonSerializable.
     *
     * @return array
     */
    public function jsonSerialize() : array
    {
        return $this->toArray();
    }
}
