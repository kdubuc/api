<?php

namespace API\Message\Command;

use API\Message\Handler as BaseHandler;
use API\Message\Message;

class Handler extends BaseHandler
{
    /**
     * Handle the command.
     *
     * @param API\Message\Message\Message $message
     */
    public function handle(Message $message)
    {
        if (!($message instanceof Command)) {
            throw new Exception('Command handler can handle command message only');
        }

        parent::handle($message);
    }
}
