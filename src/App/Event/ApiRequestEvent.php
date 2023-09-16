<?php

declare(strict_types=1);

namespace App\App\Event;

use App\Domain\Coin;
use App\Domain\Source\Source;

class ApiRequestEvent
{
    private Source $source;
    private Coin $coin;
    private \ArrayIterator|array $data;

    public function __construct(Source $source, Coin $coin, array|\ArrayIterator $data)
    {
        $this->source = $source;
        $this->coin = $coin;
        $this->data = $data;
    }

    public function getSource(): array
    {
        return $this->source->toArray();
    }

    public function getCoin(): array
    {
        return $this->coin->toArray();
    }

    public function getData(): array|\ArrayIterator
    {
        return $this->data;
    }

    public function getExchangeName(): string
    {
        return $this->source->getExchange()->name;
    }
}
