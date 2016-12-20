<?php

namespace API\Repository\Storage;

use API\Domain\Collection;
use API\Domain\Model;
use API\Repository\Criteria;

class InMemory implements Storage
{
    /**
     * Constructeur.
     *
     * @param array $data
     */
    public function __construct($data = [])
    {
        $this->data = $data;
    }

    /**
     * Insert model.
     *
     * @param API\Domain\Model $model
     *
     * @return API\Domain\Model
     */
    public function insert(Model $model) : Model
    {
        $this->data[get_class($model)][$model->getId()->toString()] = $model;

        return $model;
    }

    /**
     * Get collections.
     *
     * @param string                  $class_name
     * @param API\Repository\Criteria $criteria
     *
     * @return API\Domain\Collection
     */
    public function select($class_name, Criteria $criteria = null) : Collection
    {
        if (!array_key_exists($class_name, $this->data)) {
            throw new \Exception($class_name.' not found');
        }

        $collection = $class_name::collection($this->data[$class_name]);

        if (!empty($criteria)) {
            $collection = $collection->matching($criteria);
        }

        return $collection;
    }

    /**
     * Update model.
     *
     * @param API\Domain\Model $model
     *
     * @return API\Domain\Model
     */
    public function update(Model $model) : Model
    {
        return $this->insert($model);
    }

    /**
     * Delete values.
     *
     * @param string                  $class_name
     * @param API\Repository\Criteria $criteria
     *
     * @return API\Domain\Collection
     */
    public function delete($class_name, Criteria $criteria = null) : Collection
    {
        $collection = $this->select($class_name, $criteria);

        foreach ($collection as $model) {
            unset($this->data[$class_name][$model->getId()]);
        }

        return $collection;
    }
}
