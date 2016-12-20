<?php

namespace API;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\App as Slim;

abstract class Foundation extends Slim
{
    /**
     * Foundation.
     *
     * @param API\Kernel $kernel
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
     *
     * @param API\Kernel $kernel
     *
     * @return Psr\Http\Message\ResponseInterface
     */
    public static function power(Kernel $kernel) : Response
    {
        return (new static($kernel))->run();
    }
}
