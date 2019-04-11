<?php

namespace API\Domain\Expression\Translator;

use RuntimeException;
use API\Domain\Collection;
use API\Domain\Expression\Logical;
use API\Domain\Expression\Comparison;
use Doctrine\Common\Collections\Criteria;

class Closure extends Translator
{
    /**
     * Translate orderings into the target query language output (order field and sort type).
     */
    public static function translateOrderings(array $orderings)
    {
        // Helper for sorting arrays of objects based on multiple fields + orientations.
        $sortByField = function (string $name, int $orientation = 1, callable $next = null) : callable {
            if (!$next) {
                $next = function () : int {
                    return 0;
                };
            }

            return function ($a, $b) use ($name, $next, $orientation) : int {
                $aValue = $a->query($name);
                $bValue = $b->query($name);

                if ($aValue === $bValue) {
                    return $next($a, $b);
                }

                return (($aValue > $bValue) ? 1 : -1) * $orientation;
            };
        };

        return function (Collection $collection) use ($orderings, $sortByField) {
            $next = null;

            foreach (array_reverse($orderings) as $field => $ordering) {
                $field = mb_substr($field, 5);
                $next  = $sortByField($field, Criteria::DESC == $ordering ? -1 : 1, $next);
            }

            $data = $collection->getData();

            uasort($data, $next);

            $class_name = \get_class($collection);

            return new $class_name($data);
        };
    }

    /**
     * Translate slicing into the target query language output (first result and max count).
     */
    public static function translateSlicing(int $limit, int $skip = 0)
    {
        return function (Collection $collection) use ($limit, $skip) {
            $data = $collection->slice((int) $skip, $limit);

            return $collection->filter(function ($element) use ($data) {
                return \in_array($element, $data);
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

        // Operator
        $operator = $comparison->getOperator();

        return function (Collection $collection) use ($operator, $field, $value) {
            // Select the correct filter
            switch ($operator) {
                case 'eq':
                    $filter = function ($object) use ($value) : bool {
                        return $object === $value;
                    };
                    break;

                case 'neq':
                    $filter = function ($object) use ($value) : bool {
                        return $object !== $value;
                    };
                    break;

                case 'lt':
                    $filter = function ($object) use ($value) : bool {
                        return $object < $value;
                    };
                    break;

                case 'lte':
                    $filter = function ($object) use ($value) : bool {
                        return $object <= $value;
                    };
                    break;

                case 'gt':
                    $filter = function ($object) use ($value) : bool {
                        return $object > $value;
                    };
                    break;

                case 'gte':
                    $filter = function ($object) use ($value) : bool {
                        return $object >= $value;
                    };
                    break;

                case 'in':
                    $filter = function ($object) use ($value) : bool {
                        return \in_array($value, $object);
                    };
                    break;

                case 'nin':
                    $filter = function ($object) use ($value) : bool {
                        return !\in_array($object, $value);
                    };
                    break;

                case 'contains':
                    $filter = function ($object) use ($value) : bool {
                        return false !== mb_strpos($object, $value);
                    };
                    break;

                // Geo Within & Intersects operator disabled in Closure mode
                case 'geo_within':
                case 'geo_intersects':
                    // Geo Intersects operator disabled in Closure mode
                    $filter = function ($object) use ($value) : bool {
                        return true;
                    };
                    break;

                default:
                    throw new RuntimeException('Unknown comparison operator: '.$operator);
            }

            $indexes_match = array_keys(array_filter($collection->query($field), $filter));

            return new Collection(array_filter(array_values($collection->toArray()), function ($element, $key) use ($indexes_match) {
                return \in_array($key, $indexes_match);
            }, ARRAY_FILTER_USE_BOTH));
        };
    }

    /**
     * Translate logical expression into the target query language output (e.g. and / or).
     */
    protected function translateLogical(Logical $logical)
    {
        $expressions = array_map(function ($expression) {
            return $this->dispatch($expression);
        }, $logical->getExpressionList());

        switch ($logical->getType()) {
            case 'and':
                return function ($object) use ($expressions) : bool {
                    foreach ($expressions as $expression) {
                        if (!$expression($object)) {
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
                throw new RuntimeException('Unknown composite '.$logical->getType());
        }
    }
}
