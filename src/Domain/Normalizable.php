<?php

namespace API\Domain;

interface Normalizable
{
    /**
     * Normalize the value object into an array.
     */
    public function normalize() : array;

    /**
     * Build the value object from array.
     */
    public static function denormalize(array $data) : self;

    /**
     * Query normalizable field using dot notation (https://docs.mongodb.com/manual/core/document/#document-dot-notation).
     * Returns array of results.
     */
    public function query(string $field) : array;

    /**
     * Test if data can be denormalized to obtain Normalizable object.
     */
    public static function isDenormalizable($data) : bool;
}
