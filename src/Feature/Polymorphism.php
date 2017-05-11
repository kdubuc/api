<?php

namespace API\Feature;

use ReflectionClass;

trait Polymorphism
{
    /**
     * Polymorph the method name thanks to object class name.
     *
     * @param string $method_name_prefix
     * @param object $args
     */
    public function polymorph(string $method_name_prefix, $arg)
    {
        $arg_short_name = (new ReflectionClass($arg))->getShortName();

        $method_name = $method_name_prefix.$arg_short_name;

        if (method_exists($this, $method_name)) {
            return $this->$method_name($arg);
        }
    }
}
