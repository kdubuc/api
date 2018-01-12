<?php

namespace API;

use Slim\App as Slim;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Interfaces\RouteGroupInterface as RouteGroup;

abstract class Foundation extends Slim
{
    /**
     * Foundation.
     */
    public function __construct(Kernel $kernel)
    {
        // Set UTC Timezone for the entire API.
        date_default_timezone_set('UTC');

        // Continue the configuration.
        parent::__construct($kernel);
    }

    /**
     * Power on !
     */
    public static function power(Kernel $kernel) : Response
    {
        return (new static($kernel))->run();
    }

    /**
     * Enable access to the kernel.
     */
    public function getKernel() : Kernel
    {
        return $this->getContainer();
    }

    /**
     * Mount sub-api as a RouteGroup prefixed on the current API foundation.
     */
    public function mount(string $prefix, self $api) : RouteGroup
    {
        return $this->group($prefix, function () use ($api) {
            $router = $api->getKernel()->get('router');
            foreach ($router->getRoutes() as $route) {
                $mounted_route = $this->map($route->getMethods(), $route->getPattern(), $route->getCallable());
                $mounted_route->setArguments($route->getArguments());
                $mounted_route->setOutputBuffering($route->getOutputBuffering());
                $mounted_route->setContainer($api->getKernel());
                foreach ($route->getMiddleware() as $middleware) {
                    $mounted_route->add($middleware);
                }
            }
        })->setContainer($api->getKernel());
    }
}
