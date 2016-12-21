<?php

namespace API\Domain;

use Exception;
use API\Domain\Message\Event;
use API\Domain\ValueObject\ID;

abstract class Model
{
    protected $id;
    protected $events = [];

    /**
     * Raise an event.
     *
     * @param API\Domain\Message\Event $event
     *
     * @return self
     */
    protected function raiseEvent(Event $event) : Model
    {
        $event->setEmitterId($this->getId());

        $this->events[] = $event;

        return $this->applyEvent($event);
    }

    /**
     * Get committed events.
     *
     * @return API\Domain\Message\Event[]
     */
    public function getCommittedEvents() : array
    {
        return array_filter($this->events, function ($event) {
            return $event->isCommitted();
        });
    }

    /**
     * Get uncommitted events.
     *
     * @return API\Domain\Message\Event[]
     */
    public function getUncommittedEvents() : array
    {
        return array_diff($this->events, $this->getCommittedEvents());
    }

    /**
     * Apply event.
     *
     * @param API\Domain\Message\Event $event
     *
     * @return API\Domain\Model
     */
    public function applyEvent(Event $event) : self
    {
        $method_name = 'apply'.$event->getShortName();

        if (method_exists($this, $method_name)) {
            $this->$method_name($event);
        }

        return $this;
    }

    /**
     * Replay all committed events.
     *
     * @return API\Domain\Model
     */
    public function replayEvents() : self
    {
        $events = $this->getCommittedEvents();

        foreach ($events as $event) {
            $this->applyEvent($event);
        }

        return $this;
    }

    /**
     * Get the model id (auto generate when is empty).
     *
     * @return API\Domain\ValueObject\ID
     */
    public function getId() : ID
    {
        if (empty($this->id)) {
            $this->setId(ID::generate());
        }

        return $this->id;
    }

    /**
     * Set the model id.
     *
     * @param API\Domain\ValueObject\ID $id
     *
     * @return API\Domain\Model
     */
    public function setId(ID $id) : Model
    {
        if (!empty($this->id)) {
            throw new Exception('Unable to change ID', 1);
        }

        $this->id = $id;

        return $this;
    }

    /**
     * Build a collection which can contains the model.
     *
     * @param array $models
     *
     * @return API\Domain\Collection
     */
    public static function collection(array $models = []) : Collection
    {
        $current_class = get_called_class();

        return new Collection(array_filter($models, function ($model) use ($current_class) {
            return $model instanceof $current_class;
        }));
    }
}
