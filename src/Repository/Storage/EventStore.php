<?php

namespace API\Repository\Storage;

use MongoDB;
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
        $this->events = $database->{$collection_name};
    }

    /**
     * Insert model.
     */
    public function insert(AggregateRoot $aggregate_root) : void
    {
        // Get the AR new events to be persisted
        $events = $aggregate_root->getEventStream()->getEventsEmitted();

        // Optimistic locking

        // We find the latest version of the AR in collection
        $latest_version_of_ar = $this->events->findOne([
            'emitter_id' => $aggregate_root->getId()->toString(),
        ], [
            'sort' => [
                'record_date' => 1,
            ],
        ]);

        // If the Current AR version < Collection AR version, we abort
        if (null !== $latest_version_of_ar && $latest_version_of_ar['record_date'] > end($events)->getRecordDate()) {
            throw new Exception('Optimistic locking');
        }

        // Insert all new events
        $this->events->insertMany(array_map(function ($event) {
            return $event->toArray();
        }, $events));
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
            $aggregate_root_ids = $this->events->distinct('emitter_id', $query, $options);
        }

        // Now we have all ARs ids, we can get all events corresponding.
        $cursor = $this->events->find(array_merge($query, ['emitter_id' => ['$in' => $aggregate_root_ids]]), $options);
        $events = array_map(function ($document) {
            $event = json_decode(json_encode($document), JSON_OBJECT_AS_ARRAY);

            return $event['class_name']::recordFromArray($event);
        }, $cursor->toArray());

        // If we have no events, we return an empty collection
        if (empty($events)) {
            return $class_name::collection();
        }

        // Rebuild all ARs
        $aggregate_roots = array_map(function ($aggregate_root_id) use ($class_name, $events) {
            // Fetch all events corresponding with the AR ID, and rebuild it !
            return $class_name::rebuildFromEvents(array_filter($events, function ($event) use ($aggregate_root_id) {
                return $event->getEmitterId()->toString() == $aggregate_root_id;
            }));
        }, $aggregate_root_ids);

        // Filter the collection with criteria parameter
        return $class_name::collection($aggregate_roots)->matching($criteria);
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
     * Upgrade event version.
     */
    public function upgradeEvent(string $deprecated_event_name, callable $callable) : void
    {
        $deprecated_events_query = [['name' => $deprecated_event_name]];

        $deprecated_events = $this->events->find($deprecated_events_query);

        $new_events = array_map($callable, $deprecated_events);

        $this->events->deleteMany($deprecated_events_query);

        $this->events->insertMany(array_map(function ($event) {
            return $event->toArray();
        }, $new_events));
    }

    /**
     * Update model.
     */
    public function update(AggregateRoot $aggregate_root) : void
    {
        // Event store manage in the same way the insert and update operations
        $this->insert($aggregate_root);
    }

    /**
     * Delete values.
     */
    public function delete($class_name, Criteria $criteria = null) : void
    {
        // We get all ARs corresponding with criteria
        $aggregate_roots = $this->select($class_name, $criteria);

        // Delete all ARs events in the event store
        foreach ($aggregate_roots as $aggregate_root) {
            $this->events->deleteMany(['emitter_id' => $aggregate_root->getId()->toString()]);
        }
    }

    /**
     * Count results.
     */
    public function count($class_name, Criteria $criteria = null) : int
    {
        if (null === $criteria) {
            $query = ['emitter_class_name' => $class_name];

            return count($this->events->distinct('emitter_id', $query));
        }

        return $this->select($class_name, $criteria)->count();
    }
}
