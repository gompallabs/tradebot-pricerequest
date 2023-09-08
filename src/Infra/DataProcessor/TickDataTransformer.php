<?php

declare(strict_types=1);

namespace App\Infra\DataProcessor;

use App\Domain\DataProcessor\SampleDataMapper;
use App\Domain\DataProcessor\TickDataTransformer as TickDataTransformerInterface;
use App\Domain\TickData;
use App\Domain\TimeFormat;
use App\Infra\DataProcessor\TickDataTransformers\ChronoSortTransformer;
use App\Infra\DataProcessor\TickDataTransformers\ColumnFilterTransformer;
use App\Infra\DataProcessor\TickDataTransformers\OhlcTransformer;
use App\Infra\DataProcessor\TickDataTransformers\TickToBucketTransformer;
use App\Infra\DataProcessor\TickDataTransformers\VolumeTransformer;

class TickDataTransformer implements TickDataTransformerInterface
{
    private SampleDataMapper $dataMapper;
    private TimeFormat $timeFormat;

    public function __construct(
        SampleDataMapper $dataMapper,
        TimeFormat $timeFormat
    ) {
        $this->dataMapper = $dataMapper;
        $this->timeFormat = $timeFormat;
    }

    public function transform(array $payload, int $timeFrame = 1, bool $splitSeries = false): TickData
    {
        $transformer = new ColumnFilterTransformer($this->dataMapper);
        $transformer
            ->setNext(new ChronoSortTransformer())
            ->setNext(new TickToBucketTransformer(
                timeFormat: $this->timeFormat,
                timeFrame: $timeFrame
            ))
            ->setNext(new OhlcTransformer())
            ->setNext(new VolumeTransformer());

        return new TickData($transformer->transform(new \ArrayIterator($payload)));
    }
}
