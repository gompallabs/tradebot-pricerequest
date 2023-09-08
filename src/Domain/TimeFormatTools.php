<?php

declare(strict_types=1);

namespace App\Domain;

class TimeFormatTools
{
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

    public static function scale(int|float $start, int|float $end, TimeFormat $timeFormat, ?int $stepSize = 1): \ArrayIterator
    {
        $start = match ($timeFormat->value) {
            'DotMilliseconds' => (int) floor($start),
            'default' => throw new \LogicException('missing case in match '.__CLASS__)
        };

        $end = match ($timeFormat->value) {
            'DotMilliseconds' => (int) ceil($end),
            'default' => throw new \LogicException('missing case in match '.__CLASS__)
        };

        return match ($timeFormat->value) {
            'DotMilliseconds' => new \ArrayIterator(range($start, $end, $stepSize)),
            'default' => throw new \LogicException('Missing case in '.__CLASS__)
        };
    }
}
