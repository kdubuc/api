<?php

namespace API\ServiceProvider;

use Interop\Container\ContainerInterface as Container;
use Slim\DefaultServicesProvider;

class Slim extends ServiceProvider
{
    /**
     * Registers services on the given app.
     *
     * @param Interop\Container\ContainerInterface $container
     */
    public function register(Container $container)
    {
        (new DefaultServicesProvider())->register($container);
    }

    /**
     * Get service definitions.
     *
     * @return array
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
