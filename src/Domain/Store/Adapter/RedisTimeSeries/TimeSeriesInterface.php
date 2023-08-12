<?php

declare(strict_types=1);

namespace App\Domain\Store\Adapter\RedisTimeSeries;

use App\Domain\Store\Adapter\RedisTimeSeries\Sample\RawSample;
use App\Domain\Store\Adapter\RedisTimeSeries\Vo\SampleAggregationRule;
use App\Domain\Store\Adapter\RedisTimeSeries\Vo\SampleFilter;
use App\Domain\Store\Adapter\RedisTimeSeries\Vo\SampleMetadata;
use App\Domain\TsSampling\SampleSeries;

/**
 * TimeSeries is a adapter on RedisTimeSeries module.
 */
interface TimeSeriesInterface
{
    public function info(string $key): SampleMetadata|bool;

    public function create(string $key, int $retentionMs = null, array $labels = []): void;

    public function alter(string $key, int $retentionMs = null, array $labels = []): void;

    public function add(
        RawSample $rawSample,
        array $labels = [],
        int $retentionMs = null,
        bool $uncompressed = null,
        int $chunkSize = null,
        string $duplicatePolicy = null
    ): RawSample;

    public function addMany(array $rawSamples): array;

    public function getLastRaw(string $key): RawSample|array;

    public function getLastRaws(SampleFilter $filter): array;

    public function getKeysByFilter(SampleFilter $filter);

    public function range(string $key, int $from = null, int $to = null, int $count = null, SampleAggregationRule $rule = null, bool $reverse = false);

    public function multiRange(SampleFilter $filter, int $from = null, int $to = null, int $count = null, SampleAggregationRule $rule = null, bool $reverse = false);

    public function multiRangeWithLabels(SampleFilter $filter, int $from = null, int $to = null, int $count = null, SampleAggregationRule $rule = null, bool $reverse = false);

    public function delTs(string $key);

    public function pushSeries(SampleSeries $series);

    public function deleteSeries(SampleSeries $series): void;
}
