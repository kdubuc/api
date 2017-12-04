<?php

namespace API\ServiceProvider;

use League\Fractal\Manager as FractalManager;
use Interop\Container\ContainerInterface as Container;
use League\Fractal\Serializer\ArraySerializer as FractalSerializer;

class Fractal extends ServiceProvider
{
    /**
     * Registers services on the given app.
     */
    public function register(Container $container) : void
    {
        $container['fractal.json'] = function () {
            $fractal = new FractalManager();
            $fractal->setSerializer(new FractalSerializer());

            return $fractal;
        };
    }

    /**
     * Get service definitions.
     */
    public function getDefinitions() : array
    {
        return [
            'fractal.json' => FractalManager::class,
        ];
    }
}
