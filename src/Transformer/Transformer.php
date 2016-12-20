<?php

namespace API\Transformer;

use API\Feature\ContainerAccess;
use League\Fractal\TransformerAbstract as FractalTransformer;

abstract class Transformer extends FractalTransformer
{
    use ContainerAccess;

    /**
     * Create a new item resource object.
     *
     * @param mixed                        $data
     * @param TransformerAbstract|callable $transformer
     * @param string                       $resourceKey
     *
     * @return Item
     */
    protected function item($data, $transformer, $resourceKey = null)
    {
        $manager = $this->getContainer()->get('fractal');

        $scope = $manager->createData(parent::item($data, $transformer, $resourceKey));

        return $scope->toArray();
    }

    /**
     * Create a new collection resource object.
     *
     * @param mixed                        $data
     * @param TransformerAbstract|callable $transformer
     * @param string                       $resourceKey
     *
     * @return Collection
     */
    protected function collection($data, $transformer, $resourceKey = null)
    {
        $manager = $this->getContainer()->get('fractal');

        $scope = $manager->createData(parent::collection($data, $transformer, $resourceKey));

        return $scope->toArray();
    }
}
