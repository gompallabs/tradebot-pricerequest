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
}
