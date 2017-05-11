<?php

namespace API\Message;

use ArrayIterator;
use Datetime;
use ReflectionClass;
use ReflectionMethod;
use API\Domain\Collection;
use API\Domain\ValueObject\ValueObject;

abstract class Message
{
    protected $id, $record_date;

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
     * Payload array will be filtered to inlcude only construct params
     *
     * @return array
     */
    public function getPayload() : array
    {
        $method = new ReflectionMethod($this, '__construct');

        $parameters_name = array_map(function($parameter) {
            return $parameter->getName();
        }, $method->getParameters());

        $iterator = new ArrayIterator(array_filter(get_object_vars($this), function ($k) use ($parameters_name) {
            return in_array($k, $parameters_name);
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
    public function fillPayload(array $args = []) : Message
    {
        $current_class = get_called_class();

        $method = new ReflectionMethod($this, '__construct');

        $parameters_name = array_map(function($parameter) {
            return $parameter->getName();
        }, $method->getParameters());

        $args = (array) new ArrayIterator(array_filter($args, function ($k) use ($parameters_name) {
            return in_array($k, $parameters_name);
        }, ARRAY_FILTER_USE_KEY));

        foreach ($method->getParameters() as $parameter) {

            $name = $parameter->getName();
            $type = $parameter->getType()->__toString();

            if(is_subclass_of($type, ValueObject::class) && is_array($args[$name])) {
                $value = $type::fromArray($args[$name]);
            }
            else if($type == Collection::class) {
                if($args[$name] instanceof Collection) {
                    $value = $args[$name];
                }
                else if(!empty($args[$name])) {
                    $elements = array_map(function($element) {
                        if(is_array($element)) {
                            $type_vo = "\\".$element['value_object_class_name'];
                            return $type_vo::fromArray($element);
                        }
                        else {
                            return $element;
                        }
                    }, $args[$name]);
                    $value = new Collection($elements);
                }
                else {
                    $value = new Collection();
                }
            }
            else {
                $value = $args[$name];
            }

            $this->set($name, $value);
            // $this->set($name, $position >= count($args) ? $parameter->getDefaultValue() : $args[$position]);
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
