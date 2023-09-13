<?php

declare(strict_types=1);

namespace App\Tests\Functionnal\Prototype\TsSampling\Domain;

use App\Domain\Store\Adapter\RedisTimeSeries\Sample\RawSample;

/**
 * @deprecated
 */
final class SampleSeries implements \IteratorAggregate
{
    private string $name;
    private string $datapoint;
    private array $samples;

    public function __construct(string $name, string $datapoint)
    {
        $this->name = $name;
        $this->datapoint = $datapoint;
        $this->samples = [];
    }

    public function addSample(RawSample $sample): void
    {
        $this->samples[] = $sample;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDatapoint(): string
    {
        return $this->datapoint;
    }

    public function getTsName(): string
    {
        return $this->getName().$this->getDatapoint();
    }

    public function count(): int
    {
        return count($this->samples);
    }

    public function getIterator(): \Traversable
    {
        yield from $this->samples;
    }

    public function getSamples(): array
    {
        return $this->samples;
    }
}
