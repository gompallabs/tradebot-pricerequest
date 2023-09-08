<?php

declare(strict_types=1);

namespace App\Infra\DataProcessor\TickDataTransformers;

use App\Domain\DataProcessor\TickDataTransformers\TransformerInterface;

class VolumeTransformer implements TransformerInterface
{
    private ?TransformerInterface $nextTransformer = null;

    public function transform(\ArrayIterator $tickData): \ArrayIterator
    {
        if ($this->nextTransformer !== null) {
            return $this->nextTransformer->transform($tickData);
        }

        return $tickData;
    }

    public function setNext(TransformerInterface $transformer): TransformerInterface
    {
        $this->nextTransformer = $transformer;

        return $transformer;
    }
}
