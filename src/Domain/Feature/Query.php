<?php

namespace API\Domain\Feature;

use API\Domain\Normalizable;

trait Query
{
    /**
     * Query normalizable field using dot notation (https://docs.mongodb.com/manual/core/document/#document-dot-notation).
     */
    public function query(string $field) : array
    {
        // Normalized view
        $normalized = $this->normalize();

        // Explode field name into segments
        $segments = explode('.', $field);

        foreach ($segments as $segment) {
            if (array_key_exists($segment, $normalized)) {
                $normalized = $normalized[$segment];
            } else {
                $normalized = array_map(function ($element) use ($segment) {
                    return $element[$segment];
                }, $normalized);
            }
        }

        if (!is_array($normalized)) {
            $normalized = [$normalized];
        }

        // We try to return a Normalizable corresponding with the field, or a simple
        // data.
        return array_map(function ($data) {
            return static::isDenormalizable($data) ? $data['class_name']::denormalize($data) : $data;
        }, $normalized);
    }

    /**
     * Test if data can be denormalized to obtain Normalizable object.
     */
    public static function isDenormalizable($data) : bool
    {
        return is_array($data) && array_key_exists('class_name', $data) && is_a($data['class_name'], Normalizable::class, true);
    }
}
