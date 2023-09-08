<?php

namespace App\Tests\Functionnal\Prototype\TsSampling\Domain;

use App\Domain\TimeFormat;
use App\Tests\Functionnal\Prototype\TsSampling\Domain\TickData as TickDataInterface;
use App\Tests\Functionnal\Prototype\TsSampling\Infra\TsScale;

/**
 * @deprecated
 */
interface TickDataTransformer
{
    /**
     * transforms tickData to OHLCV series by aggregation on a given ts scale in seconds.
     */
    public function resample(TickDataInterface $tickData, TsScale $tsScale): \ArrayIterator;

    public function splitResample(
        TickData $tickData,
        TsScale $tsScale,
        string $key,
        ?array $datapoints = ['open', 'high', 'low', 'close', 'buyVolume', 'sellVolume']
    ): SplitSeries;

    public function checkColumns(TickData $tickData): bool;

    /**
     * returns array of ts scaled time (ex. per second, minute, hour ...).
     * stepSize must be expressed in seconds.
     */
    public function getTimeScale(TickData $tickData, int $stepSize, TimeFormat $timeFormat): TsScale;
}
