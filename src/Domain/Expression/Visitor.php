<?php

namespace API\Domain\Expression;

use Closure;
use RuntimeException;
use API\Domain\Normalizable;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\ClosureExpressionVisitor;

class Visitor extends ClosureExpressionVisitor
{
    /**
     * Get access to a property value using the normalize() method and the dot
     * access notation.
     */
    public static function getObjectFieldValue($object, $field)
    {
        // If object can't be normalized, we switch to the old behavior
        if (!($object instanceof Normalizable)) {
            return parent::getObjectFieldValue($object, $field);
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
     * Helper for sorting arrays of objects based on multiple fields + orientations.
     */
    public static function sortByField($name, $orientation = 1, Closure $next = null)
    {
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
    }

    /**
     * Converts a composite expression into the target query language output.
     */
    public function walkComparison(Comparison $comparison)
    {
        $field = $comparison->getField();
        $value = $comparison->getValue()->getValue();

        switch ($comparison->getOperator()) {
            case Comparison::EQ:
                return function ($object) use ($field, $value) : bool {
                    return static::getObjectFieldValue($object, $field) === $value;
                };

            case Comparison::NEQ:
                return function ($object) use ($field, $value) : bool {
                    return static::getObjectFieldValue($object, $field) !== $value;
                };

            case Comparison::LT:
                return function ($object) use ($field, $value) : bool {
                    return static::getObjectFieldValue($object, $field) < $value;
                };

            case Comparison::LTE:
                return function ($object) use ($field, $value) : bool {
                    return static::getObjectFieldValue($object, $field) <= $value;
                };

            case Comparison::GT:
                return function ($object) use ($field, $value) : bool {
                    return static::getObjectFieldValue($object, $field) > $value;
                };

            case Comparison::GTE:
                return function ($object) use ($field, $value) : bool {
                    return static::getObjectFieldValue($object, $field) >= $value;
                };

            case Comparison::IN:
                return function ($object) use ($field, $value) : bool {
                    return in_array(static::getObjectFieldValue($object, $field), $value);
                };

            case Comparison::NIN:
                return function ($object) use ($field, $value) : bool {
                    return !in_array(static::getObjectFieldValue($object, $field), $value);
                };

            case Comparison::CONTAINS:
                return function ($object) use ($field, $value) {
                    return false !== mb_strpos(static::getObjectFieldValue($object, $field), $value);
                };

            case Comparison::MEMBER_OF:
                return function ($object) use ($field, $value) : bool {
                    $fieldValues = static::getObjectFieldValue($object, $field);
                    if (!is_array($fieldValues)) {
                        $fieldValues = iterator_to_array($fieldValues);
                    }

                    return in_array($value, $fieldValues);
                };

            case Comparison::STARTS_WITH:
                return function ($object) use ($field, $value) : bool {
                    return 0 === mb_strpos(static::getObjectFieldValue($object, $field), $value);
                };

            case Comparison::ENDS_WITH:
                return function ($object) use ($field, $value) : bool {
                    return $value === mb_substr(static::getObjectFieldValue($object, $field), -mb_strlen($value));
                };

            case 'GEO_INTO':
                // $field = Coordinates
                // $value = Zone
                return function ($object) use ($field, $value) : bool {
                    return $value->contains(static::getObjectFieldValue($object, $field));
                };
                break;

            case 'GEO_CONTAINS':
                // $field = Polygone
                // $value = Coordinates
                return function ($object) use ($field, $value) : bool {
                    return static::getObjectFieldValue($object, $field)->contains($value);
                };

            case 'DATETIME_IN':

                break;

            default:
                throw new RuntimeException('Unknown comparison operator: '.$comparison->getOperator());
        }
    }
}
