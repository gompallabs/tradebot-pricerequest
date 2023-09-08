<?php

declare(strict_types=1);

namespace App\Infra\DataProcessor\TickDataTransformers;

use App\Domain\DataProcessor\TickDataTransformers\TransformerInterface;

class ChronoSortTransformer implements TransformerInterface
{
    private ?TransformerInterface $nextTransformer = null;

    public function transform(\ArrayIterator $tickData): \ArrayIterator
    {
        $tickData->rewind();
        $tickData->uasort(
            function ($a, $b) {
                if ($a[0] === $b[0]) {
                    return 0;
                }

                return ($a[0] < $b[0]) ? -1 : 1;
            }
        );
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
