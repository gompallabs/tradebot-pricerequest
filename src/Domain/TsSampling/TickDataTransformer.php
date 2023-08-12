<?php

namespace App\Domain\TsSampling;

interface TickDataTransformer
{
    /**
     * transforms tickData to OHLCV series by aggregation on a given ts scale.
     */
    public function resample(\ArrayIterator $tickData, TsScale $tsScale): \ArrayIterator;

    public function splitResample(
        \ArrayIterator $tickData,
        TsScale $tsScale,
        string $key,
        ?array $datapoints = ['open', 'high', 'low', 'close', 'buyVolume', 'sellVolume']
    ): SplitSeries;

    /**
     * check if the mandatory keys are present: time, size, side and price.
     */
    public function checkColumns(\ArrayIterator $tickData): bool;

    /**
     * returns the time format of time given as a number.
     * pass only one string :-).
     */
    public static function guessTimeStampFormat(string $timestamp): TimeFormat;

    /**
     * returns array of ts scaled time (ex. per second, minute, hour ...).
     * stepSize must be expressed in seconds.
     */
    public function getTimeScale(\ArrayIterator $tickData, int $stepSize, TimeFormat $timeFormat): TsScale;

    public function chronoSort(\ArrayIterator $tickData): array;
}
