<?php

namespace API\ServiceProvider;

use Interop\Container\ContainerInterface as Container;

abstract class ServiceProvider
{
    /**
     * Registers services on the given app.
     */
    abstract public function register(Container $app) : void;

    /**
     * Get service definitions.
     */
    abstract public function getDefinitions() : array;
}
