<?php

namespace API\Repository\Storage\ExpressionVisitor;

use RuntimeException;
use Doctrine\Common\Collections\Criteria;
use API\Domain\Expression\Logical;
use API\Domain\Expression\Comparison;
use API\Domain\Expression\Translator\Translator;

class MongoDB extends Translator
{
    /**
     * Translate orderings into the target query language output (order field and sort type).
     */
    public static function translateOrderings(array $orderings)
    {
        return [];
        
        return [
            'sort' => array_merge(array_map(function(string $column, string $sort) {
                return [$column => $sort == Criteria::ASC ? 1 : -1];
            }, array_keys($orderings), $orderings))
        ];
    }

    /**
     * Translate slicing into the target query language output (first result and max count).
     */
    public static function translateSlicing(int $limit, int $skip = 0)
    {
        return [
            'limit' => $limit,
            'skip' => (int) $skip,
        ];
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
                return [$field => $value];

            case "neq":
                return [$field => ['$ne' => $value]];

            case "lt":
                return [$field => ['$lt' => $value]];

            case "lte":
                return [$field => ['$lte' => $value]];

            case "gt":
                return [$field => ['$gt' => $value]];

            case "gte":
                return [$field => ['$gte' => $value]];

            case "in":
                return [$field => ['$in' => $value]];

            case "nin":
                return [$field => ['$nin' => $value]];

            case "contains":
                return [$field => ['$regex' => ".*$value.*"]];

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
                return ['$and' => $expressions];
            case 'or':
                return ['$or' => $expressions];
            default:
                throw new RuntimeException("Unknown composite " . $expr->getType());
        }
    }
}
