<?php

declare(strict_types=1);

namespace App\Domain\TsSampling;

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

    public function toArray(): \ArrayIterator
    {
        $result = range($this->start, $this->end, $this->stepSize);
        usort($result, function ($a, $b) {
            if ($a == $b) {
                return 0;
            }

            return ($a < $b) ? -1 : 1;
        });

        return new \ArrayIterator($result);
    }
}
