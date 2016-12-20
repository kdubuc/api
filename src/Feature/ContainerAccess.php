<?php

namespace API\Feature;

use Interop\Container\ContainerInterface as Container;

trait ContainerAccess
{
    /**
     * Provide container to the controller.
     *
     * @param Interop\Container\ContainerInterface $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Enable access to the DI.
     *
     * @return Interop\Container\ContainerInterface
     */
    public function getContainer() : Container
    {
        return $this->container;
    }
}
