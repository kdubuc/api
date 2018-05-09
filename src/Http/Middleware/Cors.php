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

        if($this->isPreflightRequest($request)) {
            return $response;
        }

        return $next($request, $response);
    }

    /**
     * Detect if the actual request is a Preflisght request.
     */
    public function isPreflightRequest(Request $request) : bool
    {
        return $request->getMethod() === 'OPTIONS'
            && $request->hasHeader('Origin')
            && $request->hasHeader('Access-Control-Request-Method');
    }
}
