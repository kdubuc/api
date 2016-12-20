<?php

namespace API\Message\Command;

use API\Message\Handler as BaseHandler;

class Handler extends BaseHandler
{
    /**
     * Handle the command.
     *
     * @param API\Message\Command\Command
     */
    public function handle(Command $command)
    {
        $this($command);
    }
}
