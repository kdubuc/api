<?php

namespace API\Domain\Expression\Translator;

use RuntimeException;
use API\Domain\Normalizable;
use API\Domain\Expression\Logical;
use Doctrine\Common\Collections\Criteria;
use API\Domain\Expression\Comparison;
use API\Domain\Collection;

class Closure extends Translator
{
    /**
     * Get access to a property value using the normalize() method and the dot
     * access notation.
     */
    private static function getObjectFieldValue($object, string $field)
    {
        // If object can't be normalized, we switch to the old behavior
        if (!($object instanceof Normalizable)) {
            throw new RuntimeException("Entity must be Normalizable");
        }

        // We get the field using normalize method and dot notation to navigate
        // in object.
        $value = array_get($object->normalize(), $field);

        // If there is a classe name parameter, we denormalize it
        if (is_array($value) && array_key_exists('class_name', $value)) {
            $value = $value['class_name']::denormalize($value);
        }

        return $value;
    }

    /**
     * Translate orderings into the target query language output (order field and sort type).
     */
    public static function translateOrderings(array $orderings)
    {
        // Helper for sorting arrays of objects based on multiple fields + orientations.
        $sortByField = function(string $name, int $orientation = 1, callable $next = null) : callable {
            if (!$next) {
                $next = function () : int {
                    return 0;
                };
            }

            return function ($a, $b) use ($name, $next, $orientation) : int {
                $aValue = static::getObjectFieldValue($a, $name);
                $bValue = static::getObjectFieldValue($b, $name);

                if ($aValue === $bValue) {
                    return $next($a, $b);
                }

                return (($aValue > $bValue) ? 1 : -1) * $orientation;
            };
        };

        return function(Collection $collection) use ($orderings, $sortByField) {
            $next = null;

            foreach (array_reverse($orderings) as $field => $ordering) {
                $next = $sortByField($field, $ordering == Criteria::DESC ? -1 : 1, $next);
            }

            $data = $collection->getData();

            uasort($data, $next);

            $class_name = get_class($collection);
            return new $class_name($data);
        };
    }

    /**
     * Translate slicing into the target query language output (first result and max count).
     */
    public static function translateSlicing(int $limit, int $skip = 0)
    {
        return function(Collection $collection) use ($limit, $skip) {
            $data = $collection->slice((int) $skip, $limit);
            return $collection->filter(function($element) use ($data) {
                return in_array($element, $data);
            });
        };
    }

    /**
     * Translate comparison expression into the target query language output (e.g. eq, lte ...).
     */
    protected function translateComparison(Comparison $comparison)
    {
        // Field path using dot notation
        $field = $comparison->getField();

        // Comparison value
        $value = $this->walkValue($comparison->getValue());

        // Return the correct query language in function of the operator used
        switch ($comparison->getOperator()) {
            case "eq":
                return function ($object) use ($field, $value) : bool {
                    return static::getObjectFieldValue($object, $field) === $value;
                };

            case "neq":
                return function ($object) use ($field, $value) : bool {
                    return static::getObjectFieldValue($object, $field) !== $value;
                };

            case "lt":
                return function ($object) use ($field, $value) : bool {
                    return static::getObjectFieldValue($object, $field) < $value;
                };

            case "lte":
                return function ($object) use ($field, $value) : bool {
                    return static::getObjectFieldValue($object, $field) <= $value;
                };

            case "gt":
                return function ($object) use ($field, $value) : bool {
                    return static::getObjectFieldValue($object, $field) > $value;
                };

            case "gte":
                return function ($object) use ($field, $value) : bool {
                    return static::getObjectFieldValue($object, $field) >= $value;
                };

            case "in":
                return function ($object) use ($field, $value) : bool {
                    return in_array(static::getObjectFieldValue($object, $field), $value);
                };

            case "nin":
                return function ($object) use ($field, $value) : bool {
                    return !in_array(static::getObjectFieldValue($object, $field), $value);
                };

            case "contains":
                return function ($object) use ($field, $value) {
                    return false !== mb_strpos(static::getObjectFieldValue($object, $field), $value);
                };

            default:
                throw new RuntimeException('Unknown comparison operator: '.$comparison->getOperator());
        }
    }

    /**
     * Translate logical expression into the target query language output (e.g. and / or).
     */
    protected function translateLogical(Logical $logical)
    {
        $expressions = array_map(function($expression) {
            return $this->dispatch($expression);
        }, $logical->getExpressionList());

        switch($expr->getType()) {
            case 'and':
                return function ($object) use ($expressions) : bool {
                    foreach ($expressions as $expression) {
                        if ( ! $expression($object)) {
                            return false;
                        }
                    }
                    return true;
                };
            case 'or':
                return function ($object) use ($expressions) : bool {
                    foreach ($expressions as $expression) {
                        if ($expression($object)) {
                            return true;
                        }
                    }
                    return false;
                };
            default:
                throw new RuntimeException("Unknown composite " . $expr->getType());
        }
    }
}
