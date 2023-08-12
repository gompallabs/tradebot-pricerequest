<?php

declare(strict_types=1);

namespace App\Domain\Source;

interface SourceInterface
{
    public function getSourceType(): SourceType;
}
