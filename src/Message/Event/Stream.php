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
            // Get all messages dispatched with the same name
            $messages = array_filter($this->getEventsEmitted(), function ($message) use ($event_name) {
                return $message->getName() === $event_name;
            });

            // Get the last element (most recent) in the result
            $message = array_pop($messages);

            // If the element is not null, return the event !
            if (null !== $message) {
                return $message;
            }

            // Wait for tick
            usleep($tick_delay);

            // Increment the tick count
            ++$tick_count;
            
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

    /**
     * Affect a listener to handle multiple events.
     */
    public function attachListener(Listener $listener, array $events_names = []) : void
    {
        // If no events names are given, listen ALL events.
        if (0 == count($events_names)) {
            $events_names[] = '*';
        }

        // Attach listeners for all events given
        foreach ($events_names as $event_name) {
            $this->addListener($event_name, $listener);
        }
    }
}
