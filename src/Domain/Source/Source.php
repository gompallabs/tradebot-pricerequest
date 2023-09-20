<?php

declare(strict_types=1);

namespace App\Domain\Source;

class Source
{
    private Exchange $exchange;
    private SourceType $sourceType;

    public function __construct(Exchange $exchange, SourceType $sourceType)
    {
        $this->exchange = $exchange;
        $this->sourceType = $sourceType;
    }

    public function getExchange(): Exchange
    {
        return $this->exchange;
    }

    public function getSourceType(): SourceType
    {
        return $this->sourceType;
    }

    public function toArray(): array
    {
        return [
            'exchange' => [
                'name' => $this->getExchange()->name,
            ],
            'sourceType' => $this->getSourceType()->name,
        ];
    }
}
