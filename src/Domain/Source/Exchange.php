<?php

declare(strict_types=1);

namespace App\Domain\Source;

enum Exchange: string
{
    case Bybit = 'Bybit';
    case Bitget = 'Bitget';
}
