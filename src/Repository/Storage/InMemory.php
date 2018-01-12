<?php

namespace API\Repository\Storage;

use API\Domain\Collection;
use API\Domain\AggregateRoot;
use Doctrine\Common\Collections\Criteria;

class InMemory implements Storage
{
    /**
     * Constructor.
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * Insert model.
     */
    public function insert(AggregateRoot $aggregate_root) : AggregateRoot
    {
        $class_name = get_class($aggregate_root);

        $this->data[$class_name][$aggregate_root->getId()->toString()] = $aggregate_root;

        return $aggregate_root;
    }

    /**
     * Get collections.
     */
    public function select($class_name, Criteria $criteria = null) : Collection
    {
        if (!array_key_exists($class_name, $this->data)) {
            return $class_name::collection();
        }

        $collection = $class_name::collection($this->data[$class_name]);

        return empty($criteria) ? $collection : $collection->matching($criteria);
    }

    /**
     * Update model.
     */
    public function update(AggregateRoot $aggregate_root) : AggregateRoot
    {
        return $this->insert($aggregate_root);
    }

    /**
     * Delete values.
     */
    public function delete($class_name, Criteria $criteria = null) : Collection
    {
        $collection = $this->select($class_name, $criteria);

        foreach ($collection as $aggregate_root) {
            unset($this->data[$class_name][$aggregate_root->getId()->toString()]);
        }

        return $collection;
    }

    /**
     * Count results.
     */
    public function count($class_name, Criteria $criteria = null) : int
    {
        return count($this->select($class_name, $criteria));
    }
}
