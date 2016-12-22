<?php

namespace API;

use Exception;
use Interop\Container\ContainerInterface as Container;
use Slim\Container as Pimple;
use Slim\DefaultServicesProvider;

class Kernel extends Pimple implements Container
{
    /**
     * Create new kernel.
     *
     * @param array $values The parameters or objects
     */
    public function __construct(array $values = [])
    {
        // If the debug mode is activated, enable error details display.
        $values['settings']['displayErrorDetails'] = $values['debug'] === true;

        // Provide defaults services
        $this->provide(new ServiceProvider\Fractal());
        $this->provide(new class() extends ServiceProvider\ServiceProvider {
            public function register(Container $app)
            {
                (new DefaultServicesProvider())->register($app);
            }
        });

        // Continue the constructor
        parent::__construct($values);

        // Check kernel integrity
        $this->checkIntegrity();
    }

    /**
     * Fill the kernel with an array of values.
     *
     * @param array $values
     *
     * @return self
     */
    protected function fillWithValues(array $values) : self
    {
        foreach ($values as $key => $value) {
            if (array_key_exists($key, $this->getDefinitions())) {
                $this[$key] = $value;
            }
        }

        return $this;
    }

    /**
     * Provide service.
     *
     * @param API\ServiceProvider\Serviceprovider $provider
     * @param array                               $values
     *
     * @return self
     */
    protected function provide(ServiceProvider\ServiceProvider $provider, array $values = []) : self
    {
        $provider->register($this);

        $this->fillWithValues($values);

        return $this;
    }

    /**
     * Check Kernel Integrity with the definitions's declaration.
     *
     * @return self
     */
    public function checkIntegrity() : self
    {
        $definitions = $this->getDefinitions();

        foreach ($definitions as $key => $instance_name) {
            if (!$this->has($key)) {
                throw new Exception('Kernel Integrity Exception : '.$key.' ('.$instance_name.') is missed');
            }
        }

        return $this;
    }

    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $id Identifier of the entry to look for
     *
     * @return mixed Entry
     */
    public function get($id)
    {
        $definitions            = $this->getDefinitions();
        $instance_name_expected = $definitions[$id];

        if (empty($instance_name_expected)) {
            throw new Exception('Kernel Integrity Exception : '.$id.' not found.');
        }

        $entry = $this->offsetGet($id);

        if (
            (is_scalar($entry) && gettype($entry) == $instance_name_expected) ||
            ($instance_name_expected == 'array' && is_array($entry)) ||
            ($entry instanceof $instance_name_expected) ||
            ($instance_name_expected == 'callable' && is_callable($entry))
        ) {
            return $entry;
        }

        throw new Exception('Kernel Integrity Exception : '.$id.' bad instance. '.gettype($entry).' given, '.$instance_name_expected.' expected.');
    }

    /**
     * Get Kernel definitions.
     *
     * @return array
     */
    protected function getDefinitions() : array
    {
        return [
            'debug'             => 'boolean',
            'fractal'           => 'League\Fractal\Manager',
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
            'phpErrorHandler'   => 'callable'
        ];
    }
}
