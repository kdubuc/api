<?php

namespace API\Domain;

use API\Domain\ValueObject\ValueObject;

interface Normalizable
{
    /**
     * Normalize the value object into an array.
     */
    public function normalize() : array;

    /**
     * Build the value object from array.
     */
    public static function denormalize(array $data) : Normalizable;
}
