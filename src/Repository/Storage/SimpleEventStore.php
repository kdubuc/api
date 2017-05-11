<?php

namespace API\Repository\Storage;

use API\Domain\Collection;
use API\Domain\Model;
use API\Domain\ValueObject\ID;
use API\Repository\Criteria;
use Datetime;
use Doctrine\DBAL\Connection;
use ReflectionClass;
use RuntimeException;

class SimpleEventStore implements Storage
{
    /*
     * Constructeur
     *
     * @param Doctrine\DBAL\Connection $connection
     */
    public function __construct(Connection $connection, $table_name = 'events')
    {
        $this->dbal       = $connection;
        $this->table_name = $table_name;
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
        $events = $model->getEventStream();

        foreach ($events as $event) {

            $payload = json_encode($event->getPayload());

            if ($payload === false) {
                throw new RuntimeException(json_last_error_msg(), json_last_error());
            }

            $this->dbal->insert($this->table_name, [
                'id'                 => $event->getId(),
                'record_date'        => $event->getRecordDate()->format('c'),
                'name'               => $event->getName(),
                'emitter_id'         => $event->getEmitterId()->toArray()['uuid'],
                'emitter_class_name' => get_class($model),
                'payload'            => $payload
            ]);

        }

        return $model;
    }

    /**
     * Get collection.
     *
     * @param string                  $class_name
     * @param API\Repository\Criteria $criteria
     *
     * @return API\Domain\Collection
     */
    public function select($class_name, Criteria $criteria = null) : Collection
    {
        // Get the query builder
        $query_builder = $this->dbal->createQueryBuilder();

        // Query base
        $query = $query_builder->select('*')->from($this->table_name);

        // Get all events for the class name
        if (empty($criteria) || empty($criteria->getWhereExpression()) || $criteria->getWhereExpression()->getField() != 'id') {

            // Set the pagination thanks to getFirstResult and getMaxResults of criteria
            if(!empty($criteria)) {

                if(is_int($criteria->getFirstResult()) && empty($criteria->getMaxResults())) {
                    $query->setFirstResult($criteria->getFirstResult());
                }
                elseif(is_int($criteria->getFirstResult()) && is_int($criteria->getMaxResults())) {
                    $query->setFirstResult($criteria->getFirstResult())->setMaxResults($criteria->getMaxResults());
                }

                $criteria->setFirstResult(0);

            }

            $query->select('DISTINCT emitter_id')->where('emitter_class_name = ?');

            $events = $this->dbal->fetchAll($query, [$class_name]);

            $criteria->where(Criteria::expr()->in('id', array_map(function($id) {
                return ID::fromArray(['uuid' => $id['emitter_id']]);
            }, $events)));

            return $this->select($class_name, $criteria);

        }
        elseif ($criteria->getWhereExpression()->getField() == 'id') {

            $query->where('emitter_id IN (?)')->orderBy('record_date', Criteria::ASC);

            $emitters_id = $criteria->getWhereExpression()->getValue()->getValue();

            $stmt = $this->dbal->executeQuery($query->getSQL(), [
                array_map(function($emitter_id) {
                    return !is_array($emitter_id) ? $emitter_id->toArray()['uuid'] : $emitter_id['uuid'];
                }, $emitters_id)
            ], [
                Connection::PARAM_INT_ARRAY
            ]);

            $events = $stmt->fetchAll();

        }

        $models = [];

        // Rebuild models and push on the collection
        foreach ($events as $event) {
            if (!array_key_exists($event['emitter_id'], $models)) {
                // Initialize an empty model (without call construct because it has to be
                // initialized like an empty shell)
                $reflection = new ReflectionClass($class_name);
                $model      = $reflection->newInstanceWithoutConstructor();
                $id         = $reflection->getProperty('id');
                $id->setAccessible(true);
                $id->setValue($model, ID::fromArray(['uuid' => $event['emitter_id']]));
                $models[$event['emitter_id']] = $model;
            }

            $event_id = $event['id'];
            $emitter_id = ID::fromArray(['uuid' => $event['emitter_id']]);
            $record_date = Datetime::createFromFormat(DateTime::ISO8601, $event['record_date']);
            $payload = json_decode($event['payload'], true);

            if (is_null($payload)) {
                throw new RuntimeException(json_last_error_msg(), json_last_error());
            }

            $event = $event['name']::rebuild($event_id, $emitter_id, $record_date, $payload);

            $models[$event->getEmitterId()->toArray()['uuid']]->handle($event);
        }

        $collection = $class_name::collection($models);

        if (!empty($criteria)) {
            $collection = $collection->matching($criteria);
        }

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
        return $class_name::collection([$this->insert($model)]);
    }
}
