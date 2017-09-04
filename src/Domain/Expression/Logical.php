<?php

namespace API\Domain\Expression;

use Exception;
use Doctrine\Common\Collections\Expr\CompositeExpression;

class Logical extends CompositeExpression
{
    /**
     * Logical helper.
     * Accept : and, or.
     */
    public static function __callStatic(string $name, array $arguments) : Logical
    {
        $type       = $name;
        $expression = $arguments[0];

        if (!in_array($type, ['and', 'or'])) {
            throw new Exception('Type invalid');
        }

        return new self($type, $expression);
    }
}
