<?php

namespace API;

use Exception;
use Slim\Container as Pimple;
use Interop\Container\ContainerInterface as Container;

class Kernel extends Pimple implements Container
{
    private $definitions = [];

    /**
     * Create new kernel.
     */
    public function __construct(array $values = [])
    {
        $this->fillWithValues($values);

        // If the debug mode is activated, enable error details display.
        $values['settings']['displayErrorDetails'] = true === $values['debug'];
        error_reporting($values['settings']['displayErrorDetails'] ? -1 : 0);

        // Provide defaults services
        $this->provide(new ServiceProvider\Slim());
        $this->provide(new ServiceProvider\Fractal());

        // Continue the constructor
        parent::__construct($values);
    }

    /**
     * Fill the kernel with an array of values.
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
     */
    protected function provide(ServiceProvider\ServiceProvider $provider, array $values = []) : self
    {
        $provider->register($this);

        $this->definitions = array_merge($this->definitions, $provider->getDefinitions());

        $this->fillWithValues($values);

        return $this;
    }

    /**
     * Check Kernel Integrity with the definitions's declaration.
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
            ((is_scalar($entry) || gettype($entry) == 'resource') && gettype($entry) == $instance_name_expected) ||
            ('array' == $instance_name_expected && is_array($entry)) ||
            ($entry instanceof $instance_name_expected) ||
            ('callable' == $instance_name_expected && is_callable($entry))
        ) {
            return $entry;
        }

        throw new Exception('Kernel Integrity Exception : '.$id.' bad instance. '.gettype($entry).' given, '.$instance_name_expected.' expected.');
    }

    /**
     * Get Kernel definitions.
     */
    public function getDefinitions() : array
    {
        return $this->definitions;
    }
}
