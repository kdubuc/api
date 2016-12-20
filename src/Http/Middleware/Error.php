<?php

namespace API\Http\Middleware;

use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use ReflectionClass;

class Error extends Middleware
{
    /**
     * Middleware handler.
     *
     * @param Psr\Http\Message\ServerRequestInterface $request
     * @param Psr\Http\Message\ResponseInterface      $response
     * @param callable                                $next
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

            if ($this->getContainer('settings')->get('debug')) {
                $error['line']  = $e->getLine();
                $error['trace'] = $e->getTraceAsString();
                $error['file']  = $e->getFile();
            }

            return $this->json($response, $error, Middleware::STATUS_BAD_REQUEST);
        }
    }
}
