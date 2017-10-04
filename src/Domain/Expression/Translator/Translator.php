<?php

namespace API\Domain\Expression\Translator;

use API\Domain\Expression\Logical;
use API\Domain\Expression\Comparison;
use Doctrine\Common\Collections\Expr\Value;
use Doctrine\Common\Collections\Expr\Expression;
use Doctrine\Common\Collections\Expr\ExpressionVisitor;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\Comparison as DoctrineComparaison;

abstract class Translator extends ExpressionVisitor
{
    /**
     * Translate expression.
     */
    public static function translateExpression(Expression $expression)
    {
        return (new static())->dispatch($expression);
    }

    /**
     * Translate comparison expression into the target query language output (e.g. eq, lte ...).
     */
    abstract protected function translateComparison(Comparison $comparison);

    /**
     * Translate logical expression into the target query language output (e.g. and / or).
     */
    abstract protected function translateLogical(Logical $logical);

    /**
     * Translate orderings into the target query language output (order field and sort type).
     */
    abstract public static function translateOrderings(array $orderings);

    /**
     * Translate slicing into the target query language output (first result and max count).
     */
    abstract public static function translateSlicing(int $limit, int $skip = 0);

    /**
     * Converts an expression into the target query language output.
     */
    public function walkComparison(DoctrineComparaison $comparison)
    {
        return $this->translateComparison($comparison);
    }

    /**
     * Converts a composite expression into the target query language output.
     */
    public function walkCompositeExpression(CompositeExpression $expr)
    {
        if (!($expr instanceof Logical)) {
            $transformer = function ($expr) use (&$transformer) {
                if (!($expr instanceof Logical) && ($expr instanceof CompositeExpression)) {
                    $type        = 'AND' == $expr->getType() ? 'and' : 'or';
                    $expressions = array_map($transformer, $expr->getExpressionList());
                    $expr        = Logical::$type($expressions);
                }

                return $expr;
            };
            $expr = $transformer($expr);
        }

        return $this->translateLogical($expr);
    }

    /**
     * Converts a composite expression into the target query language output.
     */
    public function walkValue(Value $value)
    {
        return $value->getValue();
    }
}
