<?php

namespace API\Domain\Expression;

use Exception;
use Doctrine\Common\Collections\Expr\Comparison as DoctrineComparison;

class Comparison extends DoctrineComparison implements Expression
{
    /**
     * Comparison helper.
     * Accept : eq, neq, lt, lte, gt, gte.
     */
    public static function __callStatic(string $name, array $arguments) : self
    {
        $field    = $arguments['0'];
        $operator = $name;
        $value    = $arguments['1'];

        if (!in_array($operator, ['eq', 'neq', 'lt', 'lte', 'gt', 'gte', 'in', 'nin', 'contains', 'geo_within', 'geo_intersects'])) {
            throw new Exception('Operator invalid');
        }

        return new self($field, $operator, $value);
    }
}
