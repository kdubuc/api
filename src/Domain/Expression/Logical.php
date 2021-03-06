<?php

namespace API\Domain\Expression;

use Exception;
use Doctrine\Common\Collections\Expr\CompositeExpression;

class Logical extends CompositeExpression implements Expression
{
    /**
     * Logical helper.
     * Accept : and, or.
     */
    public static function __callStatic(string $name, array $arguments) : self
    {
        $type       = $name;
        $expression = $arguments[0];

        if (!in_array($type, ['and', 'or'])) {
            throw new Exception('Type invalid');
        }

        return new self($type, $expression);
    }
}
