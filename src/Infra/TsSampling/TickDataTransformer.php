<?php

declare(strict_types=1);

namespace App\Infra\TsSampling;

use App\Domain\PriceOhlcv;
use App\Domain\Store\Adapter\RedisTimeSeries\Sample\RawSampleWithLabels;
use App\Domain\Store\Adapter\RedisTimeSeries\Vo\SampleLabel;
use App\Domain\TsSampling\SampleSeries;
use App\Domain\TsSampling\SplitSeries;
use App\Domain\TsSampling\TickDataTransformer as TickDataTransformerInterface;
use App\Domain\TsSampling\TimeFormat;
use App\Domain\TsSampling\TsScale;
use Assert\Assert;

/**
 * TickData needs mandatory columns: time, size, side and price, and data must be packed in an \ArrayIterator.
 * TsScale must be expressed in the same scale as the series :).
 */
final class TickDataTransformer implements TickDataTransformerInterface
{
    /**
     * We iterate only once over the entire tickData
     * It's possible given the timestamps are already in chronological order.
     */
    public function resample(\ArrayIterator $tickData, TsScale $tsScale): \ArrayIterator
    {
        $candles = new \ArrayIterator();
        $tickData->rewind();

        foreach ($tsScale->toArray() as $step) {
            $count = 0;
            $ohlcv = null;

            // tickData with timestamp between $step and $step + 1
            while ($tickData->valid() && ((float) $tickData->current()['timestamp']) < $step + 1) {
                $tick = $tickData->current();
                $ts = (float) $tick['timestamp'];

                // first tick init ohlcv
                if (0 === $count) {
                    $ohlcv = $this->initOhlcv($tick, $step);
                }
                if ($ts >= $step && $ts < $step + 1) {
                    $ohlcv->addTick($tick);
                }

                $tickData->next();
                ++$count;
            }
            $candles[] = $ohlcv;
        }

        $candles->rewind();

        return $candles;
    }

    public function splitResample(
        \ArrayIterator $tickData,
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
                if (0 === $count) {
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
                    key: $key.'open', value: $ohlcv->getOpen(), tsms: $tsms, labels: [new SampleLabel(key: 'asset', value: $key), new SampleLabel('dp', 'open')])
                );
                $high->addSample(RawSampleWithLabels::createFromTimestampAndLabels(
                    key: $key.'high', value: $ohlcv->getHigh(), tsms: $tsms, labels: [new SampleLabel(key: 'asset', value: $key), new SampleLabel('dp', 'high')])
                );
                $low->addSample(RawSampleWithLabels::createFromTimestampAndLabels(
                    key: $key.'low', value: $ohlcv->getLow(), tsms: $tsms, labels: [new SampleLabel(key: 'asset', value: $key), new SampleLabel('dp', 'low')])
                );
                $close->addSample(RawSampleWithLabels::createFromTimestampAndLabels(
                    key: $key.'close', value: $ohlcv->getClose(), tsms: $tsms, labels: [new SampleLabel(key: 'asset', value: $key), new SampleLabel('dp', 'close')])
                );
                $buyVolume->addSample(RawSampleWithLabels::createFromTimestampAndLabels(
                    key: $key.'buyVolume', value: $ohlcv->getBuyVolume(), tsms: $tsms, labels: [new SampleLabel(key: 'asset', value: $key), new SampleLabel('dp', 'buy_volume')])
                );
                $sellVolume->addSample(RawSampleWithLabels::createFromTimestampAndLabels(
                    key: $key.'sellVolume', value: $ohlcv->getSellVolume(), tsms: $tsms, labels: [new SampleLabel(key: 'asset', value: $key), new SampleLabel('dp', 'sell_volume')])
                );
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
    public function checkColumns(\ArrayIterator $tickData): bool
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

    public static function guessTimeStampFormat(string $timestamp): TimeFormat
    {
        $currentTime = time();
        $currentTimeLen = strlen((string) $currentTime);

        $strTime = (string) floor((float) $timestamp);
        $timeStampLen = strlen($strTime);

        if (str_contains($timestamp, '.') && $timeStampLen === $currentTimeLen) {
            return TimeFormat::DotMilliseconds;
        }

        if ($currentTimeLen === $timeStampLen) {
            return TimeFormat::Seconds;
        }

        throw new \Exception('Missing case at '.__CLASS__);
    }

    public function getTimeScale(\ArrayIterator $tickData, int $stepSize, TimeFormat $timeFormat): TsScale
    {
        [$startDt, $endDt] = $this->getMinMaxTime($tickData);
        $startSecond = $this->convertToSecond($startDt, $timeFormat);
        $endSecond = $this->convertToSecond($endDt, $timeFormat);

        return new TsScale($startSecond, $endSecond, $stepSize);
    }

    private function convertToSecond(int|float $timestamp, TimeFormat $timeFormat): int
    {
        if (TimeFormat::Seconds === $timeFormat) {
            return (int) $timestamp;
        }
        if (TimeFormat::DotMilliseconds === $timeFormat) {
            return (int) floor($timestamp);
        }

        throw new \LogicException('Missing format in '.__CLASS__);
    }

    private function getMinMaxTime(\ArrayIterator $tickData): array
    {
        $startTick = $tickData[0];
        $startDt = (float) $startTick['timestamp'];

        $endTick = $tickData->offsetGet($tickData->count() - 1);
        $endDt = (float) $endTick['timestamp'];

        return [$startDt, $endDt];
    }

    /** This method can consume a lot of memory */
    public function chronoSort(\ArrayIterator $tickData): array
    {
        $tickData->uasort(function ($a, $b) {
            $tsa = (float) $a['timestamp'];
            $tsb = (float) $b['timestamp'];
            if ($tsa === $tsb) {
                return 0;
            }

            return ($tsa < $tsb) ? -1 : 1;
        });

        $lastElement = $tickData->offsetGet($tickData->count() - 1);
        $tickData->rewind();
        $firstElement = $tickData->offsetGet(0);
        $startTs = $firstElement['timestamp'];
        $endTs = $lastElement['timestamp'];

        return [
            'tickData' => new \ArrayIterator(array_values(iterator_to_array($tickData))),
            'startTs' => (int) $startTs,
            'endTs' => (int) $endTs,
        ];
    }
}
