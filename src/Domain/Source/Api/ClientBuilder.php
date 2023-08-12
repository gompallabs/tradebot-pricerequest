<?php

declare(strict_types=1);

namespace App\Domain\Source\Api;

use App\Domain\Source\SourceType;

interface ClientBuilder
{
    public function getClientForSource(SourceType $type, string $name): Client;
}
