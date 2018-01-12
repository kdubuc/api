<?php

namespace API\Domain;

use Exception;
use API\Domain\ValueObject\ID;
use API\Domain\ValueObject\ValueObject;

abstract class Entity extends ValueObject
{
    protected $id;

    /**
     * Get the model id (auto generate when is empty).
     */
    public function getId() : ID
    {
        if (empty($this->id)) {
            $this->setId(ID::generate());
        }

        return $this->id;
    }

    /**
     * Set the model id.
     */
    public function setId(ID $id) : self
    {
        if (!empty($this->id)) {
            throw new Exception('Unable to change ID');
        }

        $this->id = $id;

        return $this;
    }
}
