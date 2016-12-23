<?php

namespace API\ServiceProvider;

use Interop\Container\ContainerInterface as Container;

abstract class ServiceProvider
{
    /**
     * Registers services on the given app.
     *
     * @param Interop\Container\ContainerInterface $container
     */
    abstract public function register(Container $app);

    /**
     * Get service definitions.
     *
     * @return array
     */
    abstract public function getDefinitions() : array;
}
