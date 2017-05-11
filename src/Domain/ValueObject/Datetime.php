<?php

namespace API\Domain\ValueObject;

use ReflectionClass;
use Datetime as DatetimeBase;
use DateTimeZone;

class Datetime extends ValueObject
{
    protected $datetime;

    /**
     * Build new Datetime.
     *
     * @param Datetime $datetime
     */
    public function __construct(DatetimeBase $datetime = null)
    {
        $this->datetime = $datetime ?? new DatetimeBase();
    }

    /**
     * Convert the value object into an array.
     *
     * @return array
     */
    public function toArray() : array
    {
        return array_merge(parent::toArray(), (array) $this->datetime);
    }

    /**
     * Build the value object from array.
     *
     * @return array $input
     *
     * @return API\Domain\ValueObject\ValueObject
     */
    public static function fromArray(array $input) : ValueObject
    {
        $timezone = new DateTimeZone($input['timezone']);

        $datetime = new DatetimeBase($input['date'], $timezone);

        return new self($datetime);
    }

    /**
     * Proxy undefined calls to Datetime object
     *
     * @return string $method
     * @return array $parameters
     *
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return call_user_func_array(array($this->datetime, $method), $parameters);
    }
}
