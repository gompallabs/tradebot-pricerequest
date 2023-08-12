<?php

declare(strict_types=1);

namespace App\Domain\TsSampling;

class SplitSeries
{
    private SampleSeries $open;
    private SampleSeries $high;
    private SampleSeries $low;
    private SampleSeries $close;
    private SampleSeries $buyVolume;
    private SampleSeries $sellVolume;

    public function __construct(
        SampleSeries $open,
        SampleSeries $high,
        SampleSeries $low,
        SampleSeries $close,
        SampleSeries $buyVolume,
        SampleSeries $sellVolume
    ) {
        $this->open = $open;
        $this->high = $high;
        $this->low = $low;
        $this->close = $close;
        $this->buyVolume = $buyVolume;
        $this->sellVolume = $sellVolume;
    }

    public function getOpen(): SampleSeries
    {
        return $this->open;
    }

    public function getHigh(): SampleSeries
    {
        return $this->high;
    }

    public function getLow(): SampleSeries
    {
        return $this->low;
    }

    public function getClose(): SampleSeries
    {
        return $this->close;
    }

    public function getBuyVolume(): SampleSeries
    {
        return $this->buyVolume;
    }

    public function getSellVolume(): SampleSeries
    {
        return $this->sellVolume;
    }
}
