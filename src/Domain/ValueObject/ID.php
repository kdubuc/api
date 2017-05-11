<?php

namespace API\Domain\ValueObject;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidFactory;

class ID extends ValueObject
{
    protected $uuid;

    /**
     * Generate new ID.
     *
     * @param Ramsey\Uuid\Uuid $uuid
     */
    public function __construct(Uuid $uuid)
    {
        $this->uuid = $uuid;
    }

    /**
     * Return the UUID.
     *
     * @return string
     */
    public function toString() : string
    {
        return $this->uuid->toString();
    }

    /**
     * Convert the value object into an array.
     *
     * @return array
     */
    public function toArray() : array
    {
        return array_merge(parent::toArray(), [
            'uuid' => $this->toString()
        ]);
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
        $factory = new UuidFactory();

        $uuid = $factory->fromString($input['uuid']);

        return new self($uuid);
    }

    /**
     * Generate new ID.
     *
     * @return API\Domain\ValueObject\ID
     */
    public static function generate() : self
    {
        return new self(Uuid::uuid4());
    }
}
