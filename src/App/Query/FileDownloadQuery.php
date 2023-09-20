<?php

declare(strict_types=1);

namespace App\App\Query;

use App\Domain\Coin;
use App\Domain\Source\Exchange;

class FileDownloadQuery
{
    private Exchange $exchange;
    private Coin $coin;
    private ?array $options;

    public function __construct(
        Exchange $exchange,
        Coin $coin,
        ?array $options = []
    ) {
        $this->exchange = $exchange;
        $this->coin = $coin;
        $this->options = $options;
    }

    public function getExchange(): Exchange
    {
        return $this->exchange;
    }

    public function getCoin(): Coin
    {
        return $this->coin;
    }

    public function getOptions(): ?array
    {
        return $this->options;
    }
}
