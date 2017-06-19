<?php

namespace API\Http\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class Cors extends Middleware
{
    /**
     * Middleware handler.
     */
    public function __invoke(Request $request, Response $response, callable $next) : Response
    {
        $response = $response->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'content-type, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PATCH, DELETE, OPTIONS, PUT');

        return $next($request, $response);
    }
}
