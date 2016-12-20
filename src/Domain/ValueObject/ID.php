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
     * Stringify the value object.
     *
     * @return string
     */
    public function toString() : string
    {
        return $this->uuid->toString();
    }

    /**
     * Unserialize value object.
     *
     * @return API\Domain\ValueObject\ID
     */
    public static function fromString(string $serialized)
    {
        $factory = new UuidFactory();

        return new self($factory->fromString($serialized));
    }

    /**
     * Generate new ID.
     *
     * @return API\Domain\ValueObject\ID
     */
    public static function generate() : ID
    {
        return new self(Uuid::uuid4());
    }
}
