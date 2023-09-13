<?php

declare(strict_types=1);

namespace App\Infra\DataProcessor\SampleChecker;

use App\Domain\DataProcessor\SampleChecker;
use App\Domain\Source\Source;
use App\Domain\Source\SourceType;

class BybitApiSampleChecker implements SampleChecker
{
    public function check(array $sample)
    {
    }

    public function supports(Source $source): bool
    {
        return
            $source->getExchange()->name === 'Bybit'
            && $source->getSourceType() === SourceType::RestApi;
    }
}
