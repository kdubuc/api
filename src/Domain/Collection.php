<?php

namespace API\Domain;

use Exception;
use API\Domain\Feature\Query;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\ArrayCollection;

class Collection extends ArrayCollection implements Normalizable
{
    use Query;

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
