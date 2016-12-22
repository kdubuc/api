<?php

namespace API\Domain;

use Doctrine\Common\Collections\ArrayCollection;

class Collection extends ArrayCollection
{
    /**
     * Morph the collection into a new one.
     *
     * @param string $collection_class_name
     *
     * @return API\Domain\Collection
     */
    public function morph(string $collection_class_name) : Collection
    {
        if (!class_exists($collection_class_name) || !is_a($collection_class_name, self::class, true)) {
            throw new Exception($collection_class_name." n'est pas une collection valide", 1);
        }

        return new $collection_class_name($this);
    }
}
