<?php

declare(strict_types=1);

namespace App\Domain\Source;

enum Category: string
{
    case spot = 'spot';
    case derivative = 'linear';
}
