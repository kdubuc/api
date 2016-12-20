<?php

namespace API\Message\Query;

use API\Message\Handler as BaseHandler;

abstract class Handler extends BaseHandler
{
    /**
     * Handle the command.
     *
     * @param API\Message\Query\Query
     */
    public function handle(Query $query)
    {
        return $this($query);
    }
}
