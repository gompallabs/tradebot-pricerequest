<?php

declare(strict_types=1);

namespace App\Infra\DataProcessor\TickDataTransformers;

use App\Domain\DataProcessor\TickDataTransformers\TransformerInterface;
use App\Domain\TimeFormat;
use App\Domain\TimeFormatTools;

class TickToBucketTransformer implements TransformerInterface
{
    private ?TransformerInterface $nextTransformer = null;

    public function __construct(
        private readonly TimeFormat $timeFormat,
        private readonly int $timeFrame
    ) {
    }

    /**
     * Read entire dataset only once.
     */
    public function transform(\ArrayIterator $tickData): \ArrayIterator
    {
        $start = $tickData->offsetGet(0);
        $end = $tickData->offsetGet($tickData->count() - 1);
        $tsScale = TimeFormatTools::scale(
            start: $start[0],
            end: $end[0],
            timeFormat: $this->timeFormat,
            stepSize: $this->timeFrame
        );

        $ohclv = new \ArrayIterator();
        foreach ($tsScale as $timeStep) {
            $bucket = new \ArrayIterator();
            while ($tickData->valid() && $tickData->current()[0] < $timeStep + 1) {
                $bucket->append($tickData->current());
                $tickData->next();
            }
            if ($bucket->count() > 0) {
                $result = $this->nextTransformer->transform($bucket);
                $ohclv->append($result->current()); // subsequent handlers return one result per bucket
            }
        }

        return $ohclv;
    }

    public function setNext(TransformerInterface $transformer): TransformerInterface
    {
        $this->nextTransformer = $transformer;

        return $transformer;
    }
}
