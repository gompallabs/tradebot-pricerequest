<?php

declare(strict_types=1);

namespace App\App\Query;

use App\Domain\Coin;
use App\Domain\Source\Source;

class ApiRequestQuery
{
    private Source $source;
    private Coin $coin;

    public function __construct(
        Source $source,
        Coin $coin,
    ) {
        $this->source = $source;
        $this->coin = $coin;
    }

    public function getSource(): Source
    {
        return $this->source;
    }

    public function getCoin(): Coin
    {
        return $this->coin;
    }
}
