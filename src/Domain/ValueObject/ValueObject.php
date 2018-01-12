<?php

namespace API\Domain\ValueObject;

use Exception;
use JsonSerializable;
use API\Domain\Collection;
use API\Domain\Normalizable;
use API\Domain\Feature\Query;

abstract class ValueObject implements JsonSerializable, Normalizable
{
    use Query;

    /**
     * Merge with another ValueObject. Return new ValueObject (immutable).
     */
    public function merge(self ...$value_objects) : self
    {
        $patcher = function ($target, $patch) use (&$patcher) {
            if (!is_object($patch)) {
                return $patch;
            }
            if (!is_object($target)) {
                $target = (object) [];
            }
            foreach ($patch as $name => $value) {
                if (null === $value) {
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

            $new_value_object = self::denormalize(json_decode($patcher(
                json_encode($new_value_object->normalize(), JSON_HEX_TAG),
                json_encode($value_object->normalize(), JSON_HEX_TAG)
            )), JSON_OBJECT_AS_ARRAY);
        }

        return $new_value_object;
    }

    /**
     * Update properties and return new ValueObject (immutable).
     */
    public function update(array $properties) : self
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
    public function isEqual(self $value_object) : bool
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
}
