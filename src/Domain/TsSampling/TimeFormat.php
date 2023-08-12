<?php

declare(strict_types=1);

namespace App\Domain\TsSampling;

enum TimeFormat: string
{
    case DotMilliseconds = 'DotMilliseconds';
    case IntMilliseconds = 'IntMilliseconds';
    case Seconds = 'Seconds';
}
