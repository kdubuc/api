<?php

namespace API\Domain\Message;

use ReflectionClass;
use Datetime;
use Exception;
use API\Domain\ValueObject\ID;
use API\Message\Event\Event as BaseEvent;

abstract class Event extends BaseEvent
{
    /**
     * Set the Emitter Id.
     *
     * @param API\Domain\ValueObject\ID $emitter_id
     *
     * @return $this
     */
    public function setEmitterId(ID $emitter_id) : Event
    {
        if (!empty($this->emitter_id)) {
            throw new Exception('Change emitter id is not allowed');
        }

        $this->emitter_id = $emitter_id;

        return $this;
    }

    /**
     * Get the emitter id.
     *
     * @return API\Domain\ValueObject\ID
     */
    public function getEmitterId() : ID
    {
        return $this->emitter_id;
    }

    /**
     * Rebuild event from the record date, and payload.
     *
     * @param API\Domain\Message\Event
     *
     * @return API\Domain\Message\Event
     */
    public static function rebuild(string $event_id, ID $emitter_id, Datetime $record_date, array $payload = []) : Event
    {
        $current_class = get_called_class();

        $reflection = new ReflectionClass($current_class);

        $event = $reflection->newInstanceWithoutConstructor();

        $event_id = $reflection->getProperty('id');
        $event_id->setAccessible(true);
        $event_id->setValue($event, $event_id);

        $event_record_date = $reflection->getProperty('record_date');
        $event_record_date->setAccessible(true);
        $event_record_date->setValue($event, $record_date);

        $event->setEmitterId($emitter_id);

        $event->fillPayload($payload);

        return $event;
    }
}
