<?php

declare(strict_types=1);

namespace App\Domain\Store\Adapter\RedisTimeSeries\Sample;

final class TimeStampConverter
{
    public static function dateTimeFromTimestampWithMs(int $timestamp): \DateTimeInterface
    {
        $dateTime = \DateTimeImmutable::createFromFormat('U.u', sprintf('%.03f', $timestamp / 1000));
        if ($dateTime === false) {
            throw new \RuntimeException(sprintf('Unable to parse timestamp: %d', $timestamp));
        }

        return $dateTime;
    }
}
