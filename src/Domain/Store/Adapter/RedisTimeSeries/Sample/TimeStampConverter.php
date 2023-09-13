<?php

declare(strict_types=1);

namespace App\Domain\Store\Adapter\RedisTimeSeries\Sample;

use Palicao\PhpRedisTimeSeries\TimeSeries\Exception\TimestampParsingException;

final class TimeStampConverter
{
    public static function dateTimeFromTimestampWithMs(int $timestamp): \DateTimeInterface
    {
        $dateTime = \DateTimeImmutable::createFromFormat('U.u', sprintf('%.03f', $timestamp / 1000));
        if ($dateTime === false) {
            throw new TimestampParsingException(sprintf('Unable to parse timestamp: %d', $timestamp));
        }

        return $dateTime;
    }
}
