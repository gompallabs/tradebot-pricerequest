<?php

declare(strict_types=1);

namespace App\Domain\DataProcessor;

use App\Domain\Source\Source;
use App\Domain\TickData;

/**
 * Handles chained handlers.
 * 1. Analyze a payload sample. If true, is able to process, else throws Exception.
 * 2. Transform TickData to OHLCV (one or multiple series) with a target timescale.
 */
interface DataProcessor
{
    public function analyzeSample(array $sample, Source $source): bool;

    public function process(array $payload, bool $splitData = false): TickData;
}
