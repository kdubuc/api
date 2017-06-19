<?php

namespace API\Http\Middleware;

use Exception;
use ReflectionClass;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class Error extends Middleware
{
    /**
     * Middleware handler.
     */
    public function __invoke(Request $request, Response $response, callable $next) : Response
    {
        try {
            return $next($request, $response);
        } catch (Exception $e) {
            $reflect = new ReflectionClass($e);

            $error = [
                'type'    => $reflect->getShortName(),
                'message' => $e->getMessage(),
                'code'    => 50000,
            ];

            if ($this->getKernel('settings')->get('debug')) {
                $error['line']  = $e->getLine();
                $error['trace'] = $e->getTraceAsString();
                $error['file']  = $e->getFile();
            }

            return $this->json($response, $error, Middleware::STATUS_BAD_REQUEST);
        }
    }
}
