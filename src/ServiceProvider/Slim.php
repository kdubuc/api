<?php

namespace API\ServiceProvider;

use Slim\DefaultServicesProvider;
use Interop\Container\ContainerInterface as Container;

class Slim extends ServiceProvider
{
    /**
     * Registers services on the given app.
     */
    public function register(Container $container) : void
    {
        (new DefaultServicesProvider())->register($container);
    }

    /**
     * Get service definitions.
     */
    public function getDefinitions() : array
    {
        return [
            'debug'             => 'boolean',
            'settings'          => 'ArrayAccess',
            'environment'       => 'Slim\Interfaces\Http\EnvironmentInterface',
            'request'           => 'Psr\Http\Message\ServerRequestInterface',
            'response'          => 'Psr\Http\Message\ResponseInterface',
            'router'            => 'Slim\Interfaces\RouterInterface',
            'foundHandler'      => 'Slim\Interfaces\InvocationStrategyInterface',
            'errorHandler'      => 'callable',
            'notFoundHandler'   => 'callable',
            'notAllowedHandler' => 'callable',
            'callableResolver'  => 'Slim\CallableResolver',
            'phpErrorHandler'   => 'callable',
        ];
    }
}
