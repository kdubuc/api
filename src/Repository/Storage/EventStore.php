<?php

namespace API\Repository\Storage;

use API\Collection\Collection;
use API\Model\Model;
use API\Repository\Criteria;
use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Cache\CacheItemPoolInterface;
use ReflectionClass;

class EventStore implements Storage
{
    /*
     * Constructeur
     *
     * @param Doctrine\ODM\MongoDB\DocumentManager $dm
     * @param Psr\Cache\CacheItemPoolInterface     $cache
     */
    public function __construct(DocumentManager $dm, CacheItemPoolInterface $cache)
    {
        $this->dm    = $dm;
        $this->cache = $cache;
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
        // Get the events raised by the model
        $events = $model->getEvents();

        // Persist all the events
        foreach ($events as $event) {
            $dm->persist($event);
        }

        $slug = get_class($model).$model->getId();

        // Cache the model
        $model_cached = $this->cache->getItem($slug)->set($model);
        $this->cache->save($model_cached);

        // Return the model
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
        // Get the model in the cache
        $model_cached = $this->cache->getItem(get_class($model))->set($model);
        $this->cache->save($model_cached);

        // If model does not exists in the cache
        if (!$model_cached->isHit()) {
            $collection = $class_name::collection();

            $model = (new ReflectionClass($class_name))->newInstanceWithoutConstructor();

            $events = $db->select('SELECT * FROM events WHERE model_id = ?');

            $model->replayEvents();
        }

        $cache->set('cache_name', $model);

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
        return $this->insert($model);
    }

    /**
     * Delete values.
     *
     * @param string                  $class_name
     * @param API\Repository\Criteria $criteria
     *
     * @return API\Collection\Collection
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
