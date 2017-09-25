<?php

namespace API\Domain;

use Exception;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\ArrayCollection;

class Collection extends ArrayCollection implements Normalizable
{
    /**
     * Initialize collection.
     */
    public function __construct(array $elements = [], $meta = [])
    {
        parent::__construct($elements);

        $this->setMeta($meta);
    }

    /**
     * Morph the collection into a new one.
     */
    public function morph(string $collection_class_name) : Collection
    {
        if (!class_exists($collection_class_name) || !is_a($collection_class_name, self::class, true)) {
            throw new Exception($collection_class_name." n'est pas une collection valide", 1);
        }

        return new $collection_class_name($this->getData(), $this->getMeta());
    }

    /**
     * Normalize the value object into an array.
     */
    public function normalize() : array
    {
        return [
            'data' => $this->map(function ($data) {
                return $data instanceof Normalizable ? $data->normalize() : $data;
            })->getValues(),
            'meta'       => $this->getMeta(),
            'class_name' => get_called_class(),
        ];
    }

    /**
     * Denormalize array to obtain a new Collection.
     */
    public static function denormalize(array $data) : Normalizable
    {
        $meta = $data['meta'];

        $data = array_map(function ($value) {
            return static::isDenormalizable($value) ? $value['class_name']::denormalize($value) : $value;
        }, $data['data']);

        return new static($data, $meta);
    }

    /**
     * Query collection field using dot notation (https://docs.mongodb.com/manual/core/document/#document-dot-notation).
     */
    public function query(string $field) : array
    {
        // If the collection is empty, let it go ..
        if($this->isEmpty()) {
            return [];
        }

        // Get the current segment from the field parameter, and update pendings
        // segments
        $pending_segments = explode('.', $field);
        $current_segment = array_shift($pending_segments);

        // Normalize the collection to obtain an array access.
        // We dig directly into the collection using the current segement because it can
        // be only 'data' or 'meta'.
        // We process other fields after that to avoid nested calls.
        $value = $this->normalize()[$current_segment];
        $current_segment = array_shift($pending_segments);

        // Handle the nested array case ...
        // and update the normalized data with the data on the current segment
        if(!array_key_exists($current_segment, $value)) {

            $array_find_nested_key = function(string $nested_key, array $values) use (&$array_find_nested_key) {

                if(array_key_exists($nested_key, $values)) {
                    return $values[$nested_key];
                }

                foreach($values as $key => $value) {
                    if(is_array($value)) {
                        $nested_data = $array_find_nested_key($nested_key, $value);
                        if(!empty($nested_data)) {
                            return $nested_data;
                        }
                    }
                }

                return null;

            };

            $value = array_map(function($data) use($current_segment, $array_find_nested_key) {
                return is_null($data) ? null : $array_find_nested_key($current_segment, $data);
            }, $value);

        }
        else {
            $value = $value[$current_segment];
        }

        // If there aren't segments left, we try to return a Normalizable corresponding
        // with the field, or simple data.
        if(empty($pending_segments)) {

            $value = is_array($value) ? $value : [$value];

            return array_map(function($data) {
                return static::isDenormalizable($data) ? $data['class_name']::denormalize($data) : $data;
            }, $value);

        }

        // We continue to dig (recursive way o/)
        if(static::isDenormalizable($value)) {
            $value = $value['class_name']::denormalize($value);
            return $value->query(implode(".", $pending_segments));
        }
        else {
            return (new Collection($value))->query("data.".implode(".", $pending_segments));
        }
    }

    /**
     * Test if data can be denormalized to obtain Normalizable object.
     */
    public static function isDenormalizable($data) : bool
    {
        return is_array($data) && array_key_exists('class_name', $data) && is_a($data['class_name'], Normalizable::class, true);
    }

    /**
     * Elements.
     */
    public function getData() : array
    {
        return $this->toArray();
    }

    /**
     * Meta.
     */
    public function getMeta() : array
    {
        return $this->meta;
    }

    /**
     * Set Meta.
     */
    public function setMeta(array $meta) : self
    {
        $this->meta = $meta;

        return $this;
    }

    /**
     * Set Meta value.
     */
    public function setMetaValue(string $key, $value) : self
    {
        $this->meta[$key] = $value;

        return $this;
    }

    /**
     * Matching.
     */
    public function matching(Criteria $criteria) : Collection
    {
        $collection = $this;

        // Filtering
        if ($expr = $criteria->getWhereExpression()) {
            $filter     = Expression\Translator\Closure::translateExpression($expr);
            $collection = $filter($collection);
        }

        // Ordering
        if ($orderings = $criteria->getOrderings()) {
            $order      = Expression\Translator\Closure::translateOrderings($orderings);
            $collection = $order($collection);
        }

        // Slice
        $offset = $criteria->getFirstResult();
        $length = $criteria->getMaxResults();
        if ($offset || $length) {
            $slice      = Expression\Translator\Closure::translateSlicing((int) $length, (int) $offset);
            $collection = $slice($collection);
        }

        return $collection;
    }
}
