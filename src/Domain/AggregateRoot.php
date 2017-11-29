<?php

namespace API\Domain;

use ReflectionClass;
use API\Domain\Message\Event;
use API\Feature\Polymorphism;
use API\Message\Event\Stream;
use League\Event\EventInterface;
use League\Event\ListenerInterface;

abstract class AggregateRoot extends Entity implements ListenerInterface
{
    use Polymorphism;

    /**
     * Raise an event.
     */
    protected function raiseEvent(Event $event) : void
    {
        $event->setEmitterId(@$event->get('id') ?? $this->getId());

        $event->setEmitterClassName(get_called_class());

        $this->getEventStream()->emit($event);
    }

    /**
     * Get all raised events.
     */
    public function getEventStream() : Stream
    {
        if (!property_exists($this, 'event_stream') || null === $this->event_stream) {
            $this->event_stream = new Stream();

            $this->event_stream->addListener('*', $this);
        }

        return $this->event_stream;
    }

    /**
     * Get all raised events and release them.
     */
    public function pullEvents() : array
    {
        $events = $this->getEventStream()->getEventsEmitted();

        $this->event_stream = null;

        return $events;
    }

    /**
     * Handle an event.
     */
    public function handle(EventInterface $event) : void
    {
        $this->polymorph('handle', $event);
    }

    /**
     * Check whether the listener is the given parameter.
     */
    public function isListener($listener) : bool
    {
        return $this === $listener;
    }

    /**
     * Rebuild the AR from a list of events.
     */
    public static function rebuildFromEvents(array $events) : self
    {
        // Initialize an empty model (without calling construct because it has to be
        // initialized like an empty shell)
        $reflection     = new ReflectionClass(get_called_class());
        $aggregate_root = $reflection->newInstanceWithoutConstructor();

        // Fire all events against the AR
        foreach ($events as $event) {
            $aggregate_root->handle($event);
        }

        return $aggregate_root;
    }
}
