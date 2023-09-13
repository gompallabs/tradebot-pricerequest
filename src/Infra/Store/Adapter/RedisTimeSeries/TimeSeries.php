<?php

declare(strict_types=1);

namespace App\Infra\Store\Adapter\RedisTimeSeries;

use App\Domain\Store\Adapter\RedisTimeSeries\Exception\InvalidDuplicatePolicyException;
use App\Domain\Store\Adapter\RedisTimeSeries\Sample\RawSample;
use App\Domain\Store\Adapter\RedisTimeSeries\Sample\RawSampleWithLabels;
use App\Domain\Store\Adapter\RedisTimeSeries\TimeSeriesInterface;
use App\Domain\Store\Adapter\RedisTimeSeries\Vo\ConnectionParams;
use App\Domain\Store\Adapter\RedisTimeSeries\Vo\SampleAggregationRule;
use App\Domain\Store\Adapter\RedisTimeSeries\Vo\SampleDuplicatePolicyList;
use App\Domain\Store\Adapter\RedisTimeSeries\Vo\SampleFilter;
use App\Domain\Store\Adapter\RedisTimeSeries\Vo\SampleLabel;
use App\Domain\Store\Adapter\RedisTimeSeries\Vo\SampleMetadata;
use App\Tests\Functionnal\Prototype\TsSampling\SampleSeries;

/**
 * Duplication policies are used when a TimeSerie already contains the same timestamp for a given key
 * Labels are metadata and are not meant to store data even if they could. For ex (e.g., room = 3; sensorType = ‘xyz’)
 * Labels are indexed, i.e. they can be used to filter / aggregate data of same labels.
 * Compactions are not yet supported by this lib but leverage the aggregate feature to compact information. For ex
 * retains the 10 minutes average in replacement of the 10 minutes second per second data.
 */
final class TimeSeries extends Client implements TimeSeriesInterface
{
    public function __construct(\Redis $redis, ConnectionParams $connectionParams)
    {
        parent::__construct($redis, $connectionParams);
    }

    /**
     * Creates a timeserie.
     */
    public function create(
        string $key,
        int $retentionMs = null,
        array $labels = [],
        bool $uncompressed = false,
        int $chunkSize = null,
        string $duplicatePolicy = null
    ): void {
        $params = [];

        if ($uncompressed === true) {
            $params[] = 'UNCOMPRESSED';
        }

        if ($chunkSize !== null) {
            $params[] = 'CHUNK_SIZE';
            $params[] = (string) $chunkSize;
        }

        if ($duplicatePolicy !== null) {
            if (!$policy = SampleDuplicatePolicyList::tryFrom($duplicatePolicy)) {
                throw new InvalidDuplicatePolicyException(sprintf('Duplicate policy %s is invalid', $duplicatePolicy));
            }
            $params[] = 'DUPLICATE_POLICY';
            $params[] = $policy->value;
        }

        $this->executeCommand(array_merge(
            ['TS.CREATE', $key],
            $this->getRetentionParams($retentionMs),
            $params,
            $this->getLabelsParams(...$labels)
        ));
    }

    /**
     * Modifies an existing timeserie.
     */
    public function alter(string $key, int $retentionMs = null, array $labels = []): void
    {
        $this->executeCommand(array_merge(
            ['TS.ALTER', $key],
            $this->getRetentionParams($retentionMs),
            $this->getLabelsParams(...$labels)
        ));
    }

    /**
     * Adds a raw.
     */
    public function add(
        RawSample $rawSample,
        array $labels = [],
        int $retentionMs = null,
        bool $uncompressed = null,
        int $chunkSize = null,
        string $duplicatePolicy = null
    ): RawSample {
        $params = [];

        if ($uncompressed === true) {
            $params[] = 'UNCOMPRESSED';
        }

        if ($chunkSize !== null) {
            $params[] = 'CHUNK_SIZE';
            $params[] = (string) $chunkSize;
        }

        if ($duplicatePolicy !== null) {
            if (!$policy = SampleDuplicatePolicyList::tryFrom($duplicatePolicy)) {
                throw new InvalidDuplicatePolicyException(sprintf('Duplicate policy %s is invalid', $duplicatePolicy));
            }

            $params[] = 'ON_DUPLICATE';
            $params[] = $policy->value;
        }

        $timestamp = $this->executeCommand(array_merge(
            ['TS.ADD'],
            $rawSample->toRedisParams(),
            $params,
            $this->getRetentionParams($retentionMs),
            $this->getLabelsParams(...$labels)
        ));

        return RawSample::createFromTimestamp($rawSample->getKey(), $rawSample->getValue(), $timestamp);
    }

    /**
     * Adds many raws.
     */
    public function addMany(array $raws): array
    {
        if (empty($raws)) {
            return [];
        }
        $params = ['TS.MADD'];
        foreach ($raws as $rawSample) {
            $rawParams = $rawSample->toRedisParams();
            foreach ($rawParams as $rawParam) {
                $params[] = $rawParam;
            }
        }

        /** @var array<int> $timestamps */
        $timestamps = $this->executeCommand($params);
        $count = count($timestamps);
        $results = [];
        for ($i = 0; $i < $count; ++$i) {
            $results[] = RawSample::createFromTimestamp(
                $raws[$i]->getKey(),
                $raws[$i]->getValue(),
                $timestamps[$i]
            );
        }

        return $results;
    }

    /**
     * Adds a raw.
     */
    public function addSampleWithLabels(
        RawSampleWithLabels $rawSampleWithLabels,
        int $retentionMs = null,
        bool $uncompressed = null,
        int $chunkSize = null,
        string $duplicatePolicy = 'LAST'
    ): RawSampleWithLabels {
        $params = [];

        if ($uncompressed === true) {
            $params[] = 'UNCOMPRESSED';
        }

        if ($chunkSize !== null) {
            $params[] = 'CHUNK_SIZE';
            $params[] = (string) $chunkSize;
        }

        if ($duplicatePolicy !== null) {
            if (!$policy = SampleDuplicatePolicyList::tryFrom($duplicatePolicy)) {
                throw new InvalidDuplicatePolicyException(sprintf('Duplicate policy %s is invalid', $duplicatePolicy));
            }

            $params[] = 'ON_DUPLICATE';
            $params[] = $policy->value;
        }
        $command = array_merge(
            ['TS.ADD'],
            $rawSampleWithLabels->toRedisParams(),
            $params,
            $this->getRetentionParams($retentionMs),
            $this->getLabelsParams(...$rawSampleWithLabels->getLabels())
        );

        $timestamp = $this->executeCommand($command);

        return RawSampleWithLabels::createFromTimestampAndLabels($rawSampleWithLabels->getKey(), $rawSampleWithLabels->getValue(), $timestamp, $rawSampleWithLabels->getLabels());
    }

    public function deleteSeries(SampleSeries $series): void
    {
        /* @var SampleSeries $serie */
        $this->delTs($series->getTsName());
    }

    /**
     * 10000000000 ms = 115.74 days.
     * Only to create new series with a bunch of samples.
     */
    public function pushSeries(SampleSeries $series, ?int $retentionMs = 10000000000): void
    {
        $info = $this->info($series->getTsName());
        if ($info !== false) {
            $this->deleteSeries($series);
        }

        $this->create(key: $series->getTsName(), retentionMs: $retentionMs, labels: [
            new SampleLabel('asset', $series->getName()),
            new SampleLabel('dp', $series->getDatapoint()),
        ], duplicatePolicy: SampleDuplicatePolicyList::BLOCK->value);

        foreach ($series->getIterator() as $sample) {
            $this->addSampleWithLabels($sample);
        }

        // duplicate policy default = BLOCK. This causes pb when dp already exists
        // should try a "addManyWithLabels" method // bulk insert

        //        if (empty($raws)) {
        //            return [];
        //        }
        //        $params = ['TS.MADD'];
        //        foreach ($raws as $rawSample) {
        //            $rawParams = $rawSample->toRedisParams();
        //            foreach ($rawParams as $rawParam) {
        //                $params[] = $rawParam;
        //            }
        //        }
        //
        //        /** @var array<int> $timestamps */
        //        $timestamps = $this->executeCommand($params);
        //        $count = count($timestamps);
        //        $results = [];
        //        for ($i = 0; $i < $count; ++$i) {
        //            $results[] = RawSample::createFromTimestamp(
        //                $raws[$i]->getKey(),
        //                $raws[$i]->getValue(),
        //                $timestamps[$i]
        //            );
        //        }
        //
        //        return $results;
    }

    /**
     * Creates an aggregation rule for a key (e.g. min, max, last, avg, stdp, etc.).
     */
    public function createRule(string $sourceKey, string $destKey, SampleAggregationRule $rule): void
    {
        $this->executeCommand(array_merge(
            ['TS.CREATERULE', $sourceKey, $destKey],
            $this->getAggregationParams($rule)
        ));
    }

    /**
     * Deletes an existing aggregation rule.
     */
    public function deleteRule(string $sourceKey, string $destKey): void
    {
        $this->executeCommand(['TS.DELETERULE', $sourceKey, $destKey]);
    }

    /**
     * Gets raws for a key, optionally aggregating them.
     */
    public function range(
        string $key,
        int $from = null,
        int $to = null,
        int $count = null,
        SampleAggregationRule $rule = null,
        bool $reverse = false
    ): array {
        $fromTs = $from ? (string) ($from) : '-';
        $toTs = $to ? (string) ($to) : '+';

        $command = $reverse ? 'TS.REVRANGE' : 'TS.RANGE';
        $params = [$command, $key, $fromTs, $toTs];
        if ($count !== null) {
            $params[] = 'COUNT';
            $params[] = (string) $count;
        }

        $command = array_merge($params, $this->getAggregationParams($rule));
        $rawResults = $this->executeCommand($command);
        $raws = [];
        if (!empty($rawResults)) {
            foreach ($rawResults as $rawResult) {
                $raws[] = RawSample::createFromTimestamp($key, (float) $rawResult[1], (int) $rawResult[0]);
            }
        }

        return $raws;
    }

    /**
     * Gets raws from multiple keys, searching by a given filter.
     */
    public function multiRange(
        SampleFilter $filter,
        int $from = null,
        int $to = null,
        int $count = null,
        SampleAggregationRule $rule = null,
        bool $reverse = false
    ): array {
        $results = $this->multiRangeRaw($filter, $from, $to, $count, $rule, $reverse);

        $raws = [];
        foreach ($results as $groupByKey) {
            $key = $groupByKey[0];
            foreach ($groupByKey[2] as $result) {
                $raws[] = RawSample::createFromTimestamp($key, (float) $result[1], (int) $result[0]);
            }
        }

        return $raws;
    }

    public function multiRangeWithLabels(
        SampleFilter $filter,
        int $from = null,
        int $to = null,
        int $count = null,
        SampleAggregationRule $rule = null,
        bool $reverse = false
    ): array {
        $results = $this->multiRangeRaw($filter, $from, $to, $count, $rule, $reverse, true);

        $raws = [];
        foreach ($results as $groupByKey) {
            $key = $groupByKey[0];
            $labels = [];
            foreach ($groupByKey[1] as $label) {
                $labels[] = new SampleLabel($label[0], $label[1]);
            }
            foreach ($groupByKey[2] as $result) {
                $raws[] = RawSampleWithLabels::createFromTimestampAndLabels(
                    $key,
                    (float) $result[1],
                    $result[0],
                    $labels
                );
            }
        }

        return $raws;
    }

    private function multiRangeRaw(
        SampleFilter $filter,
        int $from = null,
        int $to = null,
        int $count = null,
        SampleAggregationRule $rule = null,
        bool $reverse = false,
        bool $withLabels = false
    ): array {
        $fromTs = $from ? (string) ($from) : '-';
        $toTs = $to ? (string) ($to) : '+';

        $command = $reverse ? 'TS.MREVRANGE' : 'TS.MRANGE';
        $params = [$command, $fromTs, $toTs];

        if ($count !== null) {
            $params[] = 'COUNT';
            $params[] = (string) $count;
        }

        $params = array_merge($params, $this->getAggregationParams($rule));

        if ($withLabels) {
            $params[] = 'WITHLABELS';
        }

        $params = array_merge($params, ['FILTER'], $filter->toRedisParams());

        return $this->executeCommand($params);
    }

    /**
     * Gets the last raw for a key.
     */
    public function getLastRaw(string $key): RawSample|array
    {
        $result = $this->executeCommand(['TS.GET', $key]);
        if (count($result) === 0) {
            return [];
        }

        return RawSample::createFromTimestamp($key, (float) $result[1], (int) $result[0]);
    }

    /**
     * Gets the last raws for multiple keys using a filter.
     */
    public function getLastRaws(SampleFilter $filter): array
    {
        $results = $this->executeCommand(
            array_merge(['TS.MGET', 'FILTER'], $filter->toRedisParams())
        );
        $raws = [];
        foreach ($results as $result) {
            // most recent versions of TS.MGET return results in a nested array
            if (count($result) === 3) {
                $raws[] = RawSample::createFromTimestamp($result[0], (float) $result[2][1], (int) $result[2][0]);
            } else {
                $raws[] = RawSample::createFromTimestamp($result[0], (float) $result[3], (int) $result[2]);
            }
        }

        return $raws;
    }

    /**
     * Gets a key's metadata.
     */
    public function info(string $key): SampleMetadata|bool
    {
        $result = $this->executeCommand(['TS.INFO', $key]);
        if ($result === false) {
            return false;
        }

        $labels = [];
        $storedLabels = $result[19];
        if (is_array($storedLabels)) {
            foreach ($storedLabels as $strLabel) {
                $labels[] = new SampleLabel($strLabel[0], $strLabel[1]);
            }
        }

        $sourceKey = $result[21] === false ? null : $result[21];

        $rules = [];
        foreach ($result[23] as $rule) {
            $rules[$rule[0]] = new SampleAggregationRule($rule[2], $rule[1]);
        }

        $sample = SampleMetadata::fromRedis(
            lastTimestamp: $result[7],
            retentionTime: $result[9],
            chunkCount: $result[11],
            maxRawsPerChunk: $result[13],
            labels: $labels,
            rules: $rules,
            sourceKey: $sourceKey
        );

        if ($result[0] === 'totalSamples') {
            $sample->setTotalSamples($result[1]);
        }

        return $sample;
    }

    /**
     * Lists the keys matching a filter.
     */
    public function getKeysByFilter(SampleFilter $filter): array
    {
        return $this->executeCommand(
            array_merge(['TS.QUERYINDEX'], $filter->toRedisParams())
        );
    }

    private function getRetentionParams(int $retentionMs = null): array
    {
        if ($retentionMs === null) {
            return [];
        }

        return ['RETENTION', (string) $retentionMs];
    }

    private function getLabelsParams(SampleLabel ...$labels): array
    {
        $params = [];
        foreach ($labels as $label) {
            $params[] = $label->getKey();
            $params[] = $label->getValue();
        }

        if (empty($params)) {
            return [];
        }

        array_unshift($params, 'LABELS');

        return $params;
    }

    private function getAggregationParams(SampleAggregationRule $rule = null): array
    {
        if ($rule === null) {
            return [];
        }

        //        return ['AGGREGATION', $rule->getType(), (string) $rule->getTimeBucketMs()];
        return ['AGGREGATION', $rule->getType(), (string) $rule->getTimeBucketMs()];
    }

    /**
     * Increments a raw by the amount given in the passed raw.
     */
    public function incrementBy(RawSample $raw, int $resetMs = null, int $retentionMs = null, array $labels = []): void
    {
        $this->incrementOrDecrementBy('TS.INCRBY', $raw, $resetMs, $retentionMs, $labels);
    }

    /**
     * Decrements a raw by the amount given in the passed raw.
     */
    public function decrementBy(RawSample $raw, int $resetMs = null, int $retentionMs = null, array $labels = []): void
    {
        $this->incrementOrDecrementBy('TS.DECRBY', $raw, $resetMs, $retentionMs, $labels);
    }

    private function incrementOrDecrementBy(
        string $op,
        RawSample $rawSample,
        int $resetMs = null,
        int $retentionMs = null,
        array $labels = []
    ): void {
        $params = [$op, $rawSample->getKey(), (string) $rawSample->getValue()];
        if ($resetMs !== null) {
            $params[] = 'RESET';
            $params[] = (string) $resetMs;
        }
        if ($rawSample->getDateTime() !== null) {
            $params[] = 'TIMESTAMP';
            $params[] = $rawSample->getTimestampWithMs();
        }
        $params = array_merge(
            $params,
            $this->getRetentionParams($retentionMs),
            $this->getLabelsParams(...$labels)
        );
        $this->executeCommand($params);
    }

    public function unlink(string $key)
    {
        $this->redis->get($key);
        if ($this->redis->get($key) !== false) {
            $this->redis->unlink($key);
        }
    }

    public function delTs(string $key)
    {
        $this->executeCommand(['DEL', $key]);
    }
}
