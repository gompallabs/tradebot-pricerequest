<?php

declare(strict_types=1);

namespace App\Domain;

final class Coin
{
    protected string $ticker;
    protected string $category;

    public function __construct(string $ticker, string $category)
    {
        $this->ticker = $ticker;
        $this->category = $category;
    }

    public function getTicker(): string
    {
        return $this->ticker;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function toArray(): array
    {
        return [
            'ticker' => $this->getTicker(),
            'category' => $this->getCategory(),
        ];
    }

    public function fromArray(array $data): self
    {
        return new self(
            ticker: $data['ticker'],
            category: $data['category']
        );
    }
}
