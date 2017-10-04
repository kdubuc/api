<?php

namespace API\Repository\Storage;

use MongoDB;
use ReflectionClass;
use API\Domain\Collection;
use API\Domain\Expression;
use API\Domain\AggregateRoot;
use API\Domain\Message\Event;
use API\Domain\ValueObject\ID;
use Doctrine\Common\Collections\Criteria;

class EventStore implements Storage
{
    /*
     * Constructeur
     */
    public function __construct(MongoDB\Database $database, $collection_name = 'events')
    {
        $this->collection = $database->{$collection_name};
    }

    /**
     * Insert model.
     */
    public function insert(AggregateRoot $aggregate_root) : AggregateRoot
    {
        // Get the AR new events
        $events = $aggregate_root->getEventStream()->getEventsEmitted();

        // Optimistic locking
        // We find the latest version of the AR in collection
        $latest_version_of_ar = $this->collection->findOne([
            'emitter_id' => $aggregate_root->getId()->toString(),
        ], [
            'sort' => [
                'record_date' => 1,
            ],
        ]);

        // If the Current AR version < Collection AR version, we abort
        if (null !== $latest_version_of_ar && $latest_version_of_ar['record_date'] > last($events)->getRecordDate()) {
            throw new Exception('Optimistic locking');
        }

        // Insert all new events
        $this->collection->insertMany(array_map(function ($event) {
            return $event->toArray();
        }, $events));

        return $aggregate_root;
    }

    /**
     * Get collection.
     */
    public function select($class_name, Criteria $criteria = null) : Collection
    {
        // If no criteria was provided, we create an empty one.
        if (empty($criteria)) {
            $criteria = new Criteria();
        }

        // Base filter : we select all events for class name.
        $query = ['emitter_class_name' => $class_name];

        // Base options : sort by record_date ASC (for correct rebuilding)
        $options = [
            'sort' => [
                'record_date' => 1,
            ],
        ];

        // To rebuild correctly entities, we need to get ALL events (based on
        // the emitter id field) for all ARs asked, before apply criterias.
        // If no specific ARs asked, we get them ALL !
        // So, first, we need to get all asked ids (from where expression or all
        // disctinct emitter id in collection)
        if (empty($aggregate_root_ids = $this->extractIdsFromExpression($criteria->getWhereExpression()))) {
            $aggregate_root_ids = $this->collection->distinct('emitter_id', $query, $options);
        }

        // Now we have all ARs ids, we can get all events corresponding.
        $cursor = $this->collection->find(array_merge($query, ['emitter_id' => ['$in' => $aggregate_root_ids]]), $options);
        $events = array_map(function ($document) {
            $event = json_decode(json_encode($document), true);

            return $event['name']::recordFromArray($event);
        }, $cursor->toArray());

        // Rebuild all ARs asked with events and collect them into a domain collection
        if (!empty($events)) {
            // Rebuild all ARs
            $aggregate_roots = array_map(function ($aggregate_root_id) use ($class_name, $events) {
                return $this->rebuildAggregateRoot($class_name, $aggregate_root_id, $events);
            }, $aggregate_root_ids);

            // Filter the collection with criteria parameter
            $collection = $class_name::collection($aggregate_roots)->matching($criteria);
        } else {
            $collection = $class_name::collection();
        }

        // Return the collection which contain all AR o/
        return $collection;
    }

    /**
     * Extract all ids (data.id.uuid) comparison from where expression.
     */
    private function extractIdsFromExpression(Expression\Expression $where_expression = null) : array
    {
        $ids = [];

        if (!empty($where_expression)) {
            $expressions = $where_expression instanceof Expression\Logical ? $where_expression->getExpressionList() : [$where_expression];

            foreach ($expressions as $expression) {
                if ($expression instanceof Expression\Comparison && 'data.id.uuid' == $expression->getField()) {
                    if ('eq' == $expression->getOperator()) {
                        $ids[] = $expression->getValue()->getValue();
                    } elseif ('in' == $expression->getOperator()) {
                        $ids = $ids + $expression->getValue()->getValue();
                    }
                }
            }
        }

        return $ids;
    }

    /**
     * Rebuild model.
     */
    private function rebuildAggregateRoot(string $class_name, string $aggregate_root_id, array $events) : AggregateRoot
    {
        // Initialize an empty model (without call construct because it has to be
        // initialized like an empty shell)
        $reflection     = new ReflectionClass($class_name);
        $aggregate_root = $reflection->newInstanceWithoutConstructor();

        // Filter events to keep only AR events
        $events = array_filter($events, function ($event) use ($aggregate_root_id) {
            return $event->getEmitterId()->toString() == $aggregate_root_id;
        });

        // Fire all events against the model
        foreach ($events as $event) {
            $aggregate_root->handle($event);
        }

        return $aggregate_root;
    }

    /**
     * Upgrade event.
     */
    public function upgradeEvent(string $deprecated_event_name, callable $callable) : void
    {
        $deprecated_events_query = [['name' => $deprecated_event_name]];

        $deprecated_events = $this->collection->find($deprecated_events_query);

        $new_events = array_map($callable, $deprecated_events);

        $this->collection->deleteMany($deprecated_events_query);

        $this->collection->insertMany(array_map(function ($event) {
            return $event->toArray();
        }, $new_events));
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
        $aggregate_roots = $this->select($class_name, $criteria);

        // TODO: Fire delete event to all ARs

        return $aggregate_roots;
    }

    /**
     * Count results.
     */
    public function count($class_name, Criteria $criteria = null) : int
    {
        return $this->select($class_name, $criteria)->count();
    }
}
