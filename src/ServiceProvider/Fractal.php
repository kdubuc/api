<?php

namespace API\ServiceProvider;

use Interop\Container\ContainerInterface as Container;
use League\Fractal\Manager as FractalManager;
use League\Fractal\Serializer\ArraySerializer as FractalSerializer;

class Fractal extends ServiceProvider
{
    /**
     * Registers services on the given app.
     *
     * @param Interop\Container\ContainerInterface $container
     */
    public function register(Container $container)
    {
        $container['fractal'] = function () {
            $fractal = new FractalManager();
            $fractal->setSerializer(new FractalSerializer());

            return $fractal;
        };
    }
}
