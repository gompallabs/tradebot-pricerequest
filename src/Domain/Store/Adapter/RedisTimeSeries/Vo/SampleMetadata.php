<?php

declare(strict_types=1);

namespace App\Domain\Store\Adapter\RedisTimeSeries\Vo;

/**
 * Basic meta on a Sample.
 */
final class SampleMetadata
{
    private float $lastTimestamp;

    private int $retentionTime;

    private int $chunkCount;

    private int $maxRawsPerChunk;

    /** @var array<SampleLabel> */
    private array $labels;

    private string|null $sourceKey;

    /** @var array<SampleAggregationRule> */
    private array $rules;

    private ?int $totalSamples = null;

    public function __construct(
        float $lastTimestamp,
        int $retentionTime = 0,
        int $chunkCount = 0,
        int $maxRawsPerChunk = 0,
        array $labels = [],
        array $rules = [],
        string $sourceKey = null,
    ) {
        $this->lastTimestamp = $lastTimestamp;
        $this->retentionTime = $retentionTime;
        $this->chunkCount = $chunkCount;
        $this->maxRawsPerChunk = $maxRawsPerChunk;
        $this->labels = $labels;
        $this->rules = $rules;
        $this->sourceKey = $sourceKey;
    }

    /**
     * @param SampleLabel[]           $labels
     * @param SampleAggregationRule[] $rules
     *
     * @return static
     */
    public static function fromRedis(
        int $lastTimestamp,
        int $retentionTime = 0,
        int $chunkCount = 0,
        int $maxRawsPerChunk = 0,
        array $labels = [],
        array $rules = [],
        string $sourceKey = null
    ): self {
        return new self(
            lastTimestamp: $lastTimestamp,
            retentionTime: $retentionTime,
            chunkCount: $chunkCount,
            maxRawsPerChunk: $maxRawsPerChunk,
            labels: $labels,
            rules: $rules,
            sourceKey: $sourceKey
        );
    }

    public function getTotalSamples(): ?int
    {
        return $this->totalSamples;
    }

    public function setTotalSamples(?int $totalSamples): void
    {
        $this->totalSamples = $totalSamples;
    }

    public function getLastTimestamp(): \DateTimeInterface
    {
        $timestamp = (int) floor($this->lastTimestamp / 1000);
        $datetime = new \DateTime();

        return $datetime->setTimestamp($timestamp);
    }

    public function getRetentionTime(): int
    {
        return $this->retentionTime;
    }

    public function getChunkCount(): int
    {
        return $this->chunkCount;
    }

    public function getMaxRawsPerChunk(): int
    {
        return $this->maxRawsPerChunk;
    }

    public function getLabels(): array
    {
        return $this->labels;
    }

    public function getSourceKey(): ?string
    {
        return $this->sourceKey;
    }

    public function getRules(): array
    {
        return $this->rules;
    }
}
