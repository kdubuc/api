<?php

namespace API\Message\Event;

use API\Feature\KernelAccess;
use API\Feature\Polymorphism;
use League\Event\AbstractListener;
use League\Event\EventInterface as Event;

abstract class Listener extends AbstractListener
{
    use KernelAccess;
    use Polymorphism;

    /**
     * Handle the event.
     */
    public function handle(Event $event) : void
    {
        $this->polymorph('handle', $event);
    }
}
