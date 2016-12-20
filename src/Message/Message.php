<?php

namespace API\Message;

use ArrayIterator;
use Datetime;
use ReflectionClass;
use ReflectionMethod;

abstract class Message
{
    /**
     * Get the message name (e.g. the type).
     *
     * @return string
     */
    public function getName() : string
    {
        return get_class($this);
    }

    /**
     * Get the short message name (e.g. the type condensed).
     *
     * @return string
     */
    public function getShortName() : string
    {
        $reflection = new ReflectionClass($this);

        return $reflection->getShortName();
    }

    /**
     * Get message id.
     *
     * @return string
     */
    public function getId() : string
    {
        if (empty($this->id)) {
            $this->id = uniqid();
        }

        return $this->id;
    }

    /**
     * Get payload.
     *
     * @return array
     */
    public function getPayload() : array
    {
        $iterator = new ArrayIterator(array_filter(get_object_vars($this), function ($k) {
            return $k != 'id' && $k != 'record_date' && $k != 'committed';
        }, ARRAY_FILTER_USE_KEY));

        return (array) $iterator;
    }

    /**
     * Get the recorded date.
     *
     * @return Datetime
     */
    public function getRecordDate() : Datetime
    {
        if (empty($this->record_date)) {
            $this->record_date = new DateTime();
        }

        return $this->record_date;
    }

    /**
     * Fill the message payload.
     *
     * @return string
     */
    protected function fillPayload(array $args = []) : Message
    {
        $method = new ReflectionMethod($this, '__construct');

        foreach ($method->getParameters() as $parameter) {
            $name     = $parameter->getName();
            $position = $parameter->getPosition();
            $this->set($name, $position >= count($args) ? $parameter->getDefaultValue() : $args[$position]);
        }

        return $this;
    }

    /**
     * Get a payload's property.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function get(string $name)
    {
        return $this->$name;
    }

    /**
     * Set a payload's property.
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return API\Message\Message
     */
    public function set(string $name, $value) : self
    {
        $this->$name = $value;

        return $this;
    }
}
