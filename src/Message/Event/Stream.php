<?php

namespace API\Message\Event;

use Exception;
use Traversable;
use ArrayIterator;
use IteratorAggregate;
use League\Event\Emitter;
use API\Feature\KernelAccess;
use Interop\Container\ContainerInterface as Container;

class Stream extends Emitter implements IteratorAggregate
{
    use KernelAccess;

    /**
     * Build the stream.
     */
    public function __construct(Container $container = null)
    {
        $this->container      = $container;
        $this->events_emitted = [];
    }

    /**
     * Get all events emitted in chronological order (old -> new).
     */
    public function getEventsEmitted() : array
    {
        return $this->events_emitted;
    }

    /**
     * Emit an event.
     */
    public function emit($event) : Event
    {
        $this->events_emitted[] = $event;

        return parent::emit($event);
    }

    /**
     * Wait for an event (based on event's name).
     * Not async.
     */
    public function waitFor(string $event_name, int $timeout = 10) : Event
    {
        // Tick rate definitions
        $tick_count = 0;
        $tick_delay = 10000;

        do {
            // Wait for tick
            usleep($tick_delay);

            // Increment the tick count
            ++$tick_count;

            // Get all messages dispatched with the same name
            $messages = array_filter($this->getEventsEmitted(), function ($message) use ($event_name) {
                return $message->getName() === $event_name;
            });

            // Get the first element in the result
            $message = array_shift($messages);

            // If the element is not null, return the event !
            if (null !== $message) {
                return $message;
            }
        } while ($tick_count * $tick_delay < $timeout * 1000000);

        throw new Exception('Timeout reached');
    }

    /**
     * Enable Traversable.
     */
    public function getIterator() : Traversable
    {
        return new ArrayIterator($this->getEventsEmitted());
    }
}
