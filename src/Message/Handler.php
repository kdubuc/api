<?php

namespace API\Message;

use API\Feature\ContainerAccess;
use API\Feature\Polymorphism;

abstract class Handler implements CanHandleMessages
{
    use ContainerAccess, Polymorphism;

    /**
     * Handle the message.
     *
     * @param API\Message\Message\Message
     */
    public function handle(Message $message)
    {
        return $this->polymorph('handle', $message);
    }
}
