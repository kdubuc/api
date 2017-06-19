<?php

namespace API\Transformer;

use API\Domain\Collection;
use API\Feature\KernelAccess;
use League\Fractal\TransformerAbstract as FractalTransformer;

abstract class Transformer extends FractalTransformer
{
    use KernelAccess;

    /**
     * Create a new item resource object.
     */
    protected function item($data, $transformer, $resourceKey = null) : array
    {
        $manager = $this->getCurrentScope()->getManager();

        $resource = parent::item($data, $transformer, $resourceKey);

        $scope = $manager->createData($resource);

        return $scope->toArray();
    }

    /**
     * Create a new collection resource object.
     */
    protected function collection($data, $transformer, $resourceKey = null) : array
    {
        $manager = $this->getCurrentScope()->getManager();

        $resource = parent::collection($data, $transformer, $resourceKey);

        $resource->setMeta($data->getMeta());

        $scope = $manager->createData($resource);

        return $scope->toArray();
    }
}
