<?php

declare(strict_types=1);

namespace App\Domain\Source;

enum SourceType
{
    case RestApi;
    case File;
}
