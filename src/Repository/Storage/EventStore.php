<?php

namespace API\Repository\Storage;

use ReflectionClass;
use API\Domain\Collection;
use API\Domain\AggregateRoot;
use API\Domain\Message\Event;
use Doctrine\DBAL\Connection;
use API\Domain\ValueObject\ID;
use Doctrine\Common\Collections\Criteria;
use API\Domain\Expression;
use Doctrine\Common\Collections\Expr\CompositeExpression;

class EventStore implements Storage
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
     */
    public function insert(AggregateRoot $aggregate_root) : AggregateRoot
    {
        $events = $aggregate_root->getEventStream();

        foreach ($events as $event) {
            $this->dbal->insert($this->table_name, array_merge($event->toArray(), [
                'payload' => json_encode($event->toArray()['payload']),
            ]));
        }

        return $aggregate_root;
    }

    /**
     * Get collection.
     */
    public function select($class_name, Criteria $criteria = null) : Collection
    {
        // Get the query builder thanks to the DBAL Connection
        $query_builder = $this->dbal->createQueryBuilder();

        // Query base : we select all events .
        $query = $query_builder->select('*')->from($this->table_name);

        // If no criteria was provided, we create an empty one.
        if (empty($criteria)) {
            $criteria = new Criteria();
        }

        // To rebuild correctly entities, we need to get all emitters IDs (aka the
        // id of the entity which raise the event) of the class name and with criterias.
        // After that, we will filter the results.
        $ids                         = [];
        $criteria_on_id_field_exists = false;
        $where_expression            = $criteria->getWhereExpression();
        if (!empty($where_expression)) {
            $expressions = $where_expression instanceof CompositeExpression ? $where_expression->getExpressionList() : [$where_expression];
            foreach ($expressions as $expression) {
                if ($expression instanceof Expression\Comparison && $expression->getField() == 'id.uuid') {
                    if ($expression->getOperator() == 'eq') {
                        $ids[] = $expression->getValue()->getValue();
                    } elseif ($expression->getOperator() == 'in') {
                        $ids = $ids + $expression->getValue()->getValue();
                    }
                    $criteria_on_id_field_exists = true;
                }
            }
        }
        if (empty($ids) && !$criteria_on_id_field_exists) {
            $query->select('DISTINCT emitter_id')
                ->where('emitter_class_name = :class_name')
                ->setParameter('class_name', $class_name);
            $criteria->andWhere(Expression\Comparison::in('id.uuid', array_column($this->dbal->fetchAll($query, $query->getParameters()), 'emitter_id')));

            return $this->select($class_name, $criteria);
        }

        // Now we have all emitters ids. We can get all events corresponding with
        // the ids and order by record date (for correct rebuilding).
        $query->where('emitter_id IN (?)')->orderBy('record_date', Criteria::ASC);
        $stmt   = $this->dbal->executeQuery($query->getSQL(), [$ids], [Connection::PARAM_STR_ARRAY]);
        $events = array_map(function ($event) {
            $event['payload'] = json_decode($event['payload'], true);

            return Event::recordFromArray($event);
        }, $stmt->fetchAll());

        // Group events by emitter id
        $events_by_emitters = [];
        foreach ($events as $key => $event) {
            $events_by_emitters[$event->getEmitterId()->toString()][] = $event;
        }

        // Initialize a domain collection.
        $collection = $class_name::collection();

        // Rebuild all models with events and add them in the collection
        foreach ($events_by_emitters as $event_by_emitter) {
            $aggregate_root = $this->rebuildAggregateRoot($class_name, $event_by_emitter);
            $collection->add($aggregate_root);
        }

        // Filter the collection with criteria
        $collection = $collection->matching($criteria);

        return $collection;
    }

    /**
     * Rebuild model.
     */
    private function rebuildAggregateRoot(string $class_name, array $events = []) : AggregateRoot
    {
        // Initialize an empty model (without call construct because it has to be
        // initialized like an empty shell)
        $reflection     = new ReflectionClass($class_name);
        $aggregate_root = $reflection->newInstanceWithoutConstructor();

        // Fire all events against the model
        foreach ($events as $event) {
            $aggregate_root->handle($event);
        }

        return $aggregate_root;
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
        return $class_name::collection([$this->insert($aggregate_root)]);
    }
}
