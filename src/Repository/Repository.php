<?php

namespace API\Repository;

use API\Domain\Collection;
use API\Domain\AggregateRoot;
use API\Domain\ValueObject\ID;
use API\Repository\Storage\Storage;
use Doctrine\Common\Collections\Criteria;

class Repository
{
    /**
     * Build the repository with a storage strategy.
     */
    public function __construct(string $class_name, Storage $storage)
    {
        $this->storage    = $storage;
        $this->class_name = $class_name;
    }

    /**
     * Selects all elements and returns them as a collection.
     */
    public function all() : Collection
    {
        return $this->matching(Criteria::create());
    }

    /**
     * Selects all elements from a selectable that match the expression and
     * returns a new collection containing these elements.
     */
    public function matching(Criteria $criteria) : Collection
    {
        return $this->storage->select($this->class_name, $criteria);
    }

    /**
     * Returns the value at specified offset.
     */
    public function get(ID $id) : AggregateRoot
    {
        $criteria = Criteria::create()->where(Criteria::expr()->eq('id.uuid', $id->toString()));

        $collection = $this->storage->select($this->class_name, $criteria);

        if ($collection->isEmpty()) {
            throw new \Exception('Collection empty', 1);
        }

        return $collection->first();
    }

    /**
     * Add element.
     */
    public function add(AggregateRoot $model) : AggregateRoot
    {
        if ($this->contains($model)) {
            return $this->storage->update($model);
        } else {
            return $this->storage->insert($model);
        }
    }

    /**
     * Set element.
     */
    public function set(AggregateRoot $model) : AggregateRoot
    {
        return $this->add($model);
    }

    /**
     * Remove element.
     */
    public function remove(AggregateRoot $aggregate_root) : AggregateRoot
    {
        $criteria = Criteria::create()->where(Criteria::expr()->eq('id.uuid', $aggregate_root->getId()->toString()));

        $this->storage->delete($this->class_name, $criteria);

        return $model;
    }

    /**
     * Test if the element exists in the repository.
     */
    public function contains(AggregateRoot $aggregate_root) : bool
    {
        $criteria = Criteria::create()->where(Criteria::expr()->eq('id.uuid', $aggregate_root->getId()->toString()));

        return empty($this->storage->select($this->class_name, $criteria));
    }
}
