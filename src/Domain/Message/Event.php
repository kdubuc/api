<?php

namespace API\Domain\Message;

use Exception;
use API\Message\Message;
use API\Domain\ValueObject\ID;
use API\Message\Event\Event as BaseEvent;

abstract class Event extends BaseEvent
{
    /**
     * Record new message.
     */
    public static function recordFromArray(array $data) : Message
    {
        $event = parent::recordFromArray($data);

        $event->setEmitterId(ID::denormalize(['uuid' => $data['emitter_id']]));
        $event->setEmitterClassName($data['emitter_class_name']);

        return $event;
    }

    /**
     * Set the Emitter Id.
     */
    public function setEmitterId(ID $emitter_id) : self
    {
        if (!empty($this->emitter_id)) {
            throw new Exception('Change emitter id is not allowed');
        }

        $this->emitter_id = $emitter_id;

        return $this;
    }

    /**
     * Get the emitter id.
     */
    public function getEmitterId() : ID
    {
        return $this->emitter_id;
    }

    /**
     * Set the emitter class name.
     */
    public function setEmitterClassName(string $emitter_class_name) : self
    {
        if (!empty($this->emitter_class_name)) {
            throw new Exception('Change emitter class name is not allowed');
        }

        $this->emitter_class_name = $emitter_class_name;

        return $this;
    }

    /**
     * Get the emitter class name.
     */
    public function getEmitterClassName() : string
    {
        return $this->emitter_class_name;
    }

    /**
     * Return array representation.
     */
    public function toArray() : array
    {
        return array_merge(parent::toArray(), [
            'emitter_id'         => $this->getEmitterId()->normalize()['uuid'],
            'emitter_class_name' => $this->getEmitterClassName(),
        ]);
    }
}
