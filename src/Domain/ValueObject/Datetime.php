<?php

namespace API\Domain\ValueObject;

use API\Domain\Normalizable;
use Datetime as DatetimeBase;

class Datetime extends ValueObject
{
    const RFC3339_EXTENDED_V2 = 'Y-m-d\TH:i:s.uP';

    protected $datetime;

    /**
     * Build new Datetime.
     */
    public function __construct(DatetimeBase $datetime = null)
    {
        $this->datetime = $datetime ?? new DatetimeBase();
    }

    /**
     * Proxy undefined calls to Datetime object.
     */
    public function __call($method, $parameters)
    {
        return call_user_func_array([$this->datetime, $method], $parameters);
    }

    /**
     * Normalize the value object into an array.
     */
    public function normalize() : array
    {
        return [
            'iso8601'    => $this->datetime->format(self::RFC3339_EXTENDED_V2),
            'class_name' => get_class($this),
        ];
    }

    /**
     * Build the value object from array.
     */
    public static function denormalize(array $data) : Normalizable
    {
        $datetime = DatetimeBase::createFromFormat(self::RFC3339_EXTENDED_V2, $data['iso8601']);

        return new self($datetime);
    }
}
