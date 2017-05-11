<?php

namespace API\Domain;

interface CanBuildCollection
{
    /**
     * Build a collection which can contains the entity.
     *
     * @param array $domain_entities
     *
     * @return API\Domain\Collection
     */
    public static function collection(array $domain_entities = []) : Collection;
}
