<?php

namespace API\Repository\Storage;

use API\Domain\Collection;
use API\Domain\Model;
use API\Repository\Criteria;

interface Storage
{
    /**
     * Get collection.
     *
     * @param string                  $class_name
     * @param API\Repository\Criteria $criteria
     *
     * @return API\Domain\Collection
     */
    public function select($class_name, Criteria $criteria = null) : Collection;

    /**
     * Delete values.
     *
     * @param string                  $class_name
     * @param API\Repository\Criteria $criteria
     *
     * @return API\Domain\Collection
     */
    public function delete($class_name, Criteria $criteria = null) : Collection;

    /**
     * Insert model.
     *
     * @param API\Domain\Model $model
     *
     * @return API\Domain\Model
     */
    public function insert(Model $model) : Model;

    /**
     * Update model.
     *
     * @param API\Domain\Model $model
     *
     * @return API\Domain\Model
     */
    public function update(Model $model) : Model;
}
