<?php

namespace API\Message;

interface CanHandleMessages
{
    /**
     * Handle the message.
     *
     * @param API\Message\Message $message
     */
    public function handle(Message $message);
}
