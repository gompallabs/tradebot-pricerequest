<?php

declare(strict_types=1);

namespace App\Domain\Store\Adapter\RedisTimeSeries\Vo;

use App\Domain\Store\Adapter\RedisTimeSeries\Exception\InvalidAggregationException;

final class SampleAggregationRule
{
    public const AGG_AVG = 'AVG';
    public const AGG_SUM = 'SUM';
    public const AGG_MIN = 'MIN';
    public const AGG_MAX = 'MAX';
    public const AGG_RANGE = 'RANGE';
    public const AGG_COUNT = 'COUNT';
    public const AGG_FIRST = 'FIRST';
    public const AGG_LAST = 'LAST';
    public const AGG_STD_P = 'STD.P';
    public const AGG_STD_S = 'STD.S';
    public const AGG_VAR_P = 'VAR.P';
    public const AGG_VAR_S = 'VAR.S';

    private const AGGREGATIONS = [
        self::AGG_AVG,
        self::AGG_SUM,
        self::AGG_MIN,
        self::AGG_MAX,
        self::AGG_RANGE,
        self::AGG_COUNT,
        self::AGG_FIRST,
        self::AGG_LAST,
        self::AGG_STD_P,
        self::AGG_STD_S,
        self::AGG_VAR_P,
        self::AGG_VAR_S,
    ];

    /** @var string */
    private $type;

    /**
     * bucketDuration is duration of each bucket, in milliseconds.
     */
    private int $timeBucketMs;

    /**
     * @throws InvalidAggregationException
     */
    public function __construct(string $type, int|string $timeBucketMs)
    {
        $type = strtoupper($type);
        if (!in_array($type, self::AGGREGATIONS, true)) {
            throw new InvalidAggregationException(sprintf('Aggregation %s is not valid', $type));
        }
        $this->type = $type;
        $this->timeBucketMs = $timeBucketMs;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTimeBucketMs(): int
    {
        return $this->timeBucketMs;
    }
}
