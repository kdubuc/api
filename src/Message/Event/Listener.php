<?php

namespace API\Message\Event;

use API\Message\Handler;
use API\Message\Message;

abstract class Listener extends Handler
{
    /**
     * Handle the event.
     *
     * @param API\Message\Message\Message $message
     */
    public function handle(Message $message)
    {
        if (!($message instanceof Event)) {
            throw new Exception('Event handler can handle event message only');
        }

        parent::handle($message);
    }
}
