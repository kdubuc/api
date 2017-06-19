<?php

namespace API\Http\Middleware;

use API\Http\Http;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

abstract class Middleware extends Http
{
    /**
     * Middleware handler.
     */
    abstract public function __invoke(Request $request, Response $response, callable $next) : Response;
}
