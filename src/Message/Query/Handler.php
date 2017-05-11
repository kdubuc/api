<?php

namespace API\Message\Query;

use API\Message\Handler as BaseHandler;
use API\Message\Message;

abstract class Handler extends BaseHandler
{
    /**
     * Handle the query.
     *
     * @param API\Message\Message\Message $message
     */
    public function handle(Message $message)
    {
        if (!($message instanceof Query)) {
            throw new Exception('Query handler can handle query message only');
        }

        return parent::handle($message);
    }
}
