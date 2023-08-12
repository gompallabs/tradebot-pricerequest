<?php

declare(strict_types=1);

namespace App\Domain\Store\Adapter\RedisTimeSeries\Sample;

use App\Domain\Store\Adapter\RedisTimeSeries\Vo\SampleLabel;

final class RawSampleWithLabels extends RawSample
{
    /** @var array<SampleLabel> */
    private array $labels;

    /**
     * RawSampleWithLabels constructor.
     */
    public function __construct(string $key, float|string $value, array $labels = [], int $tsms = null)
    {
        parent::__construct(key: $key, value: $value, tsms: $tsms);
        $this->labels = $labels;
    }

    public static function createFromTimestampAndLabels(
        string $key,
        float|string $value,
        int $tsms,
        array $labels = []
    ): RawSampleWithLabels {
        return new self(key: $key, value: $value, labels: $labels, tsms: $tsms);
    }

    public function getLabels(): array
    {
        return $this->labels;
    }
}
