<?php

namespace API\Repository\Storage;

use API\Domain\Collection;
use API\Domain\Model;
use API\Repository\Criteria;
use Doctrine\ORM\EntityManager;

class Doctrine2 implements Storage
{
    /*
     * Constructeur
     *
     * @param Doctrine\ORM\EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Insert model.
     *
     * @param API\Model\Model $model
     *
     * @return API\Model\Model
     */
    public function insert(Model $model) : Model
    {
        $this->em->persist($model);

        $this->em->flush();

        return $model;
    }

    /**
     * Get collections.
     *
     * @param string                  $class_name
     * @param API\Repository\Criteria $criteria
     *
     * @return API\Collection\Collection
     */
    public function select($class_name, Criteria $criteria = null) : Collection
    {
        $collection = $this->em->getRepository($class_name)->matching($criteria);

        $collection = new Collection($collection->toArray());

        return $collection;
    }

    /**
     * Update model.
     *
     * @param API\Model\Model $model
     *
     * @return API\Model\Model
     */
    public function update(Model $model) : Model
    {
        if ($this->em->contains($model)) {
            $this->em->flush();

            return $model;
        } else {
            return $this->insert($model);
        }
    }

    /**
     * Delete values.
     *
     * @param string                  $class_name
     * @param API\Repository\Criteria $criteria
     *
     * @return array
     */
    public function delete($class_name, Criteria $criteria = null) : Collection
    {
        $collection = $this->select($class_name, $criteria);

        foreach ($collection as $model) {
            $this->em->remove($model);
        }

        $this->em->flush();

        return $collection;
    }
}
