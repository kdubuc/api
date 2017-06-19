<?php

namespace API;

use Slim\App as Slim;
use Psr\Http\Message\ResponseInterface as Response;

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
}
