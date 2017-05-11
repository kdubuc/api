<?php

namespace API\Domain\Feature;

use API\Domain\Collection;

trait CollectionBuilder
{
    /**
     * Build a collection which can contains the model.
     *
     * @param array $domain_entities
     *
     * @return API\Domain\Collection
     */
    public static function collection(array $domain_entities = []) : Collection
    {
        $current_class = get_called_class();

        return new Collection(array_filter($domain_entities, function ($entity) use ($current_class) {
            return $entity instanceof $current_class;
        }));
    }
}
