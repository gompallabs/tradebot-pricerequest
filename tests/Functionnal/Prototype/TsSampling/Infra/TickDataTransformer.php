<?php

declare(strict_types=1);

namespace App\Tests\Functionnal\Prototype\TsSampling\Infra;

use App\Domain\PriceOhlcv;
use App\Domain\Store\Adapter\RedisTimeSeries\Sample\RawSampleWithLabels;
use App\Domain\Store\Adapter\RedisTimeSeries\Vo\SampleLabel;
use App\Domain\TimeFormat;
use App\Tests\Functionnal\Prototype\TsSampling\Domain\SampleSeries;
use App\Tests\Functionnal\Prototype\TsSampling\Domain\SplitSeries;
use App\Tests\Functionnal\Prototype\TsSampling\Domain\TickData as TickDataInterface;
use App\Tests\Functionnal\Prototype\TsSampling\Domain\TickDataTransformer as TickDataTransformerInterface;
use Assert\Assert;

/**
 * TickData needs mandatory columns: time, size, side and price, and data must be packed in an \ArrayIterator.
 * TsScale must be expressed in the same scale as the series :).
 */
/**
 * @deprecated
 */
final class TickDataTransformer implements TickDataTransformerInterface
{
    /**
     * We iterate only once over the entire tickData
     * It's possible given the timestamps are already in chronological order.
     */
    public function resample(TickDataInterface $tickData, TsScale $tsScale): \ArrayIterator
    {
        $candles = new \ArrayIterator();
        $timeField = $tickData->getTimeFieldName();
        $timeFormat = $tickData->getTimeFormat();

        $tickData = $tickData->getIterator();
        $tickData->rewind();

        foreach ($tsScale->toArrayIterator($timeFormat) as $step) {
            $count = 0;
            $ohlcv = null;

            // tickData with timestamp between $step and $step + 1
            while ($tickData->valid() && ((float) $tickData->current()[$timeField]) < $step + 1) {
                $tick = $tickData->current();
                $ts = (float) $tick[$timeField];

                // first tick init ohlcv
                if ($count === 0) {
                    $ohlcv = $this->initOhlcv($tick, $step);
                }
                if ($ts >= $step && $ts < $step + 1) {
                    $ohlcv->addTick($tick);
                }

                $tickData->next();
                ++$count;
            }

            if ($ohlcv !== null) {
                $candles->append($ohlcv);
            }
        }

        return $candles;
    }

    public function splitResample(
        TickDataInterface $tickData,
        TsScale $tsScale,
        string $key,
        ?array $datapoints = ['open', 'high', 'low', 'close', 'buyVolume', 'sellVolume']
    ): SplitSeries {
        $open = new SampleSeries(name: $key, datapoint: 'open');
        $high = new SampleSeries(name: $key, datapoint: 'high');
        $low = new SampleSeries(name: $key, datapoint: 'low');
        $close = new SampleSeries(name: $key, datapoint: 'close');
        $buyVolume = new SampleSeries(name: $key, datapoint: 'buyVolume');
        $sellVolume = new SampleSeries(name: $key, datapoint: 'sellVolume');

        $tickData->rewind();
        foreach ($tsScale->toArray() as $step) {
            $count = 0;
            $ohlcv = null;

            // tickData with timestamp between $step and $step + 1
            while ($tickData->valid() && ((float) $tickData->current()['timestamp']) < $step + 1) {
                $tick = $tickData->current();
                $ts = (float) $tick['timestamp'];

                // first tick init ohlcv
                if ($count === 0) {
                    $ohlcv = $this->initOhlcv($tick, $step);
                }
                if ($ts >= $step && $ts < $step + 1) {
                    $ohlcv->addTick($tick);
                }

                $tickData->next();
                ++$count;
            }

            $tsms = $step * 1000;

            // https://redis.io/commands/ts.mrange/#examples
            if ($ohlcv instanceof PriceOhlcv) {
                $open->addSample(RawSampleWithLabels::createFromTimestampAndLabels(
                    key: $key.'open',
                    value: $ohlcv->getOpen(),
                    tsms: $tsms,
                    labels: [new SampleLabel(key: 'asset', value: $key), new SampleLabel('dp', 'open')]
                ));
                $high->addSample(RawSampleWithLabels::createFromTimestampAndLabels(
                    key: $key.'high',
                    value: $ohlcv->getHigh(),
                    tsms: $tsms,
                    labels: [new SampleLabel(key: 'asset', value: $key), new SampleLabel('dp', 'high')]
                ));
                $low->addSample(RawSampleWithLabels::createFromTimestampAndLabels(
                    key: $key.'low',
                    value: $ohlcv->getLow(),
                    tsms: $tsms,
                    labels: [new SampleLabel(key: 'asset', value: $key), new SampleLabel('dp', 'low')]
                ));
                $close->addSample(RawSampleWithLabels::createFromTimestampAndLabels(
                    key: $key.'close',
                    value: $ohlcv->getClose(),
                    tsms: $tsms,
                    labels: [new SampleLabel(key: 'asset', value: $key), new SampleLabel('dp', 'close')]
                ));
                $buyVolume->addSample(RawSampleWithLabels::createFromTimestampAndLabels(
                    key: $key.'buyVolume',
                    value: $ohlcv->getBuyVolume(),
                    tsms: $tsms,
                    labels: [new SampleLabel(key: 'asset', value: $key), new SampleLabel('dp', 'buy_volume')]
                ));
                $sellVolume->addSample(RawSampleWithLabels::createFromTimestampAndLabels(
                    key: $key.'sellVolume',
                    value: $ohlcv->getSellVolume(),
                    tsms: $tsms,
                    labels: [new SampleLabel(key: 'asset', value: $key), new SampleLabel('dp', 'sell_volume')]
                ));
            }
        }

        return new SplitSeries($open, $high, $low, $close, $buyVolume, $sellVolume);
    }

    public function initOhlcv(array $tick, int $time): PriceOhlcv
    {
        $tsms = $time * 1000;
        $open = (float) $tick['price'];

        return new PriceOhlcv(tsms: $tsms, open: $open);
    }

    /**
     * check if the mandatory keys are present: time, size, side and price.
     */
    public function checkColumns(TickDataInterface $tickData): bool
    {
        $firstSample = $tickData->current();
        Assert::Lazy()
            ->that($firstSample, 'Sample tick')
            ->isArray('must be an array')
            ->keyExists('timestamp', 'timestamp key must exist')
            ->keyExists('size', 'size key must exist')
            ->keyExists('price', 'price key must exist')
            ->keyExists('side', 'side key must exist to find if the tick is buy or a sell trade')
            ->verifyNow();

        return true;
    }

    public function getTimeScale(TickDataInterface $tickData, int $stepSize, TimeFormat $timeFormat): TsScale
    {
        [$startDt, $endDt] = $this->getMinMaxTime($tickData);
        $startSecond = $this->convertToSecond($startDt, $timeFormat);
        $endSecond = $this->convertToSecond($endDt, $timeFormat);

        return new TsScale($startSecond, $endSecond, $stepSize);
    }

    private function convertToSecond(int|float $timestamp, TimeFormat $timeFormat): int
    {
        if ($timeFormat === TimeFormat::Seconds) {
            return (int) $timestamp;
        }
        if ($timeFormat === TimeFormat::DotMilliseconds) {
            return (int) floor($timestamp);
        }

        throw new \LogicException('Missing format in '.__CLASS__);
    }
}
