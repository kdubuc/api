<?php

namespace API\Message\Event;

use API\Message\Message;

abstract class Event extends Message
{
    private $committed;

    /**
     * Check event commit status.
     *
     * @return bool
     */
    public function isCommitted() : bool
    {
        return $this->committed === true;
    }

    /**
     * Commit event.
     *
     * @return self
     */
    public function commit() : Event
    {
        $this->committed = true;

        return $this;
    }
}
