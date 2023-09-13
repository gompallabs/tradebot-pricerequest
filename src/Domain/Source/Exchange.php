<?php

declare(strict_types=1);

namespace App\Domain\Source;

enum Exchange
{
    case Bybit;
    case Bitget;
}
