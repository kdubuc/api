<?php

namespace API\Domain;

use API\Domain\Feature\CollectionBuilder;
use API\Domain\Message\Event;
use API\Domain\ValueObject\ID;
use API\Feature\Polymorphism;
use API\Message\CanHandleMessages;
use API\Message\Message;
use Exception;

abstract class Model implements CanHandleMessages, CanBuildCollection
{
    use Polymorphism;
    use CollectionBuilder;

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

        return $this->handle($event);
    }

    /**
     * Get all raised events.
     *
     * @return API\Domain\Message\Event[]
     */
    public function getEventStream() : array
    {
        return $this->events;
    }

    /**
     * Get all raised events and release them.
     *
     * @return API\Domain\Message\Event[]
     */
    public function pullEvents() : array
    {
        $events = $this->getEventStream();

        $this->events = [];

        return $events;
    }

    /**
     * Handle the domain event.
     *
     * @param API\Message\Message $message
     */
    public function handle(Message $message)
    {
        if (!($message instanceof Event)) {
            throw new Exception('Model can handle domain event message only');
        }

        $this->polymorph('handle', $message);

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
            throw new Exception('Unable to change ID');
        }

        $this->id = $id;

        return $this;
    }
}
