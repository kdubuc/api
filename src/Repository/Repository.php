<?php

namespace API\Repository;

use API\Domain\Collection;
use API\Domain\Expression;
use API\Domain\AggregateRoot;
use API\Domain\ValueObject\ID;
use API\Repository\Storage\Storage;
use Doctrine\Common\Collections\Criteria;

class Repository
{
    /**
     * Build the repository with a storage strategy.
     */
    public function __construct(string $class_name, Storage $storage, string $collection_class_name = 'API\\Domain\\Collection')
    {
        $this->storage               = $storage;
        $this->class_name            = $class_name;
        $this->collection_class_name = $collection_class_name;
    }

    /**
     * Selects all elements and returns them as a collection.
     */
    public function all() : Collection
    {
        return $this->matching(Criteria::create())->morph($this->collection_class_name);
    }

    /**
     * Selects all elements from a selectable that match the expression and
     * returns a new collection containing these elements.
     */
    public function matching(Criteria $criteria) : Collection
    {
        return $this->storage->select($this->class_name, $criteria)->morph($this->collection_class_name);
    }

    /**
     * Returns the value at specified offset.
     */
    public function get(ID $id) : AggregateRoot
    {
        $criteria = Criteria::create()->where(Expression\Comparison::eq('id.uuid', $id->toString()));

        $collection = $this->matching($criteria);

        if ($collection->isEmpty()) {
            throw new \Exception('Collection empty', 1);
        }

        return $collection->first();
    }

    /**
     * Add element.
     */
    public function add(AggregateRoot $aggregate_root) : AggregateRoot
    {
        $operation = $this->contains($aggregate_root) ? 'update' : 'insert';

        $this->storage->$operation($aggregate_root);

        return $aggregate_root;
    }

    /**
     * Set element.
     */
    public function set(AggregateRoot $aggregate_root) : AggregateRoot
    {
        return $this->add($aggregate_root);
    }

    /**
     * Remove element.
     */
    public function remove(AggregateRoot $aggregate_root) : AggregateRoot
    {
        $criteria = Criteria::create()->where(Expression\Comparison::eq('id.uuid', $aggregate_root->getId()->toString()));

        $this->storage->delete($this->class_name, $criteria);

        return $aggregate_root;
    }

    /**
     * Test if the element exists in the repository.
     */
    public function contains(AggregateRoot $aggregate_root) : bool
    {
        $criteria = Criteria::create()->where(Expression\Comparison::eq('id.uuid', $aggregate_root->getId()->toString()));

        return empty($this->matching($criteria));
    }
}
