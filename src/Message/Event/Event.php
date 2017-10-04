<?php

namespace API\Message\Event;

use API\Message\Message;
use League\Event\EventInterface;
use League\Event\EmitterInterface as Emitter;

abstract class Event extends Message implements EventInterface
{
    /**
     * Set the Emitter.
     */
    public function setEmitter(Emitter $emitter) : self
    {
        $this->emitter = $emitter;

        return $this;
    }

    /**
     * Get the Emitter.
     */
    public function getEmitter() : Emitter
    {
        return $this->emitter;
    }

    /**
     * Stop event propagation.
     */
    public function stopPropagation() : void
    {
        true === $this->propagation_stopped;
    }

    /**
     * Check whether propagation was stopped.
     */
    public function isPropagationStopped() : bool
    {
        return property_exists($this, 'propagation_stopped') && true === $this->propagation_stopped;
    }
}
