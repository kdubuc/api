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
            'meta' => $this->getMeta(),
            'class_name' => get_called_class()
        ];
    }

    /**
     * Denormalize array to obtain a new Collection.
     */
    public static function denormalize(array $data) : Normalizable
    {
        $meta = $data['meta'];

        $data = array_map(function($value) {
            if(is_array($value) && array_key_exists('class_name', $value)) {
                return $value['class_name']::denormalize($value);
            }
            return $value;
        }, $data['data']);

        return new static($data, $meta);
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
            $visitor    = new Expression\Visitor();
            $filter     = $visitor->dispatch($expr);
            $filtered   = array_filter($this->getData(), $filter);
            $collection = $collection->createFrom($filtered);
        }

        // Ordering
        if ($orderings = $criteria->getOrderings()) {
            $next = null;
            foreach (array_reverse($orderings) as $field => $ordering) {
                $next = Expression\Visitor::sortByField($field, $ordering == Criteria::DESC ? -1 : 1, $next);
            }
            $filtered = $collection->getData();
            uasort($filtered, $next);
            $collection = $collection->createFrom($filtered);
        }

        // Slice
        $offset = $criteria->getFirstResult();
        $length = $criteria->getMaxResults();
        if ($offset || $length) {
            $filtered   = array_slice($collection->getData(), (int) $offset, $length);
            $collection = $collection->createFrom($filtered);
        }

        return $collection;
    }
}
