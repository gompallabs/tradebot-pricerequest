<?php

declare(strict_types=1);

namespace App\Tests\Functionnal\Prototype\TsSampling\Infra;

use App\Domain\TimeFormat;

/**
 * @deprecated
 */
final class TsScale
{
    private int $start;
    private int $end;
    private int $stepSize;

    public function __construct(int $start, int $end, int $stepSize)
    {
        $this->start = $start;
        $this->end = $end;
        $this->stepSize = $stepSize;
    }

    public function toArrayIterator(TimeFormat $timeFormat): \ArrayIterator
    {
        $result = range($this->start, $this->end, $this->stepSize);

        return match ($timeFormat->value) {
            'DotMilliseconds' => new \ArrayIterator($result),
            'default' => throw new \LogicException('Missing case in '.__CLASS__)
        };
    }

    public static function createFromTickData(TickData $tickData, int $stepInSeconds): self
    {
        $tsTimeFormat = $tickData->getTimeFormat();
        [$start, $end] = $tickData->getMinMaxTime();

        return match ($tsTimeFormat->value) {
            'DotMilliseconds' => new self(
                start: (int) floor(floor(1000 * $start) / 1000),
                end: (int) ceil(ceil(1000 * $end) / 1000),
                stepSize: $stepInSeconds
            ),
            'default' => throw new \LogicException('Missing case in match expression '.$tsTimeFormat->value.' in'.__CLASS__)
        };
    }
}
