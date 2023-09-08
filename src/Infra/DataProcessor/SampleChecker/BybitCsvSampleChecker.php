<?php

declare(strict_types=1);

namespace App\Infra\DataProcessor\SampleChecker;

use App\Domain\DataProcessor\SampleChecker;
use App\Domain\Source\Source;
use App\Domain\Source\SourceType;
use Assert\Assert;

class BybitCsvSampleChecker implements SampleChecker
{
    public function check(array $sample): bool
    {
        Assert::lazy()
            ->that($sample)
            ->isArray(' must be an array')
            ->keyExists('timestamp', 'must have a timestamp key')
            ->keyExists('side', 'must have a timestamp key')
            ->keyExists('size', 'must have a timestamp key')
            ->keyExists('price', 'must have a timestamp key')
            ->verifyNow();

        return true;
    }

    public function supports(Source $source)
    {
        return
            $source->getExchange()->name === 'Bybit'
            && $source->getSourceType() === SourceType::File;
    }
}
