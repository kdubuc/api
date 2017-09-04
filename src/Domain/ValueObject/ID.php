<?php

namespace API\Domain\ValueObject;

use Ramsey\Uuid\Uuid;
use API\Domain\Normalizable;
use Ramsey\Uuid\UuidFactory;

class ID extends ValueObject
{
    protected $uuid;

    /**
     * Generate new ID.
     */
    public function __construct(Uuid $uuid)
    {
        $this->uuid = $uuid;
    }

    /**
     * Return the UUID.
     */
    public function toString() : string
    {
        return $this->uuid->toString();
    }

    /**
     * Normalize the value object into an array.
     */
    public function normalize() : array
    {
        return [
            'uuid'       => $this->toString(),
            'class_name' => get_class($this),
        ];
    }

    /**
     * Build the value object from array.
     */
    public static function denormalize(array $data) : Normalizable
    {
        $factory = new UuidFactory();

        $uuid = $factory->fromString($data['uuid']);

        return new self($uuid);
    }

    /**
     * Generate new ID.
     */
    public static function generate() : self
    {
        return new self(Uuid::uuid4());
    }
}
