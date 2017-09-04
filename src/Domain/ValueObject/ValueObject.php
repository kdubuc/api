<?php

namespace API\Domain\ValueObject;

use Exception;
use JsonSerializable;
use API\Domain\Collection;
use API\Domain\Normalizable;

abstract class ValueObject implements JsonSerializable, Normalizable
{
    /**
     * Merge with another ValueObject. Return new ValueObject (immutable).
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

        foreach ($value_objects as $value_object) {
            if (!is_a($value_object, get_class($new_value_object))) {
                throw new Exception('Error Processing Request', 1);
            }

            $new_value_object = self::denormalize((array) json_decode($patcher(
                json_encode($new_value_object->normalize(), true),
                json_encode($value_object->normalize(), true)
            )));
        }

        return $new_value_object;
    }

    /**
     * Update properties and return new ValueObject (immutable).
     */
    public function update(array $properties) : ValueObject
    {
        $value_object_normalized = $this->normalize();

        // Browse the firt dimension of the normalized VO form.
        foreach ($value_object_normalized as $field => $value) {
            if (array_key_exists($field, $properties)) {
                $value_object_normalized[$field] = $properties[$field];
            }
        }

        return static::denormalize($value_object_normalized);
    }

    /**
     * Check if the value object is empty.
     */
    public function isEmpty() : bool
    {
        $properties = array_filter(get_object_vars($this));

        return empty($properties);
    }

    /**
     * Check if the value object / entity / Aggregate root is equal to another.
     */
    public function isEqual(ValueObject $value_object) : bool
    {
        return $this->normalize() === $value_object->normalize();
    }

    /**
     * Build a collection which can contains the model.
     */
    public static function collection(array $domain_entities = []) : Collection
    {
        $current_class = get_called_class();

        return new Collection(array_filter($domain_entities, function ($entity) use ($current_class) {
            return $entity instanceof $current_class;
        }));
    }

    /**
     * Return JSON representation.
     */
    public function jsonSerialize() : string
    {
        return json_encode($this->normalize());
    }

    /**
     * Query collection field using dot notation (https://docs.mongodb.com/manual/core/document/#document-dot-notation).
     */
    public function query(string $field) : array
    {
        // Get the current segment from the field parameter, and update pendings
        // segments
        $pending_segments = explode('.', $field);
        $current_segment = array_shift($pending_segments);

        // Get normalized object
        $value = $this->normalize();

        // Handle the nested array case ...
        // and update the normalized data with the data on the current segment
        if(!array_key_exists($current_segment, $value) && array_key_exists($current_segment, reset($value))) {
            $value = array_map(function($data) use ($current_segment) {
                return $data[$current_segment];
            }, $value);
        }
        else {
            $value = $value[$current_segment];
        }

        // If there aren't segments left, we try to return a Normalizable corresponding
        // with the field, or simple data.
        if(empty($pending_segments)) {

            $value = is_array($value) ? $value : [$value];

            return array_map(function($data) {
                return static::isDenormalizable($data) ? $data['class_name']::denormalize($data) : $data;
            }, $value);

        }

        // We dig into the normalized object (Recursive way baby o/)
        return $this->query(implode('.', $pending_segments));
    }

    /**
     * Test if data can be denormalized to obtain Normalizable object.
     */
    public static function isDenormalizable($data) : bool
    {
        return is_array($data) && array_key_exists('class_name', $data) && is_a($data['class_name'], Normalizable::class, true);
    }
}
