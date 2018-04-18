<?php

namespace API\Repository\Storage;

use API\Domain\Collection;
use API\Domain\AggregateRoot;
use Doctrine\Common\Collections\Criteria;

interface Storage
{
    /**
     * Get collection.
     */
    public function select($class_name, Criteria $criteria = null) : Collection;

    /**
     * Delete values.
     */
    public function delete($class_name, Criteria $criteria = null) : void;

    /**
     * Insert model.
     */
    public function insert(AggregateRoot $aggregate_root) : void;

    /**
     * Update model.
     */
    public function update(AggregateRoot $aggregate_root) : void;

    /**
     * Count results.
     */
    public function count($class_name, Criteria $criteria = null) : int;
}
