<?php

namespace API\Repository;

use API\Domain\Collection;
use API\Domain\Model;
use API\Domain\ValueObject\ID;
use API\Repository\Storage\Storage;

class Repository
{
    /**
     * Build the repository with a storage strategy.
     *
     * @param string                         $class_name
     * @param API\Repository\Storage\Storage $storage
     */
    public function __construct(string $class_name, Storage $storage)
    {
        $this->storage    = $storage;
        $this->class_name = $class_name;
    }

    /**
     * Selects all elements from a selectable that match the expression and
     * returns a new collection containing these elements.
     *
     * @param API\Repository\Criteria $criteria
     *
     * @return API\Collection\Collection
     */
    public function matching(Criteria $criteria) : Collection
    {
        return $this->storage->select($this->class_name, $criteria);
    }

    /**
     * Returns the value at specified offset.
     *
     * @param API\Domain\ValueObject\ID $id The offset to retrieve
     *
     * @return API\Domain\Model
     */
    public function get(ID $id) : Model
    {
        $criteria = Criteria::create()->where(Criteria::expr()->eq('id', $id));

        $collection = $this->storage->select($this->class_name, $criteria);

        if ($collection->isEmpty()) {
            throw new \Exception('Collection empty', 1);
        }

        return $collection->first();
    }

    /**
     * Add element.
     *
     * @param API\Domain\Model The model to add
     *
     * @return API\Domain\Model
     */
    public function add(Model $model) : Model
    {
        if ($this->contains($model)) {
            return $this->storage->update($model);
        } else {
            return $this->storage->insert($model);
        }
    }

    /**
     * Set element.
     *
     * @param API\Domain\Model The model to set
     */
    public function set(Model $model) : Model
    {
        return $this->add($model);
    }

    /**
     * Remove element.
     *
     * @param API\Domain\Model The model to remove
     *
     * @return API\Domain\Model
     */
    public function remove(Model $model) : Model
    {
        $criteria = Criteria::create()->where(Criteria::expr()->eq('id', $model->getId()));

        $this->storage->delete($this->class_name, $criteria);

        return $model;
    }

    /**
     * Test if the element exists in the repository.
     *
     * @param bool
     *
     * @return bool
     */
    public function contains(Model $model) : bool
    {
        $criteria = Criteria::create()->where(Criteria::expr()->eq('id', $model->getId()));

        return empty($this->storage->select($this->class_name, $criteria));
    }
}
