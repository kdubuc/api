<?php

namespace API\Domain\Message;

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
        if (!empty($this->id)) {
            throw new Exception('Change emitter id is not allowed', 1);
        }

        $this->emitter_id = $emitter_id;

        return $this;
    }

    /**
     * Get the emitter id.
     *
     * @return string
     */
    public function getEmitterId() : ID
    {
        return $this->emitter_id;
    }
}
