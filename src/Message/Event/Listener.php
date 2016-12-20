<?php

namespace API\Message\Event;

use API\Message\Handler;

abstract class Listener extends Handler
{
    /**
     * Handle the event.
     *
     * @param API\Message\Event\Event
     */
    public function handle(Event $event)
    {
        $this($event);
    }
}
