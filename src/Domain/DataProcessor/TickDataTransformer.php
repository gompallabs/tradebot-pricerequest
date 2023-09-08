<?php

declare(strict_types=1);

namespace App\Domain\DataProcessor;

use App\Domain\TickData;

/**
 * Transforms to OHLCV series.
 * Aggregates per seconds by default.
 */
interface TickDataTransformer
{
    public function transform(
        array $payload,
        int $timeFrame = 1,
        bool $splitSeries = false
    ): TickData;
}
