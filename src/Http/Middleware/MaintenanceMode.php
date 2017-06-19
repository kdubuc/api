<?php

namespace API\Http\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class MaintenanceMode extends Middleware
{
    /**
     * Middleware handler.
     */
    public function __invoke(Request $request, Response $response, callable $next) : Response
    {
        if (!$this->getKernel()->get('maintenance')) {
            return $next($request, $response);
        } else {
            return $response->withStatus(Middleware::STATUS_SERVICE_UNAVAILABLE, "L'API est actuellement en maintenance. Veuillez réessayez plus tard.");
        }
    }
}
