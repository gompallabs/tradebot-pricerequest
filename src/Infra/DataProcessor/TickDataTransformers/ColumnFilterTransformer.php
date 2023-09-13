<?php

declare(strict_types=1);

namespace App\Infra\DataProcessor\TickDataTransformers;

use App\Domain\DataProcessor\SampleDataMapper;
use App\Domain\DataProcessor\TickDataTransformers\TransformerInterface;

class ColumnFilterTransformer implements TransformerInterface
{
    private ?TransformerInterface $nextTransformer = null;
    private SampleDataMapper $dataMapper;

    public function __construct(SampleDataMapper $dataMapper)
    {
        $this->dataMapper = $dataMapper;
    }

    public function transform(\ArrayIterator $tickData): \ArrayIterator
    {
        $newIterator = new \ArrayIterator();
        foreach ($tickData as $sample) {
            $newSample = $this->dataMapper->mapToTs($sample);
            $newIterator->append($newSample);
        }
        if ($this->nextTransformer !== null) {
            return $this->nextTransformer->transform($newIterator);
        }

        return $tickData;
    }

    public function setNext(TransformerInterface $transformer): TransformerInterface
    {
        $this->nextTransformer = $transformer;

        return $transformer;
    }
}
