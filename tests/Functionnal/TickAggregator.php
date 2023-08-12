<?php

declare(strict_types=1);

namespace App\Tests\Functionnal;

use App\Domain\PriceOhlcv;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Aggregates the ticks that occurs in a same millisecond and returns one PriceOhLCV.
 * Since there is no check on the limits of the bucket, check before.
 * Tickdata from imported file looks like:
 * array:10 [
 *      "timestamp" => "1585132572.9822"
 *      "symbol" => "BTCUSDT"
 *      "side" => "Sell"
 *      "size" => "0.001"
 *      "price" => "6500.0"
 *      "tickDirection" => "MinusTick"
 *      "trdMatchID" => "1876995b-1ff2-53fa-903d-b1e22e987196"
 *      "grossValue" => "650000000.0"
 *      "homeNotional" => "0.001"
 *      "foreignNotional" => "6.5"
 * ]. Time is in seconds with 4 decimals
 * Pseudo tickdata from recent-trade endpoint looks like:
 * array:4 [
 *      "price" => "25902.70"
 *      "size" => "0.036"
 *      "side" => "Sell"
 *      "time" => "1692984630592"
 * ]. Time is in milliseconds.
 */
final class TickAggregator
{
    public static function getOne(array $tick, float $t): array
    {
        $price = $tick['price'];
        $ohlcv = new PriceOhlcv(
            tsms: (int) ($t * 1000),
            open: (float) $price,
        );

        $ohlcv->addVolume($tick['side'], (float) $tick['size']);

        return $ohlcv->toArray();
    }

    public static function aggregate(array $ticks, float $tsms): ?PriceOhlcv
    {
        if (1 === count($ticks)) {
            return null;
        }

        [$simpleTicksCollection, $min, $max] = self::extractSimpleTicks($ticks);
        [$open, $close] = self::extractOpenClose($simpleTicksCollection);
        [$buyVolume, $sellVolume] = self::extractVolumes($simpleTicksCollection);

        $ohlcv = new PriceOhlcv(
            tsms: $tsms,
            open: $open,
        );
        $ohlcv->setHigh($max);
        $ohlcv->setLow($min);
        $ohlcv->setBuyVolume($buyVolume);
        $ohlcv->setSellVolume($sellVolume);
        $ohlcv->setClose($close);

        return $ohlcv;
    }

    private static function extractSimpleTicks($ticks): array
    {
        $prices = array_map(function (array $tick) {
            return (float) $tick['price'];
        }, $ticks);

        $tickCollection = new ArrayCollection();
        foreach ($ticks as $tick) {
            $simpleTick = [
                'ts' => (float) $tick['timestamp'], // here float is required to sort timewise
                'side' => $tick['side'],
                'size' => (float) $tick['size'],
                'price' => (float) $tick['price'],
            ];
            $tickCollection->add($simpleTick);
        }
        $iterator = $tickCollection->getIterator();
        $iterator->uasort(function ($a, $b) {
            return ($a['ts'] < $b['ts']) ? -1 : 1;
        });

        return [new ArrayCollection(iterator_to_array($iterator)), min($prices), max($prices)];
    }

    private static function extractVolumes(ArrayCollection $simpleTicks): array
    {
        $sellTicks = array_filter($simpleTicks->toArray(), function ($tick) {
            return 'Sell' === $tick['side'];
        });

        $buyTicks = array_filter($simpleTicks->toArray(), function ($tick) {
            return 'Buy' === $tick['side'];
        });

        $sellVolume = round(array_sum(array_map(function (array $tick) {
            return $tick['size'];
        }, $sellTicks)), 4);

        $buyVolume = round(array_sum(array_map(function (array $tick) {
            return $tick['size'];
        }, $buyTicks)), 4);

        return [$buyVolume, $sellVolume];
    }

    private static function extractOpenClose(ArrayCollection $simpleTicks): array
    {
        return [$simpleTicks->first()['price'], $simpleTicks->last()['price']];
    }
}
