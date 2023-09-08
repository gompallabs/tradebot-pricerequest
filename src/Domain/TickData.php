<?php

namespace App\Domain;

class TickData
{
    private \ArrayIterator $data;

    public function __construct(\ArrayIterator $data)
    {
        $this->data = $data;
    }

    public function getOhlcv(): \Traversable
    {
        foreach ($this->data as $candle) {
            yield $candle;
        }
    }

    public function count(): int
    {
        return $this->data->count();
    }
}
