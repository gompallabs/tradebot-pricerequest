<?php

declare(strict_types=1);

namespace App\Infra\Source\Api;

use App\Domain\Source\SourceInterface;
use App\Domain\Source\SourceType;

class BybitApi implements SourceInterface
{
    public function getSourceType(): SourceType
    {
        return SourceType::RestApi;
    }
}
