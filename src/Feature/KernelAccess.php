<?php

namespace API\Feature;

use Interop\Container\ContainerInterface as Container;

trait KernelAccess
{
    /**
     * Provide container to the controller.
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Enable access to the DI.
     */
    public function getKernel() : Container
    {
        return $this->container;
    }
}
